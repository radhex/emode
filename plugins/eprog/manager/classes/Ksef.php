<?php namespace Eprog\Manager\Classes;

use Carbon\Carbon;
use Lang;
use October\Rain\Argon\Argon;
use Backend\Helpers\Backend;
use Session;
use BackendAuth;
use Timezone;
use Config;
use Settings;
use Auth;
use Backend\Models\UserPreference;
use Rainlab\User\Models\User;
use DateTime;
use DateTimeZone;
use Redirect;
use Mail;
use Eprog\Manager\Models\Category;
use Eprog\Manager\Models\Refreshtoken;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\SettingKsef;
use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Models\Ksef as ModelKSef;
use System\Models\File as SystemFile;
use October\Rain\Exception\ValidationException;
use Flash;
use Input;
use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawHandler;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesAction;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesHandler;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\DTOs\QRCodes;
use N1ebieski\KSEFClient\DTOs\Requests\Sessions\Online\Faktura;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\Requests\Sessions\Online\Send\SendXmlRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\ReferenceNumber;
use N1ebieski\KSEFClient\Requests\Invoices\Download\DownloadRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\KsefNumber;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Upo\UpoRequest;
use N1ebieski\KSEFClient\Actions\ConvertCertificateToPkcs12\ConvertCertificateToPkcs12Handler;
use N1ebieski\KSEFClient\Actions\ConvertCertificateToPkcs12\ConvertCertificateToPkcs12Action;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendXmlRequest;
use N1ebieski\KSEFClient\Actions\DecryptDocument\DecryptDocumentAction;
use N1ebieski\KSEFClient\Actions\DecryptDocument\DecryptDocumentHandler;
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Init\InitRequest;
use N1ebieski\KSEFClient\Requests\Invoices\Query\Metadata\MetadataRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\SubjectType;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\DateType;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\DateRangeFrom;
use N1ebieski\KSEFClient\Requests\Invoices\Exports\Status\StatusRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\FormCode;
use N1ebieski\KSEFClient\DTOs\Requests\Invoices\Exports\Filters;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\PageSize;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\FormType;
use N1ebieski\KSEFClient\ValueObjects\Requests\PageOffset;
use N1ebieski\KSEFClient\ValueObjects\Requests\SortOrder;
use N1ebieski\KSEFClient\ValueObjects\CertificateSerialNumber;
use N1ebieski\KSEFClient\ValueObjects\CertificatePath;
use N1ebieski\KSEFClient\DTOs\Requests\Auth\ContextIdentifierGroup;
use N1ebieski\KSEFClient\Factories\CertificateFactory;
use N1ebieski\KSEFClient\DTOs\Requests\Invoices\DateRange;
use N1ebieski\KSEFClient\Support\Optional;
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use DateTimeInterface;
use N1ebieski\KSEFClient\ValueObjects\NIP;
use N1ebieski\KSEFClient\Actions\GeneratePDF\GeneratePDFAction;
use N1ebieski\KSEFClient\Actions\GeneratePDF\GeneratePDFHandler;
use N1ebieski\KSEFClient\ValueObjects\KsefFeInvoiceConverterPath;





class Ksef
{
    //5313151498
    public static $url   = "https://ksef-test.mf.gov.pl";
    public static $nip   = "4175135651";// "5260309174";
    public static $token = "A976F9D80D8B69AC31C5CE923DAAAEE305B35CECE08D7228FEA35CC46077A3B4";
    //public static $token = 20251103-EC-48863DA000-40E14979AE-89|nip-4175135651|9a9a829cba8c4c00992854273767383ad8508dc9a4f1492d8295578b6a7da2a1
    //public static $nip   = "1111111111";
    //public static $token = "15AD9547F473F4419CBADA8ECD43416EA0AF1797DC52C34E1A0F4F34D0ADF325";
    //public static $token = "68DADDD9A3977201DEF531CBDB0012E4C9C4ED034762D5DBBECEDF221FEFA168";
    //public static $token = "20251112-EC-45F9D13000-BDBC23D5F7-45|nip-1111111111|242a3a4d92f1485684da17441c2c444174b51140834148feb1b4b1a7a9d0c1b8"; //1111111111
    //public static $token = "20251116-EC-22D6DF6000-2CAB23EDDE-E9|nip-1111111111|5027e0ff9cff489b91e7629994ca8bff7b801cec80d242e5a4845916fe1f48f2"; //1111111111
    //public static $token = "20260103-EC-27A4B02000-3BD7938A3A-52|nip-1111111111|7173aab372fd423b97515d14618a775f166c73ece8184f2e8177f698da1432a4"; //1111111111
    //public static $token = "20260131-EC-228B4A3000-67D7A17BEA-DF|nip-1111111111|91d22c71f7bb477b8adecb790ec43a0a179ab02dfa6e4bb7b80c1e8356c7f7f5"; //1111111111
    //public static $token = "20260131-EC-22B8411000-E135298BBB-CF|nip-4175135651|9ad412ce19314aea9197f230588cbc13fdb8ec8454df4d82abecf5c2c8123bbd"; //4175135651

    //5449026350
    
  
    //8125894003 20251229-EC-2E52947000-AAA7E3BE5C-E7|nip-8125894003|8c74494c664543598a7682b417e2b955a3d55ce2448a4ae8aa480b9236b1b86a


    public static function buildClient($nip = null, $token = null, $type = null, $mode_ksef = null){

        $nip = $nip ??  str_replace("-","", SettingKsef::get("nip"));

        if(!$type) $type = SettingKsef::get("type") ?? 'cert';

        $certpath = "";
        if(SettingKsef::instance()->cert_auth_p12)
        $certpath = SettingKsef::instance()->cert_auth_p12->getLocalPath() ?? '';
        $certpass = SettingKsef::get("pass_auth") ?? '';
        if(!$token) $token = SettingKsef::get("token") ?? '';

        if(!$mode_ksef) $mode_ksef = SettingKsef::get("mode") ?? "test";
        if(Session::has("ksef.mode")) $mode_ksef = Session::get("ksef.mode");

        if($mode_ksef == "production")
            $mode = Mode::Production;
        else if($mode_ksef == "demo")
            $mode = Mode::Demo; 
        else if($mode_ksef == "test")
            $mode = Mode::Test;

        $encryptionKey = EncryptionKeyFactory::makeRandom();
        if(!Session::has("ksef")){
            try{
                if($type == "cert"){
                    $client = (new ClientBuilder())
                        ->withMode($mode)
                        ->withIdentifier($nip)
                        ->withCertificatePath($certpath, $certpass)
                        ->withEncryptionKey($encryptionKey)
                        ->withVerifyCertificateChain(false)
                        ->build();
                }
                else if($type == "token"){
                    $client = (new ClientBuilder())
                        ->withMode($mode)
                        ->withIdentifier($nip)
                        ->withKsefToken($token)
                        ->withEncryptionKey($encryptionKey)
                        ->build();
                }
            }
            catch(\Exception $e){
                throw new ValidationException(['my_field'=>$e->getMessage()]);
            }

            if($client){
                Session::put("ksef.accessToken.token",$client->getAccessToken()->token);
                Session::put("ksef.accessToken.validUntil",date("Y-m-d H:i:s",$client->getAccessToken()->validUntil->getTimestamp()));
                Session::put("ksef.refreshToken.token",$client->getRefreshToken()->token);
                Session::put("ksef.refreshToken.validUntil",date("Y-m-d H:i:s",$client->getRefreshToken()->validUntil->getTimestamp()));
            }

        }
        else{
            $client = (new ClientBuilder())
                ->withMode($mode)
                ->withAccessToken(Session::get("ksef.accessToken.token"),Session::get("ksef.accessToken.validUntil"))
                ->withRefreshToken(Session::get("ksef.refreshToken.token"),Session::get("ksef.refreshToken.validUntil"))
                ->withEncryptionKey($encryptionKey)
                ->build();           
        }

        return [$client, $encryptionKey];
    }


    public static function convertp12(){

        try{
            if(SettingKsef::instance()->cert_crt)
                $certpempath = SettingKsef::instance()->cert_crt->getLocalPath() ?? '';
            else
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_file_crt")]);

            if(SettingKsef::instance()->cert_prv)
                $certkeypath = SettingKsef::instance()->cert_prv->getLocalPath() ?? '';
            else
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_file_key")]);

            $certpass = SettingKsef::get("pass_cert") ?? '';
            $certPem = file_get_contents($certpempath);
            $privateKey = openssl_get_privatekey("file://".$certkeypath,$certpass);
            if($privateKey){
                $certificateToPkcs12 = (new ConvertCertificateToPkcs12Handler())->handle(
                    new ConvertCertificateToPkcs12Action(
                        certificate: new Certificate($certPem, [], $privateKey),
                        passphrase: $certpass
                    )
                );
            }
            else
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.ksef.privatekey_error")]);
            

            $file = storage_path('temp/public/certificate.p12');
            file_put_contents($file , $certificateToPkcs12);
        }
        catch(\Exception $e){
            throw new ValidationException(['my_field'=>$e->getMessage()]);
        }


     
    }



    public static function getInvoiceAPI(){

            list($client,$encryptionKey) = self::buildClient();

            $url = "https://ksef-test.mf.gov.pl/api/v2/invoices/query/metadata?pageSize=150&sortOrder=Desc";

            $params = json_encode(array(  
                            "subjectType"  => "Subject1" ,          
                            "dateRange" => array(
                                "dateType" => "PermanentStorage",
                                "from" => "2025-08-28T09:22:13.388+00:00"
                            )                               
            ));

            $authorization = "Authorization: Bearer ".$client->getAccessToken()->token;


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));


            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            dd(json_decode($result, true));

    }


    public static function getInvoices($nip, $limit, $offset){

            list($client,$encryptionKey) = self::buildClient($nip);
            $request = new MetadataRequest(
                SubjectType::Subject2, 
                new DateRange(DateType::PermanentStorage,new DateRangeFrom("2025-08-28T09:22:13.388+00:00")),
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                new Optional,
                SortOrder::Desc,
                new PageSize($limit),
                new PageOffset($offset),
            );
            $response = $client->invoices()->query()->metadata($request)->object();
            dd($response);
            return $response;

    }

    public static function exportInvoices($nip, $subject = "Subject1", $from, $to){

            if($from == "" && $to == "") return;
            $to = $to != ""  ? $to." 23:59" : '';
            $from = $from != ""  ? $from." 0:00" : '';
            if($to != "" && $from == "") $from = date('Y-m-d H:i:s', strtotime($to.' -2 years'));
            if($from != "" && $to == "") $to = date('Y-m-d H:i:s', strtotime($from.' +2 years'));

            list($client,$encryptionKey) = self::buildClient($nip);

            $initResponse = $client->invoices()->exports()->init([
                'filters' => [
                    'subjectType' => $subject,
                    'dateRange' => [
                        'dateType' => 'Issue',
                        'from' => (new \DateTimeImmutable($from))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
                        'to' => (new \DateTimeImmutable($to))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
                    ],
                ]
            ])->object();

            $statusResponse = Utility::retry(function () use ($client, $initResponse) {
                $statusResponse = $client->invoices()->exports()->status([
                    'referenceNumber' => $initResponse->referenceNumber
                ])->object();

                if ($statusResponse->status->code === 200) {
                    return $statusResponse;
                }

                if ($statusResponse->status->code >= 400) {
                    throw new \RuntimeException(
                        $statusResponse->status->description,
                        $statusResponse->status->code
                    );
                }
            });

            $decryptDocumentHandler = new DecryptDocumentHandler();

            $zipContents = '';

            foreach ($statusResponse->package->parts as $part) {
                $contents = file_get_contents($part->url);

                $contents = $decryptDocumentHandler->handle(new DecryptDocumentAction(
                    document: $contents,
                    encryptionKey: $encryptionKey
                ));

                $zipContents .= $contents;
            }

            $dirzip = storage_path('temp/public/invoices');
            if (!is_dir($dirzip)) mkdir($dirzip, 0777, true);
            $filezip = storage_path('temp/public/invoices.zip');        
            if(file_exists($filezip)) unlink($filezip);
            system("rm -rf ".escapeshellarg($dirzip));
            file_put_contents($filezip, $zipContents);

            $unzip = new \ZipArchive;
            $out = $unzip->open($filezip );
            if ($out === TRUE) {
              $unzip->extractTo($dirzip);
              $unzip->close();
            } else {
              echo 'Error';
            }
            //var_dump($statusResponse);
           
            $files = scandir($dirzip);
            $metafile = storage_path('temp/public/invoices/_metadata.json');
            if(file_exists($metafile)){
                $meta = json_decode(file_get_contents($metafile), true);          
                $ksef = new ModelKSef();
                foreach($meta['invoices'] as $invoice){

                    $xml = storage_path('temp/public/invoices/'.$invoice['ksefNumber'].'.xml');
                    $xml = file_exists($xml) ? file_get_contents($xml) : '';                    
                    $json = json_encode($xml);
                    $data = json_decode($json, true);
                    $exists = $ksef->where("nip","=",$nip)->where("subject","=",$subject)->where("ksefNumber","=",$invoice['ksefNumber'])->count();
                    if($exists < 1 && $xml){
                        $fa = json_decode(json_encode(simplexml_load_string(Util::removeNamespace($xml))),TRUE);
                        $vat_amount = $invoice['vatAmount'] ?? '';
                        if($invoice['currency'] != "PLN"){                                               
                            $vat25 = $fa['Fa']['P_14_1'] ?? 0;
                            $vat8 = $fa['Fa']['P_14_2'] ?? 0;
                            $vat5 = $fa['Fa']['P_14_3'] ?? 0;
                            $vat_amount = round(($vat25 + $vat8 + $vat5),2);                   
                        }
                        $ksef->create([
                         'nip' => $nip,   
                         'subject' => $subject, 
                         'saleDate' => date("Y-m-d H:i:s", strtotime($fa['Fa']['P_6'] ?? $invoice['issueDate'] ?? '')),  
                         'issueDate' => date("Y-m-d H:i:s", strtotime($invoice['issueDate'] ?? '')),  
                         'permanentStorageDate' => date("Y-m-d H:i:s", strtotime($invoice['permanentStorageDate'] ?? '')),       
                         'invoicingDate' => date("Y-m-d H:i:s", strtotime($invoice['invoicingDate'] ?? '')), 
                         'acquisitionDate' => date("Y-m-d H:i:s", strtotime($invoice['acquisitionDate'] ?? '')), 
                         'ksefNumber' => $invoice['ksefNumber'] ?? '',   
                         'invoiceNumber' => $invoice['invoiceNumber'] ?? '',  
                         'sellerNip' => $invoice['seller']['nip'] ?? '',   
                         'sellerName' => $invoice['seller']['name'],   
                         'buyerIdentifierType' => $invoice['buyer']['identifier']['type'] ?? '',  
                         'buyerIdentifierValue' => $invoice['buyer']['identifier']['value'] ?? '', 
                         'buyerName' => $invoice['buyer']['name'] ?? '',  
                         'netAmount' => $invoice['netAmount'] ?? '',  
                         'grossAmount' => $invoice['grossAmount'] ?? '', 
                         'vatAmount' => $vat_amount,   
                         'currency' => $invoice['currency'] ?? '',    
                         'invoicingMode' => $invoice['invoicingMode'] ?? '',  
                         'invoiceType' => $invoice['invoiceType'] ?? '',  
                         'formCode' => json_encode($invoice['formCode']) ?? '',  
                         'isSelfInvoicing' => $invoice['isSelfInvoicing'] ?? '',   
                         'hasAttachment' => $invoice['hasAttachment'] ?? '',   
                         'invoiceHash' => $invoice['invoiceHash'] ?? '',    
                         'thirdSubjects' =>  json_encode($invoice['thirdSubjects']) ?? '',  
                         'xml' => $xml,  
                        ]);
                    }
                }
            }

            if(file_exists($filezip)) unlink($filezip);
            system("rm -rf ".escapeshellarg($dirzip));
      
        return [$statusResponse->package->isTruncated, $statusResponse->package->lastPermanentStorageDate ?? '']; 
    }

    public static function exportInvoices1($limit, $offset){

            $type = Input::filled("type") ? [Input::get("type")] : new Optional;

            list($client,$encryptionKey) = self::buildClient();

            if(!Session::has("ksef.export.referenceNumber")){
                $request = new InitRequest(new Filters(
                    SubjectType::Subject1, 
                    new DateRange(DateType::PermanentStorage,new DateRangeFrom("2025-08-28T09:22:13.388+00:00")),
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    new Optional,
                    SortOrder::Desc,
                    new PageSize($limit),
                    new PageOffset($offset),
                ));
                $response = $client->invoices()->exports()->init($request)->object();
                if($response->referenceNumber)
                Session::put("ksef.export.referenceNumber",$response->referenceNumber);
            
            }
        
            if(Session::has("ksef.export.referenceNumber")){
                $status = $client->invoices()->exports()->status(
                    new StatusRequest(new ReferenceNumber(Session::get("ksef.export.referenceNumber")))
                )->object();
                if($status->status->code == 200)
                Session::forget("export.referenceNumber");
                return $status;
            }


    }

    public static function unzipAES(){

            //$status = self::($exportInvoices(200,0));

            $outputFile = 'plik.zip';
            $password = 'YcK8odnm/e59PfKfu/vHegCTOCupi0gMBYomJEYGCqE=';

            $encrypted = file_get_contents("https://ksef-test.mf.gov.pl/storage/02/20251110-eh-2b91122000-bbd8c64530-e4/invoice-package/20251110-EH-2B91122000-BBD8C64530-E4-001.zip.aes?skoid=0e92608a-831d-404b-9945-197ed82a5dbc&sktid=647754c7-3974-4442-a425-c61341b61c69&skt=2025-11-07T15%3A20%3A38Z&ske=2025-11-14T15%3A20%3A38Z&sks=b&skv=2025-01-05&sv=2025-01-05&st=2025-11-10T12%3A40%3A03Z&se=2025-11-10T13%3A45%3A03Z&sr=b&sp=r&sig=U%2FhDUCJ8OG81GtR%2FiaR825W7PVP2y6zxXwyE9x1X3Ig%3D");
            $iv = substr($encrypted, 0, 16);
            $ciphertext = substr($encrypted, 16);
            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $password, OPENSSL_RAW_DATA, $iv);

            // Zapisz odszyfrowany plik ZIP
            file_put_contents($outputFile, $decrypted);

    }

    public static function invoiceSendMultiple($invoices){

        list($client,$encryptionKey) = self::buildClient();

        $openResponse = $client->sessions()->batch()->openAndSend( new OpenAndSendXmlRequest(FormCode::Fa3,$invoices))->object();

        $client->sessions()->batch()->close([
            'referenceNumber' => $openResponse->referenceNumber
        ]);

        $statusResponse = Utility::retry(function () use ($client, $openResponse) {
            $statusResponse = $client->sessions()->status([
                'referenceNumber' => $openResponse->referenceNumber,
            ])->object();

            if ($statusResponse->status->code === 200) {
                return $statusResponse;
            }

            if ($statusResponse->status->code >= 400) {
                throw new ValidationException(['my_field'=> $statusResponse->status->code." ".$statusResponse->status->description]);
                /*
                throw new \RuntimeException(
                    $statusResponse->status->description,
                    $statusResponse->status->code
                );
                */
            }
        });

        $upo = file_get_contents($statusResponse->upo->pages[0]->downloadUrl);
        
        return $upo;

    }

    public static function invoiceSend($invoice){


        list($client,$encryptionKey) = self::buildClient();

        $openResponse = $client->sessions()->online()->open([
            'formCode' => 'FA (3)',
        ])->object();

        
        $sendResponse = $client->sessions()->online()->send(new SendXmlRequest(new ReferenceNumber($openResponse->referenceNumber),$invoice))->object();

        $closeResponse = $client->sessions()->online()->close([
            'referenceNumber' => $openResponse->referenceNumber
        ]);

        $statusResponse = Utility::retry(function () use ($client, $openResponse, $sendResponse) {
            $statusResponse = $client->sessions()->invoices()->status([
                'referenceNumber' => $openResponse->referenceNumber,
                'invoiceReferenceNumber' => $sendResponse->referenceNumber
            ])->object();

            if ($statusResponse->status->code === 200) {
                return $statusResponse;
            }

            if ($statusResponse->status->code >= 400) {
                throw new ValidationException(['my_field'=>$statusResponse->status->description]);
            }
        });

        $fa = json_decode(json_encode(simplexml_load_string($invoice)),TRUE);
/*
        $xml = $client->invoices()->download(new DownloadRequest(new KsefNumber($statusResponse->ksefNumber)))->body();

        $upo = $client->sessions()->invoices()->upo([
            'referenceNumber' => $openResponse->referenceNumber,
            'invoiceReferenceNumber' => $sendResponse->referenceNumber
        ])->body();

    
        $generateQRCodesHandler = new GenerateQRCodesHandler(
            qrCodeBuilder: (new QrCodeBuilder())
                ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
                ->labelFont(new OpenSans(size: 12)),
            convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
        );


        $qrCodes = $generateQRCodesHandler->handle(GenerateQRCodesAction::from([
            'nip' => $fa['Podmiot1']['DaneIdentyfikacyjne']['NIP'],
            'invoiceCreatedAt' => $fa['Naglowek']['DataWytworzeniaFa'],
            'document' => $invoice,
            'ksefNumber' => $statusResponse->ksefNumber
        ]));


        $directory = "invoices/".$statusResponse->ksefNumber;
        if (!is_dir($directory)) mkdir($directory, 0777, true);
            
 
        //file_put_contents($directory."/invoice.xml", $invoice);
        //file_put_contents($directory."/upo.xml", $upo);
        //file_put_contents($directory."/qrcode.png", $qrCodes->code1);


*/

        self::stat($fa);
        return["referenceNumber" => $openResponse->referenceNumber, "invoiceReferenceNumber" => $sendResponse->referenceNumber, "ksefNumber" => $statusResponse->ksefNumber];

    }

    public static function getSession(){

        $result = self::authorisationChallenge(self::$nip);
        $result = json_decode($result, true);

        if(isset($result['timestamp']) && isset($result['challenge'])) {

            $timestamp = strtotime($result['timestamp']);
            $publicKey = file_get_contents("publicKey.pem");
            $encrypt = self::$token . '|' . $timestamp*1000;

            openssl_public_encrypt($encrypt , $encrypted, $publicKey);
            $initToken  = base64_encode($encrypted);

            $init = implode(file("docs/ksef.xml",FILE_IGNORE_NEW_LINES));
            $init = preg_replace(["/{{nip}}/i","/{{challenge}}/i","/{{token}}/i"], [self::$nip, $result['challenge'], $initToken], $init);

            $sessionToken = json_decode(self::initToken($init), true);
            if(isset($sessionToken["referenceNumber"]) && isset($sessionToken["referenceNumber"])) {

                if(!isset($_SESSION)) session_start();
                $_SESSION["ksef"]["referenceNumber"] = $sessionToken["referenceNumber"];;   
                $_SESSION["ksef"]["sessionToken"] = $sessionToken["sessionToken"]["token"];; 
                return $_SESSION["ksef"];

            }
            else
                return false;
         }
         else
             return false;

    }
  
    public static function authorisationChallenge(){


        $url =  self::$url."/api/v2/online/Session/AuthorisationChallenge";

        $ch = curl_init();
      
        $searchQuery = json_encode(array(
                "contextIdentifier" => array(
                  "type" => "onip",
                  "identifier" => self::$nip              
                )
        ));

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $searchQuery);

        $headers = array(
          'accept: application/json',
          'Content-Type: application/json'

        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        \Log::info($result);
        curl_close($ch);

        return $result;


    }

    public static function initToken($xml){

        $url =  self::$url."/api/online/Session/InitToken";

        $ch = curl_init();
      
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $headers = array(
          'accept: application/json',
          'Content-Type: application/octet-stream',
          'Content-Length' =>  strlen($xml)

        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        \Log::info($result);
        curl_close($ch);

        return $result;


    }


    public static function invoiceMax($types){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){


            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = "https://ksef-test.mf.gov.pl/api/online/Query/Invoice/Sync?PageSize=10&PageOffset=0";

            $ch = curl_init();
            

            $searchQuery = json_encode(array(
                            "queryCriteria" => array(
                                "type" => "detail",
                                "subjectType" => "subject1",
                                "invoiceTypes" => $types,
                                "invoicingDateFrom" => "2022-01-01T01:00:00+02:00",
                                "invoicingDateTo" => gmdate("Y-m-d\TH:i:s\Z"),
                            )
            ));

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $searchQuery);

            $headers = array(
                'accept: application/json',
                'Content-Type: application/json',
                'SessionToken: '.$sessionToken,
            );
        
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            //\Log::info($result);  
            curl_close($ch);
            $result = json_decode($result, true);

            if(isset($result["invoiceHeaderList"][0]["invoiceReferenceNumber"]))
            return $result["invoiceHeaderList"][0]["invoiceReferenceNumber"];

        }

    }

    public static function invoiceExists($nr){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){


            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = "https://ksef-test.mf.gov.pl/api/online/Query/Invoice/Sync?PageSize=10&PageOffset=0";

            $ch = curl_init();
            

            $searchQuery = json_encode(array(
                            "queryCriteria" => array(
                                "type" => "detail",
                                "subjectType" => "subject1",
                                "invoiceNumber" => $nr,
                                "invoicingDateFrom" => "2022-01-01T01:00:00+02:00",
                                "invoicingDateTo" => gmdate("Y-m-d\TH:i:s\Z"),
                            )
            ));

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $searchQuery);

            $headers = array(
                'accept: application/json',
                'Content-Type: application/json',
                'SessionToken: '.$sessionToken,
            );
        
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            //\Log::info($result);  
            curl_close($ch);
            $result = json_decode($result, true);
 
            if(isset($result["numberOfElements"]) && $result["numberOfElements"] > 0)
                return true;
            else
                return false;

        }

    }


    public static function invoiceSendOLd($invoice){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){

            $exists = Ksef::invoiceExists(Input::get("Invoice.nr")); 
            if($exists)
                throw new ValidationException(['my_field'=>'Faktura o tym numerze już istnieje w systemie KSEF']);

            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = self::$url."/api/online/Invoice/Send";
        
            $size = strlen($invoice);
            $value = base64_encode(hash('sha256', $invoice, true));

            $ch = curl_init();

            $invoice = base64_encode($invoice);
        
            $params = json_encode(array(
                            "invoiceHash" => array(
                                "hashSHA" => array(
                                    "algorithm"  => "SHA-256",
                                    "encoding" =>  "Base64",
                                    "value" => $value
                                ),
                            "fileSize" => $size,
                            ),
                            "invoicePayload" => array(
                                "type" => "plain",
                                "invoiceBody" => $invoice
                            )                                   
            ));

        
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            $headers = array(
                'accept: application/json',
                'Content-Type: application/json',
                'SessionToken: '.$sessionToken,
            );
        
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
           // \Log::info($result);  
            curl_close($ch);

            $result = json_decode($result, true);


            return $result;
    

        }

    }

    public static function invoiceStatus($referenceNumber){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){

            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = self::$url."/api/online/Invoice/Status/".$referenceNumber;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
            $headers = array(
                'Content-Type: application/json',
                'SessionToken: '.$sessionToken,
            );

            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            curl_close($ch);

            return $result;

        }

    }

    public static function invoiceGet($ksefNumber){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){

            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = self::$url."/api/online/Invoice/Get/".$ksefNumber;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
            $headers = array(
                'Content-Type: application/octet-stream',
                'SessionToken: '.$sessionToken,
            );

            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            curl_close($ch);

            return $result;

        }

    }



    public static function invoiceGetCommon($ksefNumber, $issuedToIdentifier, $fullName, $nr, $ammount){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){

            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = self::$url."/api/common/Invoice/KSeF";

            $params  = json_encode(array(   

            "ksefReferenceNumber" => $ksefNumber,
            "invoiceDetails" => Array
                (
                    "subjectTo" => Array
                        (
                            "issuedToIdentifier" => $issuedToIdentifier,
                            "issuedToName" => Array
                                (
                                    "type" => "fn",
                                    "fullName" => $fullName
                                )
                        ),

                    "invoiceOryginalNumber" => $nr,
                    "dueValue" => $ammount
                )
            ));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    
            $headers = array(
                'Accept: application/octet-stream',
                'Content-Type: application/json'
            );

            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            \Log::info($issuedToIdentifier);
            curl_close($ch);

            return $result;

        }

    }

    public static function sessionStatus($sessionNumber){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){

            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = self::$url."/api/online/Session/Status/".$sessionNumber;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
            $headers = array(
                'SessionToken: '.$sessionToken,
            );

            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            curl_close($ch);

            return $result;

        }

    }

    public static function sessionTerminate(){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){

            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = self::$url."/api/online/Session/Terminate";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   
            $headers = array(
                'accept: application/json',
                'SessionToken: '.$sessionToken,
            );

            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            curl_close($ch);

        }

    }

    public static function commonStatus($sessionNumber){


        if(!isset($_SESSION)) session_start();

        if(isset($_SESSION["ksef"]["sessionToken"])){

            $sessionToken = $_SESSION["ksef"]["sessionToken"];

            $url = self::$url."/api/common/Status/".$sessionNumber;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
            $headers = array(
                //'accept: application/vnd.v3+json',
                'accept: application/json',
                'Content-Type: application/octet-stream',
                'SessionToken: '.$sessionToken,
            );

            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            //\Log::info($result);
            curl_close($ch);

            return $result;

        }

    }


    public static function invoiceXml($ksefNUmber){

            list($client,$encryptionKey) = self::buildClient();
            $xml = $client->invoices()->download(new DownloadRequest(new KsefNumber($ksefNUmber)))->body();
            return $xml;                                    
    }


    public static function invoiceUpo($referenceNumber, $invoiceReferenceNumber){

            list($client,$encryptionKey) = self::buildClient();     

            $upo = $client->sessions()->invoices()->upo([
                'referenceNumber' => $referenceNumber,
                'invoiceReferenceNumber' => $invoiceReferenceNumber
            ])->body();
            //throw new ValidationException(['my_field'=>$upo]);
            return $upo;
    }


    public static function invoiceHtml($xml, $nr){


            //$xml = file_get_contents("docs/test.xml");
            $xml = Util::removeNamespace($xml);

            $fa = Util::getBetween($xml,'kodSystemowy="','"') ?? "FA (3)";
            $fa = $fa == "FA (3)" ? "3" : "2";

            $xml_doc = new \DOMDocument();
            $xml_doc->loadXml($xml);

            $xsl_doc = new \DOMDocument();
            $xsl_doc->load("docs/invoice".$fa.".xsl");
            
            $proc = new \XSLTProcessor();

            $proc->importStylesheet($xsl_doc);

            $newdom = $proc->transformToXml($xml_doc);

            if($nr != "Offline"){
                $qrcode = self::getQRCode($xml, $nr);

                $img = "<img src=\"data:image/png;base64,".base64_encode($qrcode->code1->raw)."\" style=\"margin-top:5px;width:200px\"/><br><a href=\"".$qrcode->code1->url."\"/>".$qrcode->code1->url."</a>";
                $height = "250";
            }
            else{
                $qrcode = self::getQRCodeOffline($xml);
                $img = "<img src=\"data:image/png;base64,".base64_encode($qrcode->code1->raw)."\" style=\"margin-top:5px;width:200px\"/><br><a href=\"".$qrcode->code1->url."\" target=\"_blank\"/>".$qrcode->code1->url."</a>";
                $img .= "<img src=\"data:image/png;base64,".base64_encode($qrcode->code2->raw)."\" style=\"margin-top:5px;width:200px\"/><br><a href=\"".$qrcode->code2->url."\" target=\"_blank\"/>".$qrcode->code2->url."</a>";
                $height = "550";            
            }

            $html = str_replace("{{NrKsef}}",$nr,$newdom);
            $html = str_replace("{{QRCode}}",$img,$html);
            $html = str_replace("{height}",$height,$html);
            return $html;

    }

    public static function generateInvoicePDF($invoice){
        
        return self::generatePDF($invoice, "invoice");

    }

    public static function generateUpoPDF($invoice){

        return self::generatePDF($invoice, "upo");
    }

    public static function generatePDF($invoice, $type = "invoice"){

            $xml =  Util::removeNamespace($invoice->xml);

            $ksefFeInvoiceConverterPath = KsefFeInvoiceConverterPath::from(Utility::basePath('../../../docs/ksef-pdf-generator/dist/cli/index.js'));


            $mode_ksef = SettingKsef::get("mode") ?? "test";
            if($mode_ksef == "production")
                $mode = Mode::Production;
            else if($mode_ksef == "demo")
                $mode = Mode::Demo; 
            else if($mode_ksef == "test")
                $mode = Mode::Test;

            $fa = json_decode(json_encode(simplexml_load_string($xml)),TRUE);

            $generateQRCodesHandler = new GenerateQRCodesHandler(
                qrCodeBuilder: (new QrCodeBuilder())
                    ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
                    ->labelFont(new OpenSans(size: 12)),
                convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
            );

            $dt = new DateTime();
            $dt->setTimestamp(strtotime($fa['Fa']['P_1']));

            $qrCodes = $generateQRCodesHandler->handle(new GenerateQRCodesAction(
                mode: $mode,
                nip: new NIP($fa['Podmiot1']['DaneIdentyfikacyjne']['NIP']),
                invoiceCreatedAt: $dt,
                document: $xml,
                ksefNumber: new ksefNumber($invoice->ksefNumber)
            ));

            $pdfs = (new GeneratePDFHandler())->handle(new GeneratePDFAction(
                ksefFeInvoiceConverterPath: $ksefFeInvoiceConverterPath,    
                invoiceDocument: $xml,
                upoDocument: $invoice->upo,
                qrCodes: $qrCodes,
                ksefNumber: new ksefNumber($invoice->ksefNumber)
            ));

            if($type == "invoice")
                return $pdfs->invoice;

            if($type == "upo")
                return $pdfs->upo;

    }

    public static function generateOfflinePdf($invoice){

        return self::generatePDF2codes($invoice, "offline");

    }

    public static function generateConfirmationPdf($invoice){

        return self::generatePDF2codes($invoice, "confirmation");

    }

    public static function generatePDF2codes($invoice, $type = "offline"){

            $xml =  Util::removeNamespace($invoice->xml);

            $ksefFeInvoiceConverterPath = KsefFeInvoiceConverterPath::from(Utility::basePath('../../../docs/ksef-pdf-generator/dist/cli/index.js'));

            $certpath = "";
            if(SettingKsef::instance()->cert_link_p12)
                $certpath = SettingKsef::instance()->cert_link_p12->getLocalPath() ?? '';
            else
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.ksef.valid_cert_link")]);
            $certpass = SettingKsef::get("pass_link") ?? '';
            $certnr = SettingKsef::get("nr_link") ?? '';

            try {
                $certificateSerialNumber = CertificateSerialNumber::from($certnr);
                $certificate = CertificateFactory::makeFromCertificatePath(
                    CertificatePath::from($certpath , $certpass)
                );
            }
            catch(\Exception $e){
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.ksef.certlink_error")]);
            }

            $mode_ksef = SettingKsef::get("mode") ?? "test";
            if($mode_ksef == "production")
                $mode = Mode::Production;
            else if($mode_ksef == "demo")
                $mode = Mode::Demo; 
            else if($mode_ksef == "test")
                $mode = Mode::Test;

            $fa = json_decode(json_encode(simplexml_load_string($xml)),TRUE);

            $generateQRCodesHandler = new GenerateQRCodesHandler(
                qrCodeBuilder: (new QrCodeBuilder())
                    ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
                    ->labelFont(new OpenSans(size: 12)),
                convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
            );

            $dt = new DateTime();
            $dt->setTimestamp(strtotime($fa['Fa']['P_1']));

            $nip = $fa['Podmiot1']['DaneIdentyfikacyjne']['NIP'];
            $contextIdentifierGroup = ContextIdentifierGroup::fromIdentifier(NIP::from($nip));

            $qrCodes = $generateQRCodesHandler->handle(new GenerateQRCodesAction(
                mode: $mode,
                nip: new NIP($nip),
                invoiceCreatedAt: $dt,
                document: $xml,
                certificate: $certificate,
                certificateSerialNumber: $certificateSerialNumber,
                contextIdentifierGroup: $contextIdentifierGroup
            ));

            if($type == "offline"){
                $pdfs = (new GeneratePDFHandler())->handle(new GeneratePDFAction(
                    ksefFeInvoiceConverterPath: $ksefFeInvoiceConverterPath,    
                    invoiceDocument: $xml,
                    qrCodes: $qrCodes,
                ));
            }
            if($type == "confirmation"){

                $pdfs = (new GeneratePDFHandler())->handle(new GeneratePDFAction(
                    ksefFeInvoiceConverterPath: $ksefFeInvoiceConverterPath,    
                    confirmationDocument: $xml,
                    qrCodes: $qrCodes,
                ));

            }

            return $pdfs->invoice;

    }


    public static function getQRCode($xml, $nr){
 
            $xml = Util::removeNamespace($xml);
            $simplexml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($simplexml);
            $fa = json_decode($json, true);
   
            if($fa){
                $generateQRCodesHandler = new GenerateQRCodesHandler(
                    qrCodeBuilder: (new QrCodeBuilder())
                        ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
                        ->labelFont(new OpenSans(size: 12)),
                    convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
                );

                $dt = new DateTime();
                $dt->setTimestamp(strtotime($fa['Fa']['P_1']));

                $mode_ksef = SettingKsef::get("mode") ?? "test";
                if($mode_ksef == "production")
                    $mode = Mode::Production;
                else if($mode_ksef == "demo")
                    $mode = Mode::Demo; 
                else if($mode_ksef == "test")
                    $mode = Mode::Test;
      
                $qrCodes = $generateQRCodesHandler->handle(new GenerateQRCodesAction(
                    mode: $mode,
                    nip: new NIP($fa['Podmiot1']['DaneIdentyfikacyjne']['NIP']),
                    invoiceCreatedAt: $dt,
                    document: $xml,
                    ksefNumber: new KsefNumber($nr)
                ));

                return $qrCodes;
            }            
    }

    public static function getQRCodeOffline($xml){

            $xml = Util::removeNamespace($xml);
            $simplexml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($simplexml);
            $fa = json_decode($json, true);

    
            $nip = $nip ??  str_replace("-","", SettingKsef::get("nip"));
            $type = SettingKsef::get("type") ?? 'cert';

            $certpath = "";
            if(SettingKsef::instance()->cert_link_p12)
                $certpath = SettingKsef::instance()->cert_link_p12->getLocalPath() ?? '';
            else
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.ksef.valid_cert_link")]);
            $certpass = SettingKsef::get("pass_link") ?? '';
            $certnr = SettingKsef::get("nr_link") ?? '';
            
            try {
                $certificateSerialNumber = CertificateSerialNumber::from($certnr);
                $certificate = CertificateFactory::makeFromCertificatePath(
                    CertificatePath::from($certpath , $certpass)
                );
            }
            catch(\Exception $e){
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.ksef.certlink_error")]);
            }

            $generateQRCodesHandler = new GenerateQRCodesHandler(
                qrCodeBuilder: (new QrCodeBuilder())
                    ->roundBlockSizeMode(RoundBlockSizeMode::Enlarge)
                    ->labelFont(new OpenSans(size: 12)),
                convertEcdsaDerToRawHandler: new ConvertEcdsaDerToRawHandler()
            );

            $contextIdentifierGroup = ContextIdentifierGroup::fromIdentifier(NIP::from($nip));

            $dt = new DateTime();
            $dt->setTimestamp(strtotime($fa['Fa']['P_1']));

            $mode_ksef = SettingKsef::get("mode") ?? "test";
            if($mode_ksef == "production")
                $mode = Mode::Production;
            else if($mode_ksef == "demo")
                $mode = Mode::Demo; 
            else if($mode_ksef == "test")
                $mode = Mode::Test;

            $qrCodes = $generateQRCodesHandler->handle(new GenerateQRCodesAction(
                mode: $mode,
                nip: new NIP($fa['Podmiot1']['DaneIdentyfikacyjne']['NIP']),
                invoiceCreatedAt: $dt,
                document: $xml,
                certificate: $certificate,
                certificateSerialNumber: $certificateSerialNumber,
                contextIdentifierGroup: $contextIdentifierGroup
            ));  

            return $qrCodes;        
    }

    public static function invoiceMyHtml($xml, $nr){

            $fa = Util::getBetween($xml,'kodSystemowy="','"') ?? "FA (3)";
            $fa = $fa == "FA (3)" ? "3" : "2";

            $xml_doc = new \DOMDocument();
            $xml_doc->loadXml($xml);

            $xsl_doc = new \DOMDocument();
            $xsl_doc->load("docs/invoicemy.xsl");

            
            $proc = new \XSLTProcessor();

            $proc->importStylesheet($xsl_doc);

            $newdom = $proc->transformToXml($xml_doc);
       
            $html = str_replace("{{NrKsef}}",$nr,$newdom);
            $html = str_replace("{{QRCode}}","",$html);

            return $html;

    }

    public static function orderHtml($xml, $nr){


            $xml_doc = new \DOMDocument();
            $xml_doc->loadXml($xml);

            $xsl_doc = new \DOMDocument();
            $xsl_doc->load("docs/order.xsl");
            
            $proc = new \XSLTProcessor();

            $proc->importStylesheet($xsl_doc);

            $newdom = $proc->transformToXml($xml_doc);
       
            $html = str_replace("{{NrKsef}}",$nr,$newdom);

            return $html;

    }

    public static function orderHtmlPro($xml, $nr){


            $xml_doc = new \DOMDocument();
            $xml_doc->loadXml($xml);

            $xsl_doc = new \DOMDocument();
            $xsl_doc->load("docs/proforma.xsl");
            
            $proc = new \XSLTProcessor();

            $proc->importStylesheet($xsl_doc);

            $html = $proc->transformToXml($xml_doc);
       
            return $html;

    }


    public static function invoiceToUpo($xml){


            $xml_doc = new \DOMDocument();
            $xml_doc->loadXml($xml);

            $xsl_doc = new \DOMDocument();
            $xsl_doc->load("docs/upo.xsl");
            
            $proc = new \XSLTProcessor();

            $proc->importStylesheet($xsl_doc);
            $newdom = $proc->transformToXml($xml_doc);

            return $newdom;

    }


    public static function xml($model = "Invoice")
    {


            $faktura = new \DOMDocument('1.0', 'UTF-8');
            $faktura->preservWhiteSpace = true;
            $faktura->formatOutput = true; 

            $root = $faktura ->appendChild($faktura ->createElement("Faktura"));
            $attr1 = $faktura->createAttribute('xmlns:etd');
            $attr1->value = "http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2022/01/05/eD/DefinicjeTypy/";
            $root->appendChild($attr1);
            $attr2 = $faktura->createAttribute('xmlns:xsi');
            $attr2->value = "http://www.w3.org/2001/XMLSchema-instance";
            $root->appendChild($attr2);
            $attr3 = $faktura->createAttribute('xmlns');
            $attr3->value = "http://crd.gov.pl/wzor/2025/06/25/13775/";
            $root->appendChild($attr3);

            $naglowek = $root->appendChild($faktura ->createElement("Naglowek"));

            $KodFormularza = $naglowek->appendChild($faktura ->createElement("KodFormularza","FA"));
            $kodSystemowy = $faktura->createAttribute('kodSystemowy');
            $kodSystemowy->value = "FA (3)";
            $KodFormularza->appendChild($kodSystemowy);
            $wersjaSchemy = $faktura->createAttribute('wersjaSchemy');
            $wersjaSchemy->value = "1-0E";
            $KodFormularza->appendChild($wersjaSchemy);

            $naglowek->appendChild($faktura->createElement("WariantFormularza","3"));
            $naglowek->appendChild($faktura->createElement("DataWytworzeniaFa",date("Y-m-d",strtotime(trim(Input::get($model.".create_at"))))."T".date("H:i:s",strtotime(trim(Input::get($model.".create_at"))))."Z"));
            $naglowek->appendChild($faktura->createElement("SystemInfo","Emode"));

            $podmiot1 = $root->appendChild($faktura->createElement("Podmiot1"));
            $dane = $podmiot1->appendChild($faktura->createElement("DaneIdentyfikacyjne"));
            $dane->appendChild($faktura->createElement("NIP",str_replace("-","",trim(Input::get($model.".seller_nip")))));
            $dane->appendChild($faktura->createElement("Nazwa",htmlspecialchars(trim(Input::get($model.".seller_name")))));
            $adres = $podmiot1->appendChild($faktura->createElement("Adres"));
            $adres->appendChild($faktura->createElement("KodKraju",htmlspecialchars(trim(Input::get($model.".seller_country")))));
            $adres->appendChild($faktura->createElement("AdresL1",htmlspecialchars(trim(Input::get($model.".seller_adres1")))));
            $adres->appendChild($faktura->createElement("AdresL2",htmlspecialchars(trim(Input::get($model.".seller_adres2")))));


            if(Input::filled($model.".seller_email") || Input::filled($model.".seller_phone")){
                $danekontaktowe = $podmiot1->appendChild($faktura->createElement("DaneKontaktowe"));
                if(Input::filled($model.".seller_email")) $danekontaktowe->appendChild($faktura->createElement("Email",htmlspecialchars(trim(Input::get($model.".seller_email")))));
                if(Input::filled($model.".seller_phone")) $danekontaktowe->appendChild($faktura->createElement("Telefon",htmlspecialchars(trim(Input::get($model.".seller_phone")))));
            }

            $podmiot2 = $root->appendChild($faktura ->createElement("Podmiot2"));
            $dane = $podmiot2->appendChild($faktura ->createElement("DaneIdentyfikacyjne"));
            if(Input::get($model.".buyer_type") == 0)           
                $dane->appendChild($faktura->createElement("NIP",str_replace("-","",trim(Input::get($model.".buyer_nip")))));
            if(Input::get($model.".buyer_type") == 1){      
                $vatue = str_replace("-","",trim(Input::get($model.".buyer_nip"))); 
                $dane->appendChild($faktura->createElement("KodUE",substr($vatue,0,2)));
                $dane->appendChild($faktura->createElement("NrVatUE",substr($vatue,2,strlen($vatue)-2)));
            }
            if(Input::get($model.".buyer_type") == 2)           
                $dane->appendChild($faktura->createElement("NrID",htmlspecialchars(trim(Input::get($model.".buyer_nip")))));
            if(Input::get($model.".buyer_type") == 3)           
                $dane->appendChild($faktura->createElement("BrakID","1"));


            $dane->appendChild($faktura->createElement("Nazwa",htmlspecialchars(trim(Input::get($model.".buyer_name")))));
            $adres = $podmiot2->appendChild($faktura->createElement("Adres"));
            $adres->appendChild($faktura->createElement("KodKraju",htmlspecialchars(trim(Input::get($model.".buyer_country")))));
            $adres->appendChild($faktura->createElement("AdresL1",htmlspecialchars(trim(Input::get($model.".buyer_adres1")))));
            $adres->appendChild($faktura->createElement("AdresL2",htmlspecialchars(trim(Input::get($model.".buyer_adres2")))));

            if(Input::filled($model.".buyer_email") || Input::filled($model.".buyer_phone")){
                $danekontaktowe = $podmiot2->appendChild($faktura->createElement("DaneKontaktowe"));
                if(Input::filled($model.".buyer_email")) $danekontaktowe->appendChild($faktura->createElement("Email",htmlspecialchars(trim(Input::get($model.".buyer_email")))));
                if(Input::filled($model.".buyer_phone")) $danekontaktowe->appendChild($faktura->createElement("Telefon",htmlspecialchars(trim(Input::get($model.".buyer_phone")))));
            }

            $podmiot2->appendChild($faktura->createElement("JST","2"));
            $podmiot2->appendChild($faktura->createElement("GV","2"));

            if(Input::get($model."._addbuyer")){

                $podmiot3 = $root->appendChild($faktura ->createElement("Podmiot3"));
                $dane = $podmiot3->appendChild($faktura ->createElement("DaneIdentyfikacyjne"));
                if(Input::get($model."._addbuyer_type") == 0)           
                    $dane->appendChild($faktura->createElement("NIP",str_replace("-","",trim(Input::get($model."._addbuyer_nip")))));
                if(Input::get($model."._addbuyer_type") == 1){      
                    $vatue = str_replace("-","",trim(Input::get($model."._addbuyer_nip"))); 
                    $dane->appendChild($faktura->createElement("KodUE",substr($vatue,0,2)));
                    $dane->appendChild($faktura->createElement("NrVatUE",substr($vatue,2,strlen($vatue)-2)));
                }
                if(Input::get($model."._addbuyer_type") == 2)           
                    $dane->appendChild($faktura->createElement("NrID",htmlspecialchars(trim(Input::get($model."._addbuyer_nip")))));
                if(Input::get($model."._addbuyer_type") == 3)           
                    $dane->appendChild($faktura->createElement("BrakID","1"));


                $dane->appendChild($faktura->createElement("Nazwa",htmlspecialchars(trim(Input::get($model."._addbuyer_name")))));
                $adres = $podmiot3->appendChild($faktura->createElement("Adres"));
                $adres->appendChild($faktura->createElement("KodKraju",htmlspecialchars(trim(Input::get($model."._addbuyer_country")))));
                $adres->appendChild($faktura->createElement("AdresL1",htmlspecialchars(trim(Input::get($model."._addbuyer_adres1")))));
                $adres->appendChild($faktura->createElement("AdresL2",htmlspecialchars(trim(Input::get($model."._addbuyer_adres2")))));

                if(Input::filled($model."._addbuyer_email") || Input::filled($model."._addbuyer_phone")){
                    $danekontaktowe = $podmiot3->appendChild($faktura->createElement("DaneKontaktowe"));
                    if(Input::filled($model."._addbuyer_email")) $danekontaktowe->appendChild($faktura->createElement("Email",htmlspecialchars(trim(Input::get($model."._addbuyer_email")))));
                    if(Input::filled($model."._addbuyer_phone")) $danekontaktowe->appendChild($faktura->createElement("Telefon",htmlspecialchars(trim(Input::get($model."._addbuyer_phone")))));
                }


                if(Input::get($model."._addbuyer_role") != 10)
                    $podmiot3->appendChild($faktura ->createElement("Rola",Input::get($model."._addbuyer_role") + 1));
                else{
                    $podmiot3->appendChild($faktura ->createElement("RolaInna","1"));
                    $podmiot3->appendChild($faktura ->createElement("OpisRoli",htmlspecialchars(trim(Input::get($model."._addbuyer_role_desc")))));

                }

            }


            $fa = $root->appendChild($faktura ->createElement("Fa"));
            $fa->appendChild($faktura->createElement("KodWaluty",trim(Input::get("currency"))));
            $fa->appendChild($faktura->createElement("P_1",date("Y-m-d",strtotime(trim(Input::get($model.".create_at"))))));
            if(Input::filled($model.".place"))
            $fa->appendChild($faktura->createElement("P_1M",htmlspecialchars(trim(Input::get($model.".place")))));
            $fa->appendChild($faktura->createElement("P_2",htmlspecialchars(trim(Input::get($model.".nr")))));

            if(Input::filled($model."._wz")){
                $wza = explode("\n",trim(Input::get($model."._wz")));
                foreach($wza as $wz)
                $fa->appendChild($faktura->createElement("WZ",$wz));
            }

            $fa->appendChild($faktura->createElement("P_6",date("Y-m-d",strtotime(trim(Input::has($model.".make_at") ? Input::get($model.".make_at") : Input::get($model.".create_at"))))));

            $price = self::vatPrice();

            if(isset($price["netto"]["23"])) $fa->appendChild($faktura->createElement("P_13_1", number_format($price["netto"]["23"],2,".","")));
            if(isset($price["vat"]["23"])) $fa->appendChild($faktura->createElement("P_14_1", number_format($price["vat"]["23"],2,".","")));
            if(isset($price["vat"]["23"]) && Input::get("currency") != "PLN") 
            $fa->appendChild($faktura->createElement("P_14_1W", number_format(round($price["vatw"]["23"],2),2,".","")));

            if(isset($price["netto"]["8"])) $fa->appendChild($faktura->createElement("P_13_2", number_format($price["netto"]["8"],2,".","")));
            if(isset($price["vat"]["8"])) $fa->appendChild($faktura->createElement("P_14_2", number_format($price["vat"]["8"],2,".","")));
            if(isset($price["vat"]["8"]) && Input::get("currency") != "PLN") 
            $fa->appendChild($faktura->createElement("P_14_2W", number_format(round($price["vatw"]["8"],2),2,".","")));

            if(isset($price["netto"]["5"])) $fa->appendChild($faktura->createElement("P_13_3", number_format($price["netto"]["5"],2,".","")));
            if(isset($price["vat"]["5"])) $fa->appendChild($faktura->createElement("P_14_3", number_format($price["vat"]["5"],2,".","")));
            if(isset($price["vat"]["5"]) && Input::get("currency") != "PLN") 
            $fa->appendChild($faktura->createElement("P_14_3W", number_format(round($price["vatw"]["5"],2),2,".","")));

            if(isset($price["netto"]["0 KR"])) $fa->appendChild($faktura->createElement("P_13_6_1", number_format($price["netto"]["0 KR"],2,".","")));
            if(isset($price["netto"]["0 WDT"])) $fa->appendChild($faktura->createElement("P_13_6_2", number_format($price["netto"]["0 WDT"],2,".","")));
            if(isset($price["netto"]["0 EX"])) $fa->appendChild($faktura->createElement("P_13_6_3", number_format($price["netto"]["0 EX"],2,".","")));
            if(isset($price["netto"]["zw"])) $fa->appendChild($faktura->createElement("P_13_7", number_format($price["netto"]["zw"],2,".","")));
            if(isset($price["netto"]["np I"])) $fa->appendChild($faktura->createElement("P_13_8", number_format($price["netto"]["np I"],2,".","")));
            if(isset($price["netto"]["np II"])) $fa->appendChild($faktura->createElement("P_13_9", number_format($price["netto"]["np II"],2,".","")));
            if(isset($price["netto"]["oo"])) $fa->appendChild($faktura->createElement("P_13_10", number_format($price["netto"]["oo"],2,".","")));
            if(isset($price["netto"]["ma"])) $fa->appendChild($faktura->createElement("P_13_11", number_format($price["netto"]["ma"],2,".","")));
         
            $fa->appendChild($faktura->createElement("P_15", number_format($price["sumbrutto"],2,".","")));


            $adnotacje = $fa->appendChild($faktura->createElement("Adnotacje"));

            if(Input::get($model."._mk") == 1) $adnotacje->appendChild($faktura->createElement("P_16","1")); else $adnotacje->appendChild($faktura->createElement("P_16","2"));
            if(Input::get($model."._sf") == 1 && Input::get($model.".type") != 1) $adnotacje->appendChild($faktura->createElement("P_17","1")); else $adnotacje->appendChild($faktura->createElement("P_17","2"));
            if(Input::get($model."._oo") == 1) $adnotacje->appendChild($faktura->createElement("P_18","1")); else $adnotacje->appendChild($faktura->createElement("P_18","2"));
            if(Input::get($model."._mpp") == 1) $adnotacje->appendChild($faktura->createElement("P_18A","1")); else $adnotacje->appendChild($faktura->createElement("P_18A","2"));


            $zwolnienie = $adnotacje->appendChild($faktura->createElement("Zwolnienie"));
            if(Input::get($model."._zw") == "")
                $zwolnienie->appendChild($faktura->createElement("P_19N","1"));
            else if(Input::get($model."._zw") == 0){
                $zwolnienie->appendChild($faktura->createElement("P_19","1"));
                $zwolnienie->appendChild($faktura->createElement("P_19A",htmlspecialchars(trim(Input::get($model."._zw_desc")))));
            }
            else if(Input::get($model."._zw") == 1){
                $zwolnienie->appendChild($faktura->createElement("P_19","1"));
                $zwolnienie->appendChild($faktura->createElement("P_19B",htmlspecialchars(trim(Input::get($model."._zw_desc")))));
            }
            else if(Input::get($model."._zw") == 2){
                $zwolnienie->appendChild($faktura->createElement("P_19","1"));
                $zwolnienie->appendChild($faktura->createElement("P_19C",htmlspecialchars(trim(Input::get($model."._zw_desc")))));
            }

            $nowesrodki = $adnotacje->appendChild($faktura->createElement("NoweSrodkiTransportu"));
            $nowesrodki->appendChild($faktura->createElement("P_22N","1"));

            if(Input::get($model."._ptu") == 1) $adnotacje->appendChild($faktura->createElement("P_23","1")); else $adnotacje->appendChild($faktura->createElement("P_23","2"));

            $marza = $adnotacje->appendChild($faktura->createElement("PMarzy"));
            if(Input::get($model."._marza") == "")
                $marza->appendChild($faktura->createElement("P_PMarzyN","1"));
            else if(Input::get($model."._marza") == 0){
                $marza->appendChild($faktura->createElement("P_PMarzy","1"));
                $marza->appendChild($faktura->createElement("P_PMarzy_3_1","1"));
            }
            else if(Input::get($model."._marza") == 1){
                $marza->appendChild($faktura->createElement("P_PMarzy","1"));
                $marza->appendChild($faktura->createElement("P_PMarzy_3_2","1"));
            }
            else if(Input::get($model."._marza") == 2){
                $marza->appendChild($faktura->createElement("P_PMarzy","1"));
                $marza->appendChild($faktura->createElement("P_PMarzy_2","1"));
            }
            else if(Input::get($model."._marza") == 3){
                $marza->appendChild($faktura->createElement("P_PMarzy","1"));
                $marza->appendChild($faktura->createElement("P_PMarzy_3_3","1"));
            }

            if(Input::get($model.".type") == 0)
            $fa->appendChild($faktura->createElement("RodzajFaktury","VAT"));
            if(Input::get($model.".type") == 1)
            $fa->appendChild($faktura->createElement("RodzajFaktury","KOR"));
            if(Input::get($model.".type") == 2)
            $fa->appendChild($faktura->createElement("RodzajFaktury","ZAL"));
            if(Input::get($model.".type") == 3)
            $fa->appendChild($faktura->createElement("RodzajFaktury","ROZ"));
            if(Input::get($model.".type") == 4)
            $fa->appendChild($faktura->createElement("RodzajFaktury","UPR"));
            if(Input::get($model.".type") == 5)
            $fa->appendChild($faktura->createElement("RodzajFaktury","KOR_ZAL"));
            if(Input::get($model.".type") == 6)
            $fa->appendChild($faktura->createElement("RodzajFaktury","KOR_ROZ"));


            if(Input::get($model.".type") == 1 || Input::get($model.".type") == 5 || Input::get($model.".type") == 6) {

                if(strlen(trim(Input::get($model."._kor_reason"))) > 0) $fa->appendChild($faktura->createElement("PrzyczynaKorekty",trim(Input::get($model."._kor_reason"))));
                if(strlen(trim(Input::get($model."._kor_type"))) > 0) $fa->appendChild($faktura->createElement("TypKorekty",trim(Input::get($model."._kor_type"))));

                $kor = $fa->appendChild($faktura->createElement("DaneFaKorygowanej"));
                $kor->appendChild($faktura->createElement("DataWystFaKorygowanej",date("Y-m-d",strtotime(trim(Input::get($model.".kor_data"))))));
                $kor->appendChild($faktura->createElement("NrFaKorygowanej",trim(Input::get($model.".kor_nr"))));
                $kor->appendChild($faktura->createElement("NrKSeF","1"));
                $kor->appendChild($faktura->createElement("NrKSeFFaKorygowanej",trim(Input::get($model.".kor_nrksef"))));
                if((Input::get($model.".type") == 1 || Input::get($model.".type") == 5 || Input::get($model.".type") == 6) && trim(Input::get($model.".kor_amount")) != "")
                $fa->appendChild($faktura->createElement("P_15ZK", trim(Input::get($model.".kor_amount"))));
            }

      
            if(Input::get($model."._fp") == 1) $fa->appendChild($faktura->createElement("FP","1"));
            if(Input::get($model."._tp") == 1) $fa->appendChild($faktura->createElement("TP","1"));
     
            if(Input::get($model.".type") == 2){
                 $dodatkowy = $fa->appendChild($faktura->createElement("DodatkowyOpis"));
                 $dodatkowy->appendChild($faktura->createElement("Klucz","Kwota zaliczki"));
                 $dodatkowy->appendChild($faktura->createElement("Wartosc", number_format($price["sumbrutto"],2,","," ")." ".trim(Input::get($model.".currency"))));
            }

            if(Input::get($model.".type") == 3){
                 $dodatkowy = $fa->appendChild($faktura->createElement("DodatkowyOpis"));
                 $dodatkowy->appendChild($faktura->createElement("Klucz","Kwota pozostała do zapłaty"));
                 $dodatkowy->appendChild($faktura->createElement("Wartosc", number_format($price["sumbrutto"],2,","," ")." ".trim(Input::get($model.".currency"))));
            }

            if(Input::filled($model."._add_key") && Input::filled($model."._add_value")){
                $dodatkowy = $fa->appendChild($faktura->createElement("DodatkowyOpis"));
                $dodatkowy->appendChild($faktura->createElement("Klucz",trim(Input::get($model."._add_key"))));
                $dodatkowy->appendChild($faktura->createElement("Wartosc",trim(Input::get($model."._add_value"))));
            }

            if(Input::filled($model."._uwagi")){
                 $dodatkowy = $fa->appendChild($faktura->createElement("DodatkowyOpis"));
                 $dodatkowy->appendChild($faktura->createElement("Klucz","Uwagi"));
                 $dodatkowy->appendChild($faktura->createElement("Wartosc", trim(Input::get($model."._uwagi"))));
            }

            if(Input::get($model.".type") == 3 && Input::filled($model."._advance_nr") && Input::filled($model."._advance_nr")){
                $dodatkowy = $fa->appendChild($faktura->createElement("FakturaZaliczkowa"));
                $dodatkowy->appendChild($faktura->createElement("NrKSeFFaZaliczkowej",trim(Input::get($model."._advance_nr"))));
            }

            if(Input::get($model."._za") == 1) $fa->appendChild($faktura->createElement("ZwrotAkcyzy","1"));


            $length = sizeof(Input::get('Values.edit_product'));
        
            $l = 1;
            for($i = 0; $i < $length - 1;$i++){

                $product = htmlspecialchars(trim(Input::get('Values.edit_product.'.$i)));
                if(strlen($product) <= 0 || strlen($product) > 255)
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_product")]);

                $pkwiu = htmlspecialchars(trim(Input::get('Values.edit_pkwiu.'.$i)));
                if(strlen($pkwiu) > 255)
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_pkwiu")]);

                $cn = htmlspecialchars(trim(Input::get('Values.edit_cn.'.$i)));
                if(strlen($cn ) > 255)
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_cn")]);

                $gtu = htmlspecialchars(trim(Input::get('Values.edit_gtu.'.$i)));
                if(strlen($gtu) > 255)
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_gtu")]);

                $quantity = trim(Input::get('Values.edit_quantity.'.$i));
                if(!Util::isQuantity($quantity) || $quantity > 2147483647)
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_qty")]);

                $measure = htmlspecialchars(trim(Input::get('Values.edit_measure.'.$i)));
                if(strlen($measure) <= 0 || strlen($measure) > 255)
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_measure")]);

                $netto = preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_netto.'.$i));
                if(!Util::isCurrency($netto))
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_currency")]);

                $brutto = preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_brutto.'.$i));
                if(!Util::isCurrency($brutto))
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_currency")]);

                $vat = trim(Input::get('Values.edit_vat.'.$i));
                if(!in_array($vat, array_keys(Util::getVat())))
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_vat")]);

                $wiersz = $fa->appendChild($faktura->createElement("FaWiersz"));
                $wiersz->appendChild($faktura->createElement("NrWierszaFa",$l));
                $wiersz->appendChild($faktura->createElement("P_7",$product));

                if(Input::get($model.".type") != 4){
                    if(strlen($pkwiu) > 0) $wiersz->appendChild($faktura->createElement("PKWiU",$pkwiu));
                    if(strlen($cn) > 0) $wiersz->appendChild($faktura->createElement("CN",$cn));
                    $wiersz->appendChild($faktura->createElement("P_8A",$measure));
                    $wiersz->appendChild($faktura->createElement("P_8B",$quantity));
                    if(Input::get("count") == "netto"){
                        $wiersz->appendChild($faktura->createElement("P_9A",number_format(floatval($netto),2,".","")));
                        $wiersz->appendChild($faktura->createElement("P_11",number_format(round(floatval($netto)*$quantity,2),2,".","")));
                    }
                    else{
                        $wiersz->appendChild($faktura->createElement("P_9B",number_format(floatval($brutto),2,".","")));
                        $wiersz->appendChild($faktura->createElement("P_11A",number_format(round(floatval($brutto)*$quantity,2),2,".","")));
                    }

                    //$vat = explode("%", $vat)[0];$vat = is_numeric($vat) ? $vat : substr($vat, 0, 2);
                    if($vat != "ma")$wiersz->appendChild($faktura->createElement("P_12",$vat));
                    if(strlen($gtu) > 0){
                        $gtu = explode("_",str_replace(" ","_",$gtu));
                        if(sizeof($gtu) == 2 && $gtu[0] == "GTU"){              
                            $gtu = $gtu[0]."_".sprintf('%02d', $gtu[1]);
                            $wiersz->appendChild($faktura->createElement("GTU",$gtu));
                        }
                    }
                    $exchange = preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_exchange.'.$i));
                    if(Input::get("currency") != "PLN") $wiersz->appendChild($faktura->createElement("KursWaluty",$exchange));
                }


                if(Input::get('Values.edit_kor.'.$i) == "before") $wiersz->appendChild($faktura->createElement("StanPrzed","1"));

                if(Input::get('Values.edit_kor.'.$i) == "after" || Input::get('Values.edit_kor.'.$i) == null)
                $l++;

            }

            if(Input::filled($model."._charge_amount") || Input::filled($model."._deduct_amount")){

                $sumbrutto = number_format($price["sumbrutto"],2,".","");
                $rozliczenie = $fa->appendChild($faktura->createElement("Rozliczenie"));
                $sumobciazenia = 0;
                $sumodliczenia = 0;
                if(Input::filled($model."._charge_amount")){
                    $obciazenia = $rozliczenie->appendChild($faktura->createElement("Obciazenia"));
                    $obciazenia->appendChild($faktura->createElement("Kwota",Input::get($model."._charge_amount")));
                    if(Input::filled($model."._charge_reason"))
                    $obciazenia->appendChild($faktura->createElement("Powod",Input::get($model."._charge_reason")));
                    $rozliczenie->appendChild($faktura->createElement("SumaObciazen",Input::get($model."._charge_amount")));
                    $sumobciazenia = number_format(Input::get($model."._charge_amount"),2,".","");
                }
                if(Input::filled($model."._deduct_amount")){
                    $odliczenia = $rozliczenie->appendChild($faktura->createElement("Odliczenia"));
                    $odliczenia->appendChild($faktura->createElement("Kwota",Input::get($model."._deduct_amount")));
                    if(Input::filled($model."._deduct_reason"))
                    $odliczenia->appendChild($faktura->createElement("Powod",Input::get($model."._deduct_reason")));
                    $rozliczenie->appendChild($faktura->createElement("SumaOdliczen",Input::get($model."._deduct_amount")));
                    $sumodliczenia = number_format(Input::get($model."._deduct_amount"),2,".","");
                }

                $dozaplaty = $sumbrutto + $sumobciazenia - $sumodliczenia;
                $rozliczenie->appendChild($faktura->createElement("DoZaplaty",$dozaplaty));
            }
            
            
            if(Input::filled($model."._pay_type") || Input::filled($model."._pay_termin") || Input::filled($model."._pay_other_desc") || Input::filled($model."._pay_info") || Input::filled($model."._pay_part") || Input::filled($model."._pay_date") || Input::filled($model."._bank_nr") || Input::filled($model."._bank") || Input::filled($model."._swift") || Input::filled($model."._skonto") || Input::filled($model."._skonto_cond") || Input::filled($model."._pay_link") || Input::filled($model."._pay_id"))
            $platnosc = $fa->appendChild($faktura->createElement("Platnosc"));

            if(Input::get($model."._pay_info") == 0) {
                $platnosc->appendChild($faktura->createElement("Zaplacono","1"));
                if(Input::filled($model."._pay_date")) $platnosc->appendChild($faktura->createElement("DataZaplaty",date("Y-m-d",strtotime(trim(Input::get($model."._pay_date"))))));
            }

            if(Input::get($model."._pay_info") == 1) {
                $platnosc->appendChild($faktura->createElement("ZnacznikZaplatyCzesciowej","1"));
                $czesciowa = $platnosc->appendChild($faktura->createElement("ZaplataCzesciowa"));
                if(Input::filled($model."._pay_part")) $czesciowa->appendChild($faktura->createElement("KwotaZaplatyCzesciowej",preg_replace(["/,/i","/\s/i"], [".", ""], Input::get($model."._pay_part"))));
                if(Input::filled($model."._pay_date")) $czesciowa->appendChild($faktura->createElement("DataZaplatyCzesciowej",date("Y-m-d",strtotime(trim(Input::get($model."._pay_date"))))));
            }

            if(Input::filled($model."._pay_termin") || Input::filled($model."._pay_termin_ilosc")) {
                $termin = $platnosc->appendChild($faktura->createElement("TerminPlatnosci"));
                if(Input::filled($model."._pay_termin"))
                $termin->appendChild($faktura->createElement("Termin",date("Y-m-d",strtotime(trim(Input::get($model."._pay_termin"))))));
                if(Input::filled($model."._pay_termin_ilosc")){
                    $terminopis = $termin->appendChild($faktura->createElement("TerminOpis"));
                    $terminopis->appendChild($faktura->createElement("Ilosc",trim(Input::get($model."._pay_termin_ilosc"))));
                    $terminopis->appendChild($faktura->createElement("Jednostka",trim(Input::get($model."._pay_termin_jednostka"))));
                    $terminopis->appendChild($faktura->createElement("ZdarzeniePoczatkowe",trim(Input::get($model."._pay_termin_zdarzeniepoczatkowe"))));
                }
            }

            if(Input::get($model."._pay_type") >=0 && Input::get($model."._pay_type") <= 6)
                $platnosc->appendChild($faktura->createElement("FormaPlatnosci",Input::get($model."._pay_type")+1));

            if(Input::get($model."._pay_type") == 7){
                $platnosc->appendChild($faktura->createElement("PlatnoscInna","1"));
                if(Input::filled($model."._pay_other_desc")) $platnosc->appendChild($faktura->createElement("OpisPlatnosci",htmlspecialchars(trim(Input::get($model."._pay_other_desc")))));
            }


            if(Input::filled($model."._bank_nr") || Input::filled($model."._bank") || Input::filled($model."._swift")){
                $bank = $platnosc->appendChild($faktura->createElement("RachunekBankowy"));
                if(Input::filled($model."._bank_nr")) $bank->appendChild($faktura->createElement("NrRB",htmlspecialchars(trim(Input::get($model."._bank_nr")))));
                if(Input::filled($model."._swift")) $bank->appendChild($faktura->createElement("SWIFT",htmlspecialchars(trim(Input::get($model."._swift")))));
                if(Input::filled($model."._bank")) $bank->appendChild($faktura->createElement("NazwaBanku",htmlspecialchars(trim(Input::get($model."._bank")))));
            }
            if(Input::filled($model."._skonto")){
                $skonto = $platnosc->appendChild($faktura->createElement("Skonto"));
                $skonto->appendChild($faktura->createElement("WarunkiSkonta",htmlspecialchars(trim(Input::get($model."._skonto_cond")))));
                $skonto->appendChild($faktura->createElement("WysokoscSkonta",htmlspecialchars(trim(Input::get($model."._skonto")))));
            }

            if(Input::filled($model."._pay_link"))
                $platnosc->appendChild($faktura->createElement("LinkDoPlatnosci",Input::get($model."._pay_link")));

            if(Input::filled($model."._pay_id"))
                $platnosc->appendChild($faktura->createElement("IPKSeF",Input::get($model."._pay_id")));
            
            if(!$platnosc->hasChildNodes()) $platnosc->remove();

            if(Input::filled($model."._umo_nr") || Input::filled($model."._umo_date") || Input::filled($model."._zam_nr") || Input::filled($model."._zam_date") || Input::filled($model."._wu") || Input::filled($model."._ku") || Input::filled($model."._wdt") || Input::filled($model."._pp"))
            $warunki = $fa->appendChild($faktura->createElement("WarunkiTransakcji"));
            if(Input::filled($model."._umo_nr")|| Input::filled($model."._umo_date"))
            $umowy = $warunki->appendChild($faktura->createElement("Umowy"));
            if(Input::filled($model."._umo_date"))  $umowy->appendChild($faktura->createElement("DataUmowy",date("Y-m-d",strtotime(trim(Input::get($model."._umo_date"))))));
            if(Input::filled($model."._umo_nr"))    $umowy->appendChild($faktura->createElement("NrUmowy",htmlspecialchars(trim(Input::get($model."._umo_nr")))));
            if(Input::filled($model."._zam_nr") || Input::filled($model."._zam_date"))
            $zamowienia = $warunki->appendChild($faktura->createElement("Zamowienia"));
            if(Input::filled($model."._zam_date"))  $zamowienia->appendChild($faktura->createElement("DataZamowienia",date("Y-m-d",strtotime(trim(Input::get($model."._zam_date"))))));
            if(Input::filled($model."._zam_nr"))    $zamowienia->appendChild($faktura->createElement("NrZamowienia",htmlspecialchars(trim(Input::get($model."._zam_nr")))));
            if(Input::filled($model."._wdt")) $warunki->appendChild($faktura->createElement("WarunkiDostawy",htmlspecialchars(trim(Input::get($model."._wdt")))));
            if(Input::filled($model."._ku")) $warunki->appendChild($faktura->createElement("KursUmowny",trim(Input::get($model."._ku"))));
            if(Input::filled($model."._wu")) $warunki->appendChild($faktura->createElement("WalutaUmowna",trim(Input::get($model."._wu"))));
            if(Input::get($model."._pp") == 1) $warunki->appendChild($faktura->createElement("PodmiotPosredniczacy","1"));

            if(!$warunki->hasChildNodes()) $warunki->remove();

            if(Input::filled($model."._stopka")){
                $stopka = $root->appendChild($faktura->createElement("Stopka"));
                $info = $stopka->appendChild($faktura->createElement("Informacje"));
                $info->appendChild($faktura->createElement("StopkaFaktury",htmlspecialchars(trim(Input::get($model."._stopka")))));
            }

            if(Input::filled($model."._full_name") || Input::filled($model."._krs")  || Input::filled($model."._regon") || Input::filled($model."._bdo")){
                if(!isset($stopka)) $stopka = $root->appendChild($faktura->createElement("Stopka"));
                $rejestry = $stopka->appendChild($faktura->createElement("Rejestry"));
                if(Input::filled($model."._full_name")) $rejestry->appendChild($faktura->createElement("PelnaNazwa",htmlspecialchars(trim(Input::get($model."._full_name")))));
                if(Input::filled($model."._krs")) $rejestry->appendChild($faktura->createElement("KRS",htmlspecialchars(trim(Input::get($model."._krs")))));
                if(Input::filled($model."._regon")) $rejestry->appendChild($faktura->createElement("REGON",htmlspecialchars(trim(Input::get($model."._regon")))));
                if(Input::filled($model."._bdo")) $rejestry->appendChild($faktura->createElement("BDO",htmlspecialchars(trim(Input::get($model."._bdo")))));
            }


            return $faktura->saveXml();


    }

    public static function verifyXml($xml)
    {
        $xml = rtrim($xml);
        $dom = new \DOMDocument(); 
        $dom->loadXML($xml); 

        if (!$dom->schemaValidate('docs/schema_v3.xsd')) {
            print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
            libxml_display_errors();
        }
        else 
            return $xml;

    }

    public static function vatPrice()
    {

        $price = [];
        $price["netto"] = [];
        $price["vat"] = [];
        $price["vatw"] = [];
        $price["brutto"] = [];
        $price["sumbrutto"] = 0;

        $length = sizeof(Input::get('Values.edit_product'));
        
        for($i = 0; $i < $length - 1;$i++){

            if(Input::get("count") == "netto")
            $netto = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_netto.'.$i)));
            else
            $brutto = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_brutto.'.$i)));
    
            $extra = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_extra.'.$i)));
            $exchange = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_exchange.'.$i)));

            $vat_base = trim(Input::get('Values.edit_vat.'.$i));
            $quantity = trim(Input::get('Values.edit_quantity.'.$i));

            $vat_stawka = $vat_base;$vat_stawka = is_numeric($vat_stawka) ? $vat_stawka : 0;

            if(Input::get("count") == "netto"){
                $vatw = round($exchange*$netto*$quantity*$vat_stawka/100,2);
                $netto = round($netto*$quantity,2);
                $brutto = round($netto*((100+$vat_stawka)/100),2);
            }
            else{
                $vatw = round($exchange*$brutto*$quantity*$vat_stawka/(100+$vat_stawka),2);
                $brutto = round($brutto*$quantity,2);
                $netto = round($brutto/((100+$vat_stawka)/100),2);
            }

            if(Input::get("Invoice.type") == 2 || Input::get("Invoice.type") == 3 || Input::get("Invoice.type") == 5 || Input::get("Invoice.type") == 6){
                $brutto = round($extra,2);
                $netto = round($brutto/((100+$vat_stawka)/100),2);
            }
         
            $vat = round($brutto - $netto,2);


            if(!isset($price["netto"][$vat_base])) $price["netto"][$vat_base] = 0;
            if(!isset($price["vat"][$vat_base])) $price["vat"][$vat_base] = 0;
            if(!isset($price["vatw"][$vat_base])) $price["vatw"][$vat_base] = 0;
            if(!isset($price["brutto"][$vat_base])) $price["brutto"][$vat_base] = 0;

            if(Input::get('Values.edit_kor.'.$i) == "before"){
                $netto = -$netto;
                $vat = -$vat;
                $vatw = -$vatw;
                $brutto = -$brutto;
            }

            $price["netto"][$vat_base] += $netto;
            $price["vat"][$vat_base] += $vat;
            $price["vatw"][$vat_base] += $vatw;
            $price["brutto"][$vat_base] += $brutto;
            $price["sumbrutto"] += $brutto;



        }
     
        return $price;
    }


    public static function stat($fa)
    {

        $ip = $_SERVER['REMOTE_ADDR'];
        $json = file_get_contents("http://ip-api.com/json/$ip");
        $location = json_decode($json, true);
        $data = [];
        $data["time"] = date("Y-m-d H:i:s");
        $data["useragent"] = $_SERVER['HTTP_USER_AGENT'];
        $data["location"] = $location;

        if(Session::has("ksef.mode") && BackendAuth::getUser() && BackendAuth::getUser()->login === 'ksef'){    
                $file = Session::get("ksef.mode").".log";
                $log = file_exists($file) ? file_get_contents($file).PHP_EOL : '';
                file_put_contents($file,$log.json_encode($data));

                $file = Session::get("ksef.mode")."_full.log";
                $data["fa"] = json_encode($fa);
                $log = file_exists($file) ? file_get_contents($file).PHP_EOL : '';
                file_put_contents($file,$log.json_encode($data));
        }


    }
    
}