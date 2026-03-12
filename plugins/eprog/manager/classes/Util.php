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
use Redirect;
use DateTimeZone;
use Mail;
use Eprog\Manager\Models\Category;
use Eprog\Manager\Models\SettingStatus;
use Illuminate\Support\Facades\DB;
use Flash;

class Util
{

    public static function mode($type)
    {
        $mode = (array) Session::get('license.mode', []);

        foreach ($mode as $m) {
            if (str_starts_with($m, (string)$type)) {
                return true;
            }
        }

        return false;
    }

    public static function checkExpired() {

        if(Session::has("license.subscription") && Session::get("license.subscription") > 1) {
            if(Session::has("license.expires_at") && Carbon::parse(Session::get("license.expires_at"))->lt(Carbon::now('UTC')))
            return Redirect::to(config('cms.backendUri')."/eprog/manager/info/expired")->send();
        }

    }

    public static function checkCapacity() {

        if(Session::has("license.subscription") && Session::get("license.subscription") > 1) {
            $parts1 = 0;  $parts2 = 0;  $parts3 = 0; $database = 0;

            $folder = "storage"; $output = null;$return_var = null;
            exec("du -sm " . escapeshellarg($folder), $output, $return_var);
            if ($return_var === 0) {
                $parts1 = preg_split("/\s+/", $output[0])[0] ?? 0;
            }

            $folder = "../backup"; $output = null; $return_var = null;
            exec("du -sm " . escapeshellarg($folder), $output, $return_var);
            if ($return_var === 0) {
                $parts2 = preg_split("/\s+/", $output[0])[0] ?? 0;
            }  

            $folder = "../logs"; $output = null; $return_var = null;
            exec("du -sm " . escapeshellarg($folder), $output, $return_var);
            if ($return_var === 0) {
                $parts3 = preg_split("/\s+/", $output[0])[0] ?? 0;
            }  

            $size = DB::select("SELECT table_schema AS database_name,ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = '".env("DB_DATABASE")."' GROUP BY table_schema");
            $database = $size[0]->size ?? 0;

            $all = $parts1 + $parts2 + $parts3 + $database;
            if($all  > 0)  Session::put("capacity",$all);
            
            if(Session::has("capacity") && Session::has("license.capacity") && Session::get("capacity") > Session::get("license.capacity"))
            return Redirect::to(config('cms.backendUri')."/eprog/manager/info/capacity")->send();
        }
        
    }

    public static function checkBearer()
    {

        if(empty(Session::get("bearer"))){
            $bearer = self::getBearer();
            Session::put("bearer",$bearer);
        }
        else
            $bearer = Session::get("bearer") ?? "";

        return $bearer;

    }


    public static function getBearer()
    {

        $email = env("API_EMAIL");
        $password = env("API_PASSWORD");
        $url = env("API_URL")."/login?email=".$email."&password=".$password;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,1);
        $result = curl_exec($ch);
        $result = json_decode($result, true) ?? [];
        curl_close($ch);

        return $result["token"] ?? "";

    }

    public static function getApi($method, $term = "")
    {

        $bearer = self::checkBearer();

        $email = env("API_EMAIL");
        $url = env("API_URL")."/".$method."?email=".$email;
        if($term) $url .= "&term=".urlencode($term);
        $authorization = "Authorization: Bearer ".$bearer;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization ));

        $result = curl_exec($ch);
        $result = json_decode($result, true) ?? [];

        curl_close($ch);

        return $result[$method] ?? [];

    }


    public static function dd($input){

        file_put_contents("dd",json_encode($input));
        die();
    }

    public static function date($input){

        if($input == NULL || $input == "") return "";   
            $output = new Backend($input);    
            return $output->dateTime($input);
   
        
    }

    public static function dateFormat($input, $format = 'Y-m-d H:i'){

        if($input == NULL || $input == "") return "";   
        $date = Carbon::createFromTimestamp(strtotime($input));
        return gmdate($date->format($format)); 
   
        
    }

    public static function dateLocale($input, $format = 'Y-m-d H:i:s'){

        if($input == NULL || $input == "") return "";
        $timezone = UserPreference::forUser()->get('backend::backend.preferences')['timezone'] ?? Config::get("app.timezone");
        return static::dateLocaleBase($input, $format, $timezone);
        
    }


    public static function dateLocaleClient($input, $format = 'Y-m-d H:i:s'){

        if($input == NULL || $input == "") return "";
        $timezone =  Auth::getUser()->timezone;
        if($timezone == null) $timezone = Config::get("app.timezone");
        return static::dateLocaleBase($input, $format, $timezone);
        
    }

    public static function dateLocaleBase($input, $format = 'Y-m-d H:i:s', $timezone = ''){

        if($input == NULL || $input == "") return "";   
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $input, Config::get("app.timezone"));

        $date->setTimezone($timezone);
        return gmdate($date->format($format));  
        //return $date->formatLocalized("%Y-%m-%d %H:%M"); 
        
    }


    public static function timeZoneOffset(){

   
        $utcTime = new DateTime('now', new DateTimeZone('UTC'));
        $currentTimezone = new DateTimeZone(Config::get("app.timezone"));
        $offset = $currentTimezone->getOffset($utcTime);
        //return " UTC".($offset > 0 ? "+":"").($offset/3600-1);
        return "";

    }


    public static function dateCalendar($input){
        
        if($input == NULL || $input == "") return "";
        $timezone = UserPreference::forUser()->get('backend::backend.preferences')['timezone'];
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $input, $timezone);
        $date->setTimezone(config('app.timezone'));
        return $date->format('Y-m-d H:i:s');  
        //return $date->formatLocalized("%Y-%m-%d %H:%M"); 
        
    }


    public static function getUserGroups(){

        $user = BackendAuth::getUser();
        $groups = ["superuser" => 0 , "admin"=> 0,"manager"=> 0,"worker"=> 0];
        
        if(!$user) return false;
        if($user->is_superuser) $groups['superuser']= 1;

        foreach($user->getGroups() as $group){
                
            if($group->code == "admin") $groups['admin']= 1;
            if($group->code == "manager") $groups['manager']= 1;
            if($group->code == "worker") $groups['worker']= 1;

        }

        return $groups;
    }

    public static function isGroup($user,$rule){

    
        foreach($user->getGroups() as $group){
                
            if($group->code == $rule) return true;

        }

        return false;
    }

    public static function currency($input){

            $input = str_replace(",",".",$input);
            $input = str_replace(" ","",$input);
            
        
            return    number_format((float)$input, 2, ',', ' ');
    
  
    }

    public static function zero_first($numer) {

        if($numer < 10)
            return sprintf("0%d", $numer);
        else
            return $numer;
    }

    public static function invdate($date, $short = false)
    {

        $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $date);
        $carbon->setTimezone(Session::get("locale.zone"));
        if(strtotime($date) > 0) {
            if($short == 1)
                return $carbon->format("Y-m-d");//date('Y-m-d', strtotime($date));
            else if($short == 2)
                return $carbon->format("H:i");//date('Y-m-d', strtotime($date));
            else if($short == 3)
                return $carbon->format("m");//date('Y-m-d', strtotime($date));
            else if($short == 4)
                return $carbon->format("Y");//date('Y-m-d', strtotime($date));
            else if($short == 5)
                return $carbon->format("m/Y");//date('Y-m-d', strtotime($date));
            else
                return $carbon->format("Y-m-d H:i");;//date('Y-m-d H:i', strtotime($date));
        }

    }


    public static function kwota($string){

        $string = round(str_replace(",",".",$string),2);
        $tmp = explode(".",$string);
        if(isset($tmp[1])) {
            if ($tmp[1] == "00")
                return number_format($tmp[0], 0, ',', ' ');
            else
                return number_format($string, 2, ',', ' ');
        }
        else
            return number_format($tmp[0], 0, ',', ' ');

        //return number_format($string, 2, ',', ' ');
    }

    public static function dkwota($string){

        $string = round(str_replace(",",".",str_replace(" ","",$string)),2);
        return number_format($string, 2, ',', ' ');
    }

    public static function rate($string){

        return number_format($string, 1, ',', '');
        
    }


    public static function scan_dir($dir) {


        if(file_exists($dir)) {
                $ignored = array('.', '..', '.svn', '.htaccess');

                $files = array();    
                foreach (scandir($dir) as $file) {
                    if (in_array($file, $ignored)) continue;
                    $files[$file] = filemtime($dir . '/' . $file);
                }

                arsort($files);
                $files = array_keys($files);

                return ($files) ? $files : false;
        }
    }



    public static function categoryPath($id){


       $model = Category::find($id);
        return $model->name ?? '';

    }



    public static function isXML($xml){

        libxml_use_internal_errors(true);

        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->loadXML($xml);

        $errors = libxml_get_errors();

        if(empty($errors)){
            return "";
        }

        $error = $errors[0];
        if($error->level < 3){
            return "";
        }

        $explodedxml = explode("r", $xml);
        $badxml = $explodedxml[($error->line)-1];

        $message = $error->message;
        return $message;

    }


    public static function is_int($var) {

        $tmp = (int) $var;
        if($tmp == $var)
           return true;
        else
           return false;

    }

    public static function is_date($value) {

        if (!$value) {
            return false;
        } else {
            $date = date_parse($value);
            if($date['error_count'] == 0 && $date['warning_count'] == 0){
                return checkdate($date['month'], $date['day'], $date['year']);
            } else {
                return false;
            }
        }
    }


    public static function isQuantity($value) {

        /*
        if(self::is_int($value) && $value >= 0)
           return true;
        else
           return false;
        */

        return is_numeric($value);   

    }

    public static function  isCurrency($value){


        return preg_match('/^[0-9\.]+$/', $value) && $value >= 0;
            
    } 


    public static function getBetween($content,$start,$end){

        $r = explode($start, $content);
        if (isset($r[1])){
          $r = explode($end, $r[1]);
          return $r[0];
        }
        return '';

    }

    public static function filesize($path)
    {
        return self::formatsize(filesize($path));
    }

    public static function formatsize($size)
    {
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1000)) : 0;
        $formatsize = $size > 0 ? number_format($size / pow(1024, $power), 2, '.', ' ') : 0;

        return $size > 0 ? $formatsize. ' ' . $units[$power] : "";
    }

    public static function driveIcon($mimeType)
    {

        $color = "#222";
        $icon = "icon-file-o";
        if(preg_match("/pdf/",$mimeType)) { $icon = "icon-file-pdf-o"; $color = "#c63434";}
        if(preg_match("/excel|\.sheet|\.spreadsheet/",$mimeType)) { $icon = "icon-file-excel-o"; $color = "#37850c";}
        if(preg_match("/\.document/",$mimeType)) { $icon = "icon-file-word-o"; $color = "#2673b4";}
        if(preg_match("/text\/xml|json/",$mimeType)) { $icon = "icon-file-code-o"; $color = "#957316";}
        if(preg_match("/image|\.drawing/",$mimeType)) { $icon = "icon-file-image-o"; $color = "#a0139e";}
        if(preg_match("/\.presentation/",$mimeType)) { $icon = "icon-file-powerpoint-o";  $color = "brown";}
        if(preg_match("/audio/",$mimeType)) { $icon = "icon-file-audio-o"; }
        if(preg_match("/video/",$mimeType)) { $icon = "icon-file-video-o"; }
        if(preg_match("/text\/plain/",$mimeType)) { $icon = "icon-file-text-o"; }
        if(preg_match("/zip/",$mimeType)) {$icon = "icon-file-archive-o"; }


        return ["icon" => $icon, "color" => $color];

    }

    public static function br2nl($str) {

        $str = preg_replace("/(\r\n|\n|\r)/", "", $str);
        return preg_replace("=&lt;br */?&gt;=i", "\n", $str);

    } 

    public static function getVat($date = null) {

        $vat = [config("tax.vat.23") => config("tax.vat.23").'%', config("tax.vat.8") => config("tax.vat.8").'%', config("tax.vat.5") => config("tax.vat.5").'%','0 KR' => '0% - krajowe','0 WDT' => '0% - WDT','0 EX' => '0% - export','zw' => 'zw','oo' => 'oo','np I' => 'np I','np II' => 'np II','ma' => 'marża'];
        if($date && $date >= config("tax.date.vat1.from") && $date <= config("tax.date.vat1.to"))
        $vat = [config("tax.vat1.23") => config("tax.vat1.23").'%', config("tax.vat1.8") => config("tax.vat1.8").'%', config("tax.vat1.5") => config("tax.vat1.5").'%','0 KR' => '0% - krajowe','0 WDT' => '0% - WDT','0 EX' => '0% - export','zw' => 'zw','oo' => 'oo','np I' => 'np I','np II' => 'np II','ma' => 'marża'];

        return $vat;

    }

    public static function getVatRate($rate, $date = null) {

        if($date && $date >= config("tax.date.vat1.from") && $date <= config("tax.date.vat1.to")) return config("tax.vat1.".$rate);
        return config("tax.vat.".$rate);

    }


    public static function getCurrencies() {

        return ["PLN"=> "PLN","EUR"=> "EUR","USD"=> "USD","AED"=> "AED","AFN"=> "AFN","ALL"=> "ALL","AMD"=> "AMD","ANG"=> "ANG","AOA"=> "AOA","ARS"=> "ARS","AUD"=> "AUD","AWG"=> "AWG","AZN"=> "AZN","BAM"=> "BAM","BBD"=> "BBD","BDT"=> "BDT","BGN"=> "BGN","BHD"=> "BHD","BIF"=> "BIF","BMD"=> "BMD","BND"=> "BND","BOB"=> "BOB","BOV"=> "BOV","BRL"=> "BRL","BSD"=> "BSD","BTN"=> "BTN","BWP"=> "BWP","BYN"=> "BYN","BZD"=> "BZD","CAD"=> "CAD","CDF"=> "CDF","CHE"=> "CHE","CHF"=> "CHF","CHW"=> "CHW","CLF"=> "CLF","CLP"=> "CLP","CNY"=> "CNY","COP"=> "COP","COU"=> "COU","CRC"=> "CRC","CUC"=> "CUC","CUP"=> "CUP","CVE"=> "CVE","CZK"=> "CZK","DJF"=> "DJF","DKK"=> "DKK","DOP"=> "DOP","DZD"=> "DZD","EGP"=> "EGP","ERN"=> "ERN","ETB"=> "ETB","FJD"=> "FJD","FKP"=> "FKP","GBP"=> "GBP","GEL"=> "GEL","GGP"=> "GGP","GHS"=> "GHS","GIP"=> "GIP","GMD"=> "GMD","GNF"=> "GNF","GTQ"=> "GTQ","GYD"=> "GYD","HKD"=> "HKD","HNL"=> "HNL","HRK"=> "HRK","HTG"=> "HTG","HUF"=> "HUF","IDR"=> "IDR","ILS"=> "ILS","IMP"=> "IMP","INR"=> "INR","IQD"=> "IQD","IRR"=> "IRR","ISK"=> "ISK","JEP"=> "JEP","JMD"=> "JMD","JOD"=> "JOD","JPY"=> "JPY","KES"=> "KES","KGS"=> "KGS","KHR"=> "KHR","KMF"=> "KMF","KPW"=> "KPW","KRW"=> "KRW","KWD"=> "KWD","KYD"=> "KYD","KZT"=> "KZT","LAK"=> "LAK","LBP"=> "LBP","LKR"=> "LKR","LRD"=> "LRD","LSL"=> "LSL","LYD"=> "LYD","MAD"=> "MAD","MDL"=> "MDL","MGA"=> "MGA","MKD"=> "MKD","MMK"=> "MMK","MNT"=> "MNT","MOP"=> "MOP","MRU"=> "MRU","MUR"=> "MUR","MVR"=> "MVR","MWK"=> "MWK","MXN"=> "MXN","MXV"=> "MXV","MYR"=> "MYR","MZN"=> "MZN","NAD"=> "NAD","NGN"=> "NGN","NIO"=> "NIO","NOK"=> "NOK","NPR"=> "NPR","NZD"=> "NZD","OMR"=> "OMR","PAB"=> "PAB","PEN"=> "PEN","PGK"=> "PGK","PHP"=> "PHP","PKR"=> "PKR","PLN"=> "PLN","PYG"=> "PYG","QAR"=> "QAR","RON"=> "RON","RSD"=> "RSD","RUB"=> "RUB","RWF"=> "RWF","SAR"=> "SAR","SBD"=> "SBD","SCR"=> "SCR","SDG"=> "SDG","SEK"=> "SEK","SGD"=> "SGD","SHP"=> "SHP","SLL"=> "SLL","SOS"=> "SOS","SRD"=> "SRD","SSP"=> "SSP","STN"=> "STN","SVC"=> "SVC","SYP"=> "SYP","SZL"=> "SZL","THB"=> "THB","TJS"=> "TJS","TMT"=> "TMT","TND"=> "TND","TOP"=> "TOP","TRY"=> "TRY","TTD"=> "TTD","TWD"=> "TWD","TZS"=> "TZS","UAH"=> "UAH","UGX"=> "UGX","USN"=> "USN","UYI"=> "UYI","UYU"=> "UYU","UYW"=> "UYW","UZS"=> "UZS","VES"=> "VES","VND"=> "VND","VUV"=> "VUV","WST"=> "WST","XAF"=> "XAF","XAG"=> "XAG","XAU"=> "XAU","XBA"=> "XBA","XBB"=> "XBB","XBC"=> "XBC","XBD"=> "XBD","XCD"=> "XCD","XDR"=> "XDR","XOF"=> "XOF","XPD"=> "XPD","XPF"=> "XPF","XPT"=> "XPT","XSU"=> "XSU","XUA"=> "XUA","XXX"=> "XXX","YER"=> "YER","ZAR"=> "ZAR","ZMW"=> "ZMW","ZWL"=> "ZWL"];

    }


    public static function getRegions()    {

    
        $regions = [

            'dolnośląskie' => 'dolnośląskie',
            'kujawsko-pomorskie' => 'kujawsko-pomorskie',
            'lubelskie' => 'lubelskie',
            'lubuskie' => 'lubuskie',
            'łódzkie' => 'łódzkie',
            'małopolskie' => 'małopolskie',
            'mazowieckie' => 'mazowieckie',
            'opolskie' => 'opolskie',
            'podkarpackie' => 'podkarpackie',
            'podlaskie' => 'podlaskie',
            'pomorskie' => 'pomorskie',
            'śląskie' => 'śląskie',
            'świętokrzyskie' => 'świętokrzyskie',
            'warmińsko-mazurskie' => 'warmińsko-mazurskie',
            'wielkopolskie' => 'wielkopolskie',
            'zachodniopomorskie' => 'zachodniopomorskie',
        ];

        return  $regions;

    }

    public static function getTermin() {


        $termin = [

            'dni' => 'dni',
            'miesięcy' => 'm-cy'

        ];

        return  $termin;

    }


    public static function getCountries() {

        $countries = [];    
        $countries["pl"] = ["AF"=> "Afganistan","AL"=> "Albania","DZ"=> "Algieria","AD"=> "Andora","AO"=> "Angola","AI"=> "Anguilla","AQ"=> "Antarktyda","AG"=> "Antigua i Barbuda","SA"=> "Arabia Saudyjska","AR"=> "Argentyna","AM"=> "Armenia","AW"=> "Aruba","AU"=> "Australia","AT"=> "Austria","AZ"=> "Azerbejdżan","BS"=> "Bahamy","BH"=> "Bahrajn","BD"=> "Bangladesz","BB"=> "Barbados","BE"=> "Belgia","BZ"=> "Belize","BJ"=> "Benin","BM"=> "Bermudy","BT"=> "Bhutan","BY"=> "Białoruś","MM"=> "Birma","BO"=> "Boliwia","BQ"=> "Bonaire, Sint Eustatius i Saba","BA"=> "Bośnia i Hercegowina","BW"=> "Botswana","BR"=> "Brazylia","BN"=> "Brunei","IO"=> "Brytyjskie Terytorium Oceanu Indyjskiego","VG"=> "Brytyjskie Wyspy Dziewicze","BG"=> "Bułgaria","BF"=> "Burkina Faso","BI"=> "Burundi","CL"=> "Chile","CN"=> "Chiny","HR"=> "Chorwacja","CW"=> "Curaçao","CY"=> "Cypr","TD"=> "Czad","ME"=> "Czarnogóra","CZ"=> "Czechy","UM"=> "Dalekie Wyspy Mniejsze Stanów Zjednoczonych","DK"=> "Dania","CD"=> "Demokratyczna Republika Konga","DM"=> "Dominika","DO"=> "Dominikana","DJ"=> "Dżibuti","EG"=> "Egipt","EC"=> "Ekwador","ER"=> "Erytrea","EE"=> "Estonia","ET"=> "Etiopia","FK"=> "Falklandy","FJ"=> "Fidżi","PH"=> "Filipiny","FI"=> "Finlandia","FR"=> "Francja","TF"=> "Francuskie Terytoria Południowe i Antarktyczne","GA"=> "Gabon","GM"=> "Gambia","GS"=> "Georgia Południowa i Sandwich Południowy","GH"=> "Ghana","GI"=> "Gibraltar","GR"=> "Grecja","GD"=> "Grenada","GL"=> "Grenlandia","GE"=> "Gruzja","GU"=> "Guam","GG"=> "Guernsey","GF"=> "Gujana Francuska","GY"=> "Gujana","GP"=> "Gwadelupa","GT"=> "Gwatemala","GW"=> "Gwinea Bissau","GQ"=> "Gwinea Równikowa","GN"=> "Gwinea","HT"=> "Haiti","ES"=> "Hiszpania","NL"=> "Holandia","HN"=> "Honduras","HK"=> "Hongkong","IN"=> "Indie","ID"=> "Indonezja","IQ"=> "Irak","IR"=> "Iran","IE"=> "Irlandia","IS"=> "Islandia","IL"=> "Izrael","JM"=> "Jamajka","JP"=> "Japonia","YE"=> "Jemen","JE"=> "Jersey","JO"=> "Jordania","KY"=> "Kajmany","KH"=> "Kambodża","CM"=> "Kamerun","CA"=> "Kanada","QA"=> "Katar","KZ"=> "Kazachstan","KE"=> "Kenia","KG"=> "Kirgistan","KI"=> "Kiribati","CO"=> "Kolumbia","KM"=> "Komory","CG"=> "Kongo","KR"=> "Korea Południowa","KP"=> "Korea Północna","CR"=> "Kostaryka","CU"=> "Kuba","KW"=> "Kuwejt","LA"=> "Laos","LS"=> "Lesotho","LB"=> "Liban","LR"=> "Liberia","LY"=> "Libia","LI"=> "Liechtenstein","LT"=> "Litwa","LU"=> "Luksemburg","LV"=> "Łotwa","MK"=> "Macedonia","MG"=> "Madagaskar","YT"=> "Majotta","MO"=> "Makau","MW"=> "Malawi","MV"=> "Malediwy","MY"=> "Malezja","ML"=> "Mali","MT"=> "Malta","MP"=> "Mariany Północne","MA"=> "Maroko","MQ"=> "Martynika","MR"=> "Mauretania","MU"=> "Mauritius","MX"=> "Meksyk","FM"=> "Mikronezja","MD"=> "Mołdawia","MC"=> "Monako","MN"=> "Mongolia","MS"=> "Montserrat","MZ"=> "Mozambik","NA"=> "Namibia","NR"=> "Nauru","NP"=> "Nepal","DE"=> "Niemcy","NE"=> "Niger","NG"=> "Nigeria","NI"=> "Nikaragua","NU"=> "Niue","NF"=> "Norfolk","NO"=> "Norwegia","NC"=> "Nowa Kaledonia","NZ"=> "Nowa Zelandia","OM"=> "Oman","PK"=> "Pakistan","PW"=> "Palau","PS"=> "Palestyna","PA"=> "Panama","PG"=> "Papua-Nowa Gwinea","PY"=> "Paragwaj","PE"=> "Peru","PN"=> "Pitcairn","PF"=> "Polinezja Francuska","PL"=> "Polska","PR"=> "Portoryko","PT"=> "Portugalia","TW"=> "Tajwan","ZA"=> "Republika Południowej Afryki","CF"=> "Republika Środkowoafrykańska","CV"=> "Republika Zielonego Przylądka","RE"=> "Reunion","RU"=> "Rosja","RO"=> "Rumunia","RW"=> "Rwanda","EH"=> "Sahara Zachodnia","KN"=> "Saint Kitts i Nevis","LC"=> "Saint Lucia","VC"=> "Saint Vincent i Grenadyny","BL"=> "Saint-Barthélemy","MF"=> "Saint-Martin","PM"=> "Saint-Pierre i Miquelon","SV"=> "Salwador","AS"=> "Samoa Amerykańskie","WS"=> "Samoa","SM"=> "San Marino","SN"=> "Senegal","RS"=> "Serbia","SC"=> "Seszele","SL"=> "Sierra Leone","SG"=> "Singapur","SX"=> "Sint Maarten","SK"=> "Słowacja","SI"=> "Słowenia","SO"=> "Somalia","LK"=> "Sri Lanka","US"=> "Stany Zjednoczone","SZ"=> "Suazi","SD"=> "Sudan","SR"=> "Surinam","SJ"=> "Svalbard i Jan Mayen","SY"=> "Syria","CH"=> "Szwajcaria","SE"=> "Szwecja","TJ"=> "Tadżykistan","TH"=> "Tajlandia","TZ"=> "Tanzania","TL"=> "Timor Wschodni","TG"=> "Togo","TK"=> "Tokelau","TO"=> "Tonga","TT"=> "Trynidad i Tobago","TN"=> "Tunezja","TR"=> "Turcja","TM"=> "Turkmenistan","TC"=> "Turks i Caicos","TV"=> "Tuvalu","UG"=> "Uganda","UA"=> "Ukraina","UY"=> "Urugwaj","UZ"=> "Uzbekistan","VU"=> "Vanuatu","WF"=> "Wallis i Futuna","VA"=> "Watykan","VE"=> "Wenezuela","HU"=> "Węgry","GB"=> "Wielka Brytania","VN"=> "Wietnam","IT"=> "Włochy","CI"=> "Wybrzeże Kości Słoniowej","BV"=> "Wyspa Bouveta","CX"=> "Wyspa Bożego Narodzenia","IM"=> "Wyspa Man","SH"=> "Wyspa Świętej Heleny, Wyspa Wniebowstąpienia i Tristan da Cunha","AX"=> "Wyspy Alandzkie","CK"=> "Wyspy Cooka","VI"=> "Wyspy Dziewicze Stanów Zjednoczonych","HM"=> "Wyspy Heard i McDonalda","CC"=> "Wyspy Kokosowe","MH"=> "Wyspy Marshalla","FO"=> "Wyspy Owcze","SB"=> "Wyspy Salomona","ST"=> "Wyspy Świętego Tomasza i Książęca","ZM"=> "Zambia","ZW"=> "Zimbabwe","AE"=> "Zjednoczone Emiraty Arabskie"];
        $countries["en"] = ["AF"=> "Afghanistan","AL"=> "Albania","DZ"=> "Algeria","AD"=> "Andorra","AO"=> "Angola","AI"=> "Anguilla","AQ"=> "Antarctica","AG"=> "Antigua and Barbuda","SA"=> "Saudi Arabia","AR"=> "Argentina","AM"=> "Armenia","AW"=> "Aruba","AU"=> "Australia","AT"=> "Austria","AZ"=> "Azerbaijan","BS"=> "Bahamas","BH"=> "Bahrain","BD"=> "Bangladesh","BB"=> "Barbados","BE"=> "Belgium","BZ"=> "Belize","BJ"=> "Benin","BM"=> "Bermuda","BT"=> "Bhutan","BY"=> "Belarus","MM"=> "Myanmar","BO"=> "Bolivia, Plurinational State of","BQ"=> "Bonaire, Saint Eustatius and Saba","BA"=> "Bosnia and Herzegovina","BW"=> "Botswana","BR"=> "Brazil","BN"=> "Brunei Darussalam","IO"=> "British Indian Ocean Territory","VG"=> "Virgin Islands, British","BG"=> "Bulgaria","BF"=> "Burkina Faso","BI"=> "Burundi","CL"=> "Chile","CN"=> "China","HR"=> "Croatia","CW"=> "Curaçao","CY"=> "Cyprus","TD"=> "Chad","ME"=> "Montenegro","CZ"=> "Czech Republic","UM"=> "United States Minor Outlying Islands","DK"=> "Denmark","CD"=> "Congo, the Democratic Republic of the","DM"=> "Dominica","DO"=> "Dominican Republic","DJ"=> "Djibouti","EG"=> "Egypt","EC"=> "Ecuador","ER"=> "Eritrea","EE"=> "Estonia","ET"=> "Ethiopia","FK"=> "Falkland Islands (Malvinas)","FJ"=> "Fiji","PH"=> "Philippines","FI"=> "Finland","FR"=> "France","TF"=> "French Southern Territories","GA"=> "Gabon","GM"=> "Gambia","GS"=> "South Georgia and the South Sandwich Islands","GH"=> "Ghana","GI"=> "Gibraltar","GR"=> "Greece","GD"=> "Grenada","GL"=> "Greenland","GE"=> "Georgia","GU"=> "Guam","GG"=> "Guernsey","GF"=> "French Guiana","GY"=> "Guyana","GP"=> "Guadeloupe","GT"=> "Guatemala","GW"=> "Guinea-Bissau","GQ"=> "Equatorial Guinea","GN"=> "Guinea","HT"=> "Haiti","ES"=> "Spain","NL"=> "Netherlands","HN"=> "Honduras","HK"=> "Hong Kong","IN"=> "India","ID"=> "Indonesia","IQ"=> "Iraq","IR"=> "Iran, Islamic Republic of","IE"=> "Ireland","IS"=> "Iceland","IL"=> "Israel","JM"=> "Jamaica","JP"=> "Japan","YE"=> "Yemen","JE"=> "Jersey","JO"=> "Jordan","KY"=> "Cayman Islands","KH"=> "Cambodia","CM"=> "Cameroon","CA"=> "Canada","QA"=> "Qatar","KZ"=> "Kazakhstan","KE"=> "Kenya","KG"=> "Kyrgyzstan","KI"=> "Kiribati","CO"=> "Colombia","KM"=> "Comoros","CG"=> "Congo","KR"=> "Korea, Republic of","KP"=> "Korea, Democratic People’s Republic of","CR"=> "Costa Rica","CU"=> "Cuba","KW"=> "Kuwait","LA"=> "Lao People’s Democratic Republic","LS"=> "Lesotho","LB"=> "Lebanon","LR"=> "Liberia","LY"=> "Libyan Arab Jamahiriya","LI"=> "Liechtenstein","LT"=> "Lithuania","LU"=> "Luxembourg","LV"=> "Latvia","MK"=> "Macedonia, the former Yugoslav Republic of","MG"=> "Madagascar","YT"=> "Mayotte","MO"=> "Macao","MW"=> "Malawi","MV"=> "Maldives","MY"=> "Malaysia","ML"=> "Mali","MT"=> "Malta","MP"=> "Northern Mariana Islands","MA"=> "Morocco","MQ"=> "Martinique","MR"=> "Mauritania","MU"=> "Mauritius","MX"=> "Mexico","FM"=> "Micronesia, Federated States of","MD"=> "Moldova, Republic of","MC"=> "Monaco","MN"=> "Mongolia","MS"=> "Montserrat","MZ"=> "Mozambique","NA"=> "Namibia","NR"=> "Nauru","NP"=> "Nepal","DE"=> "Germany","NE"=> "Niger","NG"=> "Nigeria","NI"=> "Nicaragua","NU"=> "Niue","NF"=> "Norfolk Island","NO"=> "Norway","NC"=> "New Caledonia","NZ"=> "New Zealand","OM"=> "Oman","PK"=> "Pakistan","PW"=> "Palau","PS"=> "Palestinian Territory, Occupied","PA"=> "Panama","PG"=> "Papua New Guinea","PY"=> "Paraguay","PE"=> "Peru","PN"=> "Pitcairn","PF"=> "French Polynesia","PL"=> "Poland","PR"=> "Puerto Rico","PT"=> "Portugal","TW"=> "Taiwan","ZA"=> "South Africa","CF"=> "Central African Republic","CV"=> "Cape Verde","RE"=> "Réunion","RU"=> "Russian Federation","RO"=> "Romania","RW"=> "Rwanda","EH"=> "Western Sahara","KN"=> "Saint Kitts and Nevis","LC"=> "Saint Lucia","VC"=> "Saint Vincent and the Grenadines","BL"=> "Saint Barthélemy","MF"=> "Saint Martin (French part)","PM"=> "Saint Pierre and Miquelon","SV"=> "El Salvador","AS"=> "American Samoa","WS"=> "Samoa","SM"=> "San Marino","SN"=> "Senegal","RS"=> "Serbia","SC"=> "Seychelles","SL"=> "Sierra Leone","SG"=> "Singapore","SX"=> "Sint Maarten (Dutch part)","SK"=> "Slovakia","SI"=> "Slovenia","SO"=> "Somalia","LK"=> "Sri Lanka","US"=> "United States","SZ"=> "Swaziland","SD"=> "Sudan","SR"=> "Suriname","SJ"=> "Svalbard and Jan Mayen","SY"=> "Syrian Arab Republic","CH"=> "Switzerland","SE"=> "Sweden","TJ"=> "Tajikistan","TH"=> "Thailand","TZ"=> "Tanzania, United Republic of","TL"=> "Timor-Leste","TG"=> "Togo","TK"=> "Tokelau","TO"=> "Tonga","TT"=> "Trinidad and Tobago","TN"=> "Tunisia","TR"=> "Turkey","TM"=> "Turkmenistan","TC"=> "Turks and Caicos Islands","TV"=> "Tuvalu","UG"=> "Uganda","UA"=> "Ukraine","UY"=> "Uruguay","UZ"=> "Uzbekistan","VU"=> "Vanuatu","WF"=> "Wallis and Futuna","VA"=> "Holy See (Vatican City State)","VE"=> "Venezuela, Bolivarian Republic of","HU"=> "Hungary","GB"=> "United Kingdom","VN"=> "Viet Nam","IT"=> "Italy","CI"=> "Côte d’Ivoire","BV"=> "Bouvet Island","CX"=> "Christmas Island","IM"=> "Isle of Man","SH"=> "Saint Helena, Ascension and Tristan da Cunha","AX"=> "Åland Islands","CK"=> "Cook Islands","VI"=> "Virgin Islands, U.S.","HM"=> "Heard Island and McDonald Islands","CC"=> "Cocos (Keeling) Islands","MH"=> "Marshall Islands","FO"=> "Faroe Islands","SB"=> "Solomon Islands","ST"=> "Sao Tome and Principe","ZM"=> "Zambia","ZW"=> "Zimbabwe","AE"=> "United Arab Emirates"];

        return $countries;

    }

    public static function getNip() {

        return [trans("eprog.manager::lang.ksef.nip"),trans("eprog.manager::lang.ksef.vatue"),trans("eprog.manager::lang.ksef.vat_other"),trans("eprog.manager::lang.ksef.vat_none")];

    }

    public static function getRole() {

        return [trans("eprog.manager::lang.ksef.roles.0"),trans("eprog.manager::lang.ksef.roles.1"),trans("eprog.manager::lang.ksef.roles.2"),trans("eprog.manager::lang.ksef.roles.3"),trans("eprog.manager::lang.ksef.roles.4"),trans("eprog.manager::lang.ksef.roles.5"),trans("eprog.manager::lang.ksef.roles.6"),trans("eprog.manager::lang.ksef.roles.7"),trans("eprog.manager::lang.ksef.roles.8"),trans("eprog.manager::lang.ksef.roles.9"),trans("eprog.manager::lang.ksef.roles.10")];

    }

    public static function getInvoiceType()
    {
        
        return ['Vat' => trans("eprog.manager::lang.ksef.base"), 'Kor' => trans("eprog.manager::lang.ksef.correct"), 'Zal' => trans("eprog.manager::lang.ksef.advance"), 'Roz' => trans("eprog.manager::lang.ksef.settlement"), 'Upr' => trans("eprog.manager::lang.ksef.simple"), 'KorZal' => trans("eprog.manager::lang.ksef.correct_advance"), 'KorRoz' => trans("eprog.manager::lang.ksef.correct_settlement")];

    }


    public static function getInvoiceRules() {


        $rules = [
                'create_at'     => 'required|datetermin:1',
                'make_at'       => 'required|datetermin:2',
                'buyer_name'    => 'required',
                'buyer_nip'     => 'required_unless:buyer_type,3|nip:buyer_type,0|vateu:buyer_type,1|ksef:buyer_type,2',
                'buyer_city'    => 'required',
                'buyer_code'    => 'required',
                'buyer_country' => 'required',
                'buyer_street'  => 'required',
                'buyer_number'  => 'required',
                'buyer_email'   => 'email',
                '_addbuyer_role_desc'    => 'required_if:_addbuyer_role,10',
                '_addbuyer_name'    => 'required_unless:addbuyer,0',
                '_addbuyer_nip'     => 'required_unless:_addbuyer_type,3|nip:_addbuyer_type,0|vateu:_addbuyer_type,1|ksef:_addbuyer_type,2',
                '_addbuyer_city'    => 'required_unless:addbuyer,0',
                '_addbuyer_code'    => 'required_unless:addbuyer,0',
                '_addbuyer_country' => 'required_unless:addbuyer,0',
                '_addbuyer_street'  => 'required_unless:addbuyer,0',
                '_addbuyer_number'  => 'required_unless:addbuyer,0',
                '_addbuyer_email'   => 'email',
                //'buyer_phone'   => 'phone',
                'seller_name'    => 'required',
                'seller_nip'     => 'required|nip',
                'seller_city'    => 'required',
                'seller_code'    => 'required',
                'seller_country' => 'required',
                'seller_street'  => 'required',
                'seller_number'  => 'required',
                'seller_email'   => 'email',
                //'seller_phone'   => 'phone',
                'currency'  => 'required',
                'exchange' => 'required|numeric|gt:0',
                '_pay_part' => 'required_if:_pay_info,1|ksef:_pay_info,1',
                '_pay_date' => 'required_with:_pay_info|datetermin:3',
                '_pay_termin' => 'datetermin:3',
                '_pay_other_desc' => 'required_if:_pay_type,7|max:256',
                '_ku' => 'required_with:_wu|numeric|gt:0',
                '_wu' => 'required_with:_ku',
                '_zw_desc' => 'required_with:_zw',
                '_skonto_cond' => 'required_with:_skonto',
                '_skonto' => 'required_with:_skonto_cond',
                '_umo_date' => 'datetermin:3',
                '_zam_date' => 'datetermin:3',
                '_regon' => 'regon',
                '_krs' => 'krs',
                '_bdo' => 'bdo',
                '_wdt' => 'max:256',
                '_stopka' => 'max:3500',
                '_add_desc' => 'max:256',
                '_bank_nr' => 'required_with:_swift|bank',
                '_swift' => 'swift',

            ];

            return $rules;
    }

    public static function removeNamespace($xml) {

        $xml = preg_replace('/<(\/?)([a-zA-Z0-9_]+):/', '<$1', $xml);

        return $xml;

    }


    public static function escapeXml($xmlString) {
        return preg_replace_callback('/&(?!amp;|lt;|gt;|quot;|apos;)/', function($matches) {
            return '&amp;';
        }, $xmlString);
    }


    public static function getTaxOffice() {

        $data = [];
        $data[''] = e(trans('eprog.manager::lang.select_dropdown'));

        $kody = file_get_contents("docs/KodyUrzedowSkarbowych_v8-0E.xsd");
        $kody = json_decode(json_encode(simplexml_load_string(Util::removeNamespace($kody))),TRUE);
        foreach($kody['simpleType']['restriction']['enumeration'] as $k => $v)
            $data[$v['@attributes']['value']] = $v['annotation']['documentation'];

        return $data;

    }

    public static function getTaxLump()
    {
        
        return ['17' => '17%','15' => '15%','14' => '14%','12.5' => '12,5%','12' => '12%','10' => '10%','8.5' => '8,5%','5.5' => '5,5%','3' => '3%','2' => '2%'];

    }

    public static function getTaxForm()
    {
        
        return ['scale'=> e(trans('eprog.manager::lang.tax_scale')), 'line'=> e(trans('eprog.manager::lang.tax_line')), 'lump'=> e(trans('eprog.manager::lang.tax_lump'))];

    }

    public static function getVatType()
    {
        
        return [1=>"Dostawa towarów oraz świadczenie usług, na terytorium kraju",2=>"Nabycie towarów i usług pozostałych",3=>"Nabycie towarów i usług pozostałych odliczane według struktury sprzedaży",4=>"Nabycie środków trwałych",5=>"Nabycie środków trwałych odliczane według struktury sprzedaży",6=>"Sprzedaż nieudokumentowana",7=>"Wewnątrzwspólnotowa dostawa towarów",8=>"Eksport towarów",9=>"Wewnątrzwspólnotowe nabycie towarów",10=>"Import usług",11=>"Nabycie towarów i usług, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 7 i 8 ustawy",12=>"Nabycie towarów, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 5 ustawy",13=>"Dostawa towarów, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 7 ustawy",14=>"Dostawa usług, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 8 ustawy",15=>"Dostawa towarów oraz świadczenie usług, poza terytorium kraju",16=>"Import towarów, podlegający rozliczeniu zgodnie z art. 33a ustawy",17=>"Świadczenie usług, poza terytorium kraju - art. 100 ust. 1 pkt. 4 ustawy",18=>"Nabycie od podatników podatku od wartości dodanej usług, do których stosuje się art. 28b ustawy",19=>"Dostawa towarów o której mowa w art. 129 ustawy",20=>"Podatek należny od spisu z natury",21=>"Zwrot kwoty odliczonej lub zwróconej wydatkowanej na zakup kasy fiskalnej",22=>"Podatek należny od wewnątrzwspólnotowego nabycia środków transportu",23=>"Podatek należny od wewnątrzwspólnotowego nabycia paliw silnikowych",24=>"Korekta podatku naliczonego od nabycia środków trwałych",25=>"Korekta podatku naliczonego od pozostałych nabyć",26=>"Korekta podatku naliczonego o którym mowa w art. 89b ust. 1 ustawy",27=>"Korekta podatku naliczonego o którym mowa w art. 89b ust. 4 ustawy",28 => "Podatek od niezwróconej kaucji za produkty w opakowaniach na napoje"];

        /*

        1 => Dostawa towarów oraz świadczenie usług, na terytorium kraju
        2 => Nabycie towarów i usług pozostałych
        3 => Nabycie towarów i usług pozostałych odliczane według struktury sprzedaży
        4 => Nabycie środków trwałych
        5 => Nabycie środków trwałych odliczane według struktury sprzedaży
        6 => Sprzedaż nieudokumentowana
        7 => Wewnątrzwspólnotowa dostawa towarów
        8 => Eksport towarów
        9 => Wewnątrzwspólnotowe nabycie towarów
        10 => Import usług
        11 => Nabycie towarów i usług, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 7 i 8 ustawy
        12 => Nabycie towarów, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 5 ustawy
        13 => Dostawa towarów, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 7 ustawy
        14 => Dostawa usług, dla których podatnikiem jest nabywca zgodnie z art. 17 ust. 1 pkt 8 ustawy
        15 => Dostawa towarów oraz świadczenie usług, poza terytorium kraju
        16 => Import towarów, podlegający rozliczeniu zgodnie z art. 33a ustawy
        17 => Świadczenie usług, poza terytorium kraju - art. 100 ust. 1 pkt 4 ustawy
        18 => Nabycie od podatników podatku od wartości dodanej usług, do których stosuje się art. 28b ustawy
        19 => Dostawa towarów, o której mowa w art. 129 ustawy
        20 => Podatek należny od spisu z natury
        21 => Zwrot kwoty odliczonej lub zwróconej wydatkowanej na zakup kasy fiskalnej
        22 => Podatek należny od wewnątrzwspólnotowego nabycia środków transportu
        23 => Podatek należny od wewnątrzwspólnotowego nabycia paliw silnikowych
        24 => Korekta podatku naliczonego od nabycia środków trwałych
        25 => Korekta podatku naliczonego od pozostałych nabyć
        26 => Korekta podatku naliczonego, o którym mowa w art. 89b ust. 1 ustawy
        27 => Korekta podatku naliczonego, o którym mowa w art. 89b ust. 4 ustawy
        28 => Podatek od niezwróconej kaucji za produkty w opakowaniach na napoje
        7697208563
        */
        
    }



}