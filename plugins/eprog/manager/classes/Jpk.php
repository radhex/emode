<?php namespace Eprog\Manager\Classes;

use Eprog\Manager\Models\SettingJpk;
use Eprog\Manager\Models\SettingConfig;
use Rainlab\User\Models\User;
use Session;

class Jpk
{
    private string $baseUrl;
    private string $mfPublicKeyPem;
    private string $version;

    public function __construct()
    {
        if(SettingJpk::get("mode") == "test"){
            $this->baseUrl = rtrim('https://test-e-dokumenty.mf.gov.pl/api/Storage', '/');
            $this->mfPublicKeyPem = 'docs/mf_public_test.pem';
        }
        else {
            $this->baseUrl = rtrim('https://e-dokumenty.mf.gov.pl/api/Storage', '/');
            $this->mfPublicKeyPem = 'docs/mf_public_prod.pem';
        }
    }

    public function jpkSend($xml, $version): string
    {


        $this->version = $version;
        $nip = Session::get("selected.nip") ?? '';

        if(SettingConfig::get("nip") == $nip){
            $name = SettingConfig::get("name") ?? "";
            $surname = SettingConfig::get("surname") ?? "";
            $birthday = SettingConfig::get("birthday") ?? "";
            $kwota  = str_replace(",",".",str_replace(" ","",SettingConfig::get("tax_amount") ?? ""));
        }  
        else{
            $user = User::where("firm_nip",$nip)->first();
            $name = $user->name ?? "";
            $surname = $user->surname ?? "";
            $birthday = $user->birthday ?? "";
            $kwota  = str_replace(",",".",str_replace(" ","",$user->tax_amount ?? ""));
        }

        return $this->send($xml, [
            'nip' => $nip,
            'imie' => $name,
            'nazwisko' => $surname,
            'dataUrodzenia' => $birthday,
            'kwota' => $kwota
        ]);
    }

    private function buildDaneAutoryzujace(array $d): string
    {
        return <<<XML
<podp:DaneAutoryzujace xmlns:podp="http://e-deklaracje.mf.gov.pl/Repozytorium/Definicje/Podpis/">
    <podp:NIP>{$d['nip']}</podp:NIP>
    <podp:ImiePierwsze>{$d['imie']}</podp:ImiePierwsze>
    <podp:Nazwisko>{$d['nazwisko']}</podp:Nazwisko>
    <podp:DataUrodzenia>{$d['dataUrodzenia']}</podp:DataUrodzenia>
    <podp:Kwota>{$d['kwota']}</podp:Kwota>
</podp:DaneAutoryzujace>
XML;
    }

    private function encryptAuthData(string $authXml, string &$aesKey, string &$iv): string
    {
        $aesKey = random_bytes(32);
        $iv = random_bytes(16);

        $cipher = openssl_encrypt($authXml, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) throw new \RuntimeException('AES AuthData failed');

        return base64_encode($cipher);
    }

    private function encryptAesKeyWithMf(string $aesKey): string
    {
        $pub = file_get_contents($this->mfPublicKeyPem);
        if (!$pub) throw new \RuntimeException('MF public key missing');

        openssl_public_encrypt($aesKey, $out, $pub, OPENSSL_PKCS1_PADDING);
        return base64_encode($out);
    }

    private function buildInitUploadXml(array $m): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<InitUpload xmlns="http://e-dokumenty.mf.gov.pl">
    <DocumentType>JPK</DocumentType>
    <Version>01.02.01.20160617</Version>
    <EncryptionKey algorithm="RSA" mode="ECB" padding="PKCS#1" encoding="Base64">{$m['encKey']}</EncryptionKey>
    <DocumentList>
        <Document>
            <FormCode systemCode="{$m['systemCode']}" schemaVersion="{$m['schemaVersion']}">{$m['formCode']}</FormCode>
            <FileName>{$m['fileName']}</FileName>
            <ContentLength>{$m['xmlLen']}</ContentLength>
            <HashValue algorithm="SHA-256" encoding="Base64">{$m['xmlHash']}</HashValue>
            <FileSignatureList filesNumber="1">
                <Packaging><SplitZip type="split" mode="zip"/></Packaging>
                <Encryption>
                    <AES size="256" block="16" mode="CBC" padding="PKCS#7">
                        <IV bytes="16" encoding="Base64">{$m['iv']}</IV>
                    </AES>
                </Encryption>
                <FileSignature>
                    <OrdinalNumber>1</OrdinalNumber>
                    <FileName>{$m['zipName']}</FileName>
                    <ContentLength>{$m['zipLen']}</ContentLength>
                    <HashValue algorithm="MD5" encoding="Base64">{$m['zipMd5']}</HashValue>
                </FileSignature>
            </FileSignatureList>
        </Document>
    </DocumentList>
    <AuthData>{$m['authData']}</AuthData>
</InitUpload>
XML;
    }

    public function send(string $jpkXml, array $auth): string
    {
        $authXml = $this->buildDaneAutoryzujace($auth);
        $aesKey = $iv = '';
        $authData = $this->encryptAuthData($authXml, $aesKey, $iv);
        $encKey = $this->encryptAesKeyWithMf($aesKey);

        $zip = new \ZipArchive();
        $zipPath = tempnam(sys_get_temp_dir(), 'jpk');
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('JPK.xml', $jpkXml);
        $zip->close();
        $zipRaw = file_get_contents($zipPath);
        unlink($zipPath);

        $zipEnc = openssl_encrypt($zipRaw, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($zipEnc === false) throw new \RuntimeException('ZIP AES failed');
    
        $initXml = $this->buildInitUploadXml([
            'encKey' => $encKey,
            'systemCode' => $this->version,
            'schemaVersion' => '1-0E',
            'formCode' => 'JPK_VAT',
            'fileName' => 'JPK.xml',
            'xmlLen' => strlen($jpkXml),
            'xmlHash' => base64_encode(hash('sha256', $jpkXml, true)),
            'iv' => base64_encode($iv),
            'zipName' => 'JPK.xml.zip.0.aes',
            'zipLen' => strlen($zipEnc),
            'zipMd5' => base64_encode(md5($zipEnc, true)),
            'authData' => $authData
        ]);

        $resp = $this->postXml('/InitUploadSigned', $initXml);

        $ref = $resp['ReferenceNumber'];
        $azureBlobNameList = $resp['AzureBlobNameList'] ?? [];

        foreach ($resp['RequestToUploadFileList'] as $file) {
            $headers = [];
            foreach ($file['HeaderList'] as $h) {
                $headers[] = $h['Key'] . ': ' . $h['Value'];
            }

            $ch = curl_init($file['Url']);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $file['Method'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $zipEnc
            ]);

            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code !== 200 && $code !== 201) {
                throw new \RuntimeException('UploadSigned failed');
            }
        }

        $this->postXmlFinish('/FinishUpload', $ref, $azureBlobNameList);

        return $ref;
    }

    public function getStatus(string $ref): array
    {
        return json_decode(file_get_contents($this->baseUrl . '/Status/' . $ref), true);
    }

    public function getUpo(string $ref): array
    {
        return json_decode(file_get_contents($this->baseUrl . '/Status/' . $ref), true);
    }

    private function postXml(string $path, string $xml): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/xml', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => $xml
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 400) throw new \RuntimeException("MF $code: $resp");
        return json_decode($resp, true);
    }

    private function postXmlFinish(string $path, string $ref, array $azureBlobNameList): array
    {
        $payload = [
            'ReferenceNumber' => $ref,
            'AzureBlobNameList' => $azureBlobNameList
        ];

        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 400) {
            throw new \RuntimeException("MF $code: $resp");
        }

        return json_decode($resp, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function verifyJpkXml($xml, $wersja)
    {
        $xsd = "jpk_schema_v2.xsd";
        if($wersja == "JPK_V7M (3)")
            $xsd = "jpk_schema_v3.xsd";

        $dom = new \DOMDocument(); 
        $dom->loadXML($xml); 

        if (!$dom->schemaValidate('docs/'.$xsd)) {
            print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
            libxml_display_errors();
        }
        else 
            return $xml;
    }

    public static function getUpoHtml($xml)
    {
        $xml_doc = new \DOMDocument();
        $xml_doc->loadXml($xml);
        //file_put_contents("upo.xml",$xml);

        $xsl_doc = new \DOMDocument();
        $xsl_doc->load("docs/jpk_upo.xsl");
        
        $proc = new \XSLTProcessor();
        $proc->importStylesheet($xsl_doc);
        return $proc->transformToXml($xml_doc);
    }
}
