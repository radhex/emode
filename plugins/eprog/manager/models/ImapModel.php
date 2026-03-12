<?php namespace Eprog\Manager\Models;

use Model;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Input;
use ApplicationException;
use Flash;
use Mail as SendMail;
use Carbon\Carbon;
use Redirect;
use Webklex\IMAP\Facades\Client;


/**
 * Model
 */
class ImapModel extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \Sushi\Sushi;

    
    /*
     * Validation
     */    
    public $rules = [
        'title'    => 'required',
        'body'    => 'required'
    ];

    public $customMessages = [

        'title.required' => 'eprog.manager::lang.valid_title',
        'body.required' => 'eprog.manager::lang.valid_body'
      
    ];
    
    
    /**
     * @var string The database table used by the model.
     */
    

    public $table = 'eprog_manager_imap';

    public $folder;

    public $perPage;

    public function setRecordsPerPage($perPage){

        $this->perPage = $perPage;

    }

    public function getFolder()
    {
     
        $this->folder = '';
        return $this->folder;

    }

    public function beforeFetch()
    {
     
        $this->getCurrent();

    }

    public function getPerPage()
    {
     
        $this->perPage = config("imap.perPage.default");
        return $this->perPage;

    }

    public function getPage()
    {

        return  post('page') ? post('page') : 1;

    }

    public function getCurrent()
    {

        if(Input::has("term"))
            $term = Input::get("term");
        else
            $term = post("listToolbarSearch[term]");
     
        $curr = $this->getPage();


        if(Input::segment(7) > 0)
            $curr = Input::segment(7);
        else if(Input::has("page"))
            $curr = Input::get("page");

        return intval($curr);

    }

    public function getPageFrom()
    {

        $page = $this->getCurrent()*$this->getPerPage() - $this->getPerPage() + 1;

        if($this->getTotal() == 0)
            $page = 0;

        return $page;
    }  

    public function getPageTo()
    {

        if($this->getCurrent() == $this->getLast())
            $page = $this->getTotal();
        else
            $page = $this->getCurrent()*$this->getPerPage();

        if($this->getTotal() == 0)
            $page = 0;

        return $page;
    }    
  

    public function getTotal()
    {

        $client = Client::account('default');
        $client->connect();

        $folder = $client->getFolder($this->getFolder());
        if(!$folder) return 0;

        if(Input::has("term"))
            $term = Input::get("term");
        else
            $term = post("listToolbarSearch[term]");

        if($term != ""){
            if(filter_var($term, FILTER_VALIDATE_EMAIL)){
                if(Input::segment(4) == "sent")
                    return $folder->query()->to($term)->all()->count();
                else
                    return $folder->query()->from($term)->all()->count();
            }
            else
                return $folder->query()->text($term)->all()->count();
            }
        else
            return $folder->query()->all()->count();

    }

    public function getLast()
    {
     
        return  intval(ceil($this->getTotal()/$this->getPerPage()));

    }

    public function getRows()
    {
   
        $client = Client::account('default');

        $folder = $client->getFolder($this->getFolder());
        if(!$folder) return [];

        if(Input::filled("term"))
            $term = trim(Input::get("term"));
        else
            $term = post("listToolbarSearch[term]");

        if(Input::segment(6) > 0) {
            $message[] = $folder->query()->getMessage(Input::segment(6));
            $message[0]->setFlag(['Seen']);
        }
        else {
            if($term != ""){
                if(filter_var($term, FILTER_VALIDATE_EMAIL)){
                    if(Input::segment(4) == "sent")
                        $message = $folder->query()->to($term)->all()->paginate($this->getPerPage(), $this->getCurrent()); 
                    else
                        $message = $folder->query()->from($term)->all()->paginate($this->getPerPage(), $this->getCurrent()); 
                }
                else
                    $message = $folder->query()->text($term)->all()->paginate($this->getPerPage(), $this->getCurrent());        
            }
            else    
                $message = $folder->query()->all()->paginate($this->getPerPage(), $this->getCurrent());
        }

        $data = [];
        foreach($message as $msg){

            $weight = isset($msg->getFlags()->toArray()['seen']) ? 'normal' : 'bold';

            $title = $msg->getSubject()->toArray()[0] ?? '';
            $body = $msg->hasHtmlBody() ? $msg->getHTMLBodyWithEmbeddedBase64Images() : $msg->getTextBody();     
            
            $attchments = $msg->getAttachments();
            $attach = [];        
            foreach($attchments as $attachment)
            $attach[] = ["id" => $attachment->getId(),"name" => $attachment->getName()];      

            $data[] = ['id' => $msg->uid, 'currentPage' => $this->getCurrent(), 'attach' => json_encode($attach) , 'from' => mb_decode_mimeheader($msg->getFrom()), 'to' => mb_decode_mimeheader($msg->getTo()), 'cc' => mb_decode_mimeheader($msg->getCc()),  'title' => self::title_decode($title), 'body' => '', 'weight' => $weight, 'date' => $msg->getDate()];
         
        }

        return $data;

    }


    public function decodeBrokenSubject($subject)
    {
        // usuń łamania linii
        $subject = preg_replace("/\r?\n/", "", $subject);

        // wyciągnij wszystkie fragmenty Base64
        preg_match_all('/\?B\?(.+?)\?=/i', $subject, $m);
        if (!$m[1]) {
            return $subject;
        }

        // sklej Base64
        $b64 = implode('', $m[1]);

        // dopełnij Base64 jeśli trzeba
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        // dekoduj
        $decoded = base64_decode($b64);

        // spróbuj konwersji
        return mb_convert_encoding($decoded, 'UTF-8', ['Windows-1255', 'ISO-8859-8', 'UTF-8']);
    }

    public function title_decode($title)
    {
        $subject = '';
        $elements = imap_mime_header_decode($title);

        foreach ($elements as $element) {
            $charset = $element->charset;
            $text = $element->text;

            if ($charset != 'default') {
                $text = iconv($charset, 'UTF-8//IGNORE', $text);
            }

            $subject .= $text;
        }

        return $subject;

    }

}