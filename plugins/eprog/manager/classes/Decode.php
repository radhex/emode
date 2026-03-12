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
use Mail;
use Eprog\Manager\Models\Category;
use Eprog\Manager\Models\SettingStatus;
use Flash;

class Decode
{

    public static function local_secret() {
        return hex2bin('3735653161393637356239626234616239316333366336633865316563333563');
    }

    public static function key_domain(string $domain): string {
        return hash('sha256', self::local_secret() . '|' . strtolower($domain), true);
    }

    public static function key_code(string $remoteKey): string {
        return hash('sha256', self::local_secret() . '|' . $remoteKey, true);
    }

    public static function api_url(string $code): string {
        static $url;
        if ($url) return $url;

        if (empty($_SERVER['SERVER_NAME'])) die('Brak domeny');

        $bin = base64_decode($code, true);
        if (!$bin || strlen($bin) < 17) die('Nieprawidłowy API payload');

        $iv  = substr($bin, 0, 16);
        $enc = substr($bin, 16);

        $url = openssl_decrypt(
            $enc,
            'AES-256-CBC',
            self::key_domain($_SERVER['SERVER_NAME']),
            OPENSSL_RAW_DATA,
            $iv
        );

        if (!$url){
            session_start();
            $_SESSION['error'] = 'B&#322;&#261;d deszyfrowania API url';
            header("Location: /error.php");
            exit;
        }
        return $url;
    }

    public static function fetch_license(string $domain, string $code)
    {    
        $fn  = "\x66\x69\x6c\x65" . "_" . "\x67\x65\x74" . "_" . "\x63\x6f\x6e\x74\x65\x6e\x74\x73";
        $h   = substr(hash("\x73\x68\x61\x31", $domain), 0, 10);

        $t   = time() ^ crc32($domain);
        $hbq = "\x68\x74\x74\x70\x5f\x62\x75\x69\x6c\x64\x5f\x71\x75\x65\x72\x79";

        $q   = $hbq(["\x68\x6f\x73\x74"=>$domain,"\x68"=>$h,"\x74"=>$t]);
        $scc = "\x73\x74\x72\x65\x61\x6d\x5f\x63\x6f\x6e\x74\x65\x78\x74\x5f\x63\x72\x65\x61\x74\x65";
        $ctx = $scc(["\x68\x74\x74\x70"=>["\x74\x69\x6d\x65\x6f\x75\x74"=>3]]);
 
        $j   = json_decode(@$fn(self::api_url($code)."\x3f".$q, false, $ctx), true);
/*
        if (!is_array($j)){
            session_start();
            $_SESSION['error'] = 'Licencja nieprawid&#322;owa';
            header("Location: /error.php");
            exit;
        }
*/
        return $j;
    }

    public static function lic_expired($lic) {

        $isoDate = $lic['expires_at'] ?? ''; 

        $date = new DateTime($isoDate);

        $now = new DateTime('now', new DateTimeZone('UTC'));

        if ($date < $now){
            session_start();
            $_SESSION['error'] = 'Licencja nieaktualna';
            header("Location: /error.php");
            exit;
        }
           
    }


}

