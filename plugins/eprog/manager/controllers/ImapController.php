<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Mailing as ModelMailing;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Mail;
use Carbon\carbon;
use Artisan;
use Webklex\IMAP\Facades\Client;
use Eprog\Manager\Models\Inbox as ModelInbox;
use Eprog\Manager\Classes\Ksef;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Eprog\Manager\Classes\Util;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use View;


use Lang;


class ImapController extends Controller
{
    public $implement = ['Extends\Backend\Behaviors\ListController','Extends\Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $folder;

    public $requiredPermissions = ['eprog.manager.access_inbox'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'inbox',  'inbox');
        $this->folder = '';
        
    }

    public function index()
    {
        if(!config('imap.enable'))
        return Redirect::to(config('cms.backendUri'));

        $this->addJs('/plugins/rainlab/user/assets/js/bulk-actions.js');
        $this->asExtension('ListController')->index();

    }

    public function listExtendQuery($query, $definition = null)
    {
      
  		$query->orderBy("id", "desc");	

    }

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
        if(Input::segment(5) == "preview" && is_array(json_decode($form->model->attach, true)) && sizeof(json_decode($form->model->attach, true)) == 0)
        $form->getField('attach')->hidden = true;

        if(Input::segment(5) == "preview" && strlen(trim($form->model->cc)) == 0)
        $form->getField('cc')->hidden = true;

    }

    public function connect()
    {

        $client = Client::account('default');
        $client->connect();
        $folder = $client->getFolder($this->folder);

        return $folder;

    }


    public function index_onBulkAction()
    {


        if (
            ($bulkAction = post('action')) &&
            ($checkedIds = post('checked')) &&
            is_array($checkedIds) &&
            count($checkedIds)
        ) {

            foreach ($checkedIds as $Id) {           
                switch ($bulkAction) {
                    case 'delete':
                        $message = $this->connect()->query()->getMessageByUid($Id)->move(config("imap.folders.trash.action"));
                        break;

                    case 'inbox':
                        $message = $this->connect()->query()->getMessageByUid($Id)->move(config("imap.folders.inbox.action"));
                        break;

                    case 'sent':
                        $message = $this->connect()->query()->getMessageByUid($Id)->move(config("imap.folders.sent.action"));
                        break;

                    case 'draft':
                        $message = $this->connect()->query()->getMessageByUid($Id)->move(config("imap.folders.draft.action"));
                        break;

                    case 'archive':
                        $message = $this->connect()->query()->getMessageByUid($Id)->move(config("imap.folders.archive.action"));
                        break;

                    case 'spam':
                        $message = $this->connect()->query()->getMessageByUid($Id)->move(config("imap.folders.spam.action"));
                        break;

                    case 'seen':
                        $message = $this->connect()->query()->getMessageByUid($Id)->setFlag(['Seen']);
                        break;

                    case 'unseen':
                        $message = $this->connect()->query()->getMessageByUid($Id)->unsetFlag(['Seen']);
                        break;
                }
            }

            Flash::success(Lang::get('eprog.manager::lang.process_success'));
        }
        else {
            Flash::error(Lang::get('eprog.manager::lang.selected_empty'));
        }

        return $this->listRefresh();
    }


    public function preview_onInbox()
    {

        $id = Input::segment(6);

        if($id > 0){

            $message = $this->connect()->query()->getMessageByUid($id)->move(config("imap.folders.inbox.action"));
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to("/".config('cms.backendUri')."/eprog/manager/inbox");

        }
    }


    public function preview_onSent()
    {

        $id = Input::segment(6);

        if($id > 0){

            $message = $this->connect()->query()->getMessageByUid($id)->move(config("imap.folders.sent.action"));
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to("/".config('cms.backendUri')."/eprog/manager/sent");

        }
    }

    public function preview_onDraft()
    {

        $id = Input::segment(6);

        if($id > 0){

            $message = $this->connect()->query()->getMessageByUid($id)->move(config("imap.folders.draft.action"));
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to("/".config('cms.backendUri')."/eprog/manager/draft");

        }
    }

    public function preview_onArchive()
    {

        $id = Input::segment(6);

        if($id > 0){

            $message = $this->connect()->query()->getMessageByUid($id)->move(config("imap.folders.archive.action"));
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to("/".config('cms.backendUri')."/eprog/manager/archive");

        }
    }

    public function preview_onSpam()
    {

        $id = Input::segment(6);

        if($id > 0){

            $message = $this->connect()->query()->getMessageByUid($id)->move(config("imap.folders.spam.action"));
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to("/".config('cms.backendUri')."/eprog/manager/spam");

        }
    }

    public function preview_onTrash()
    {

        $id = Input::segment(6);

        if($id > 0){

            $message = $this->connect()->query()->getMessageByUid($id)->move(config("imap.folders.trash.action"));
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to("/".config('cms.backendUri')."/eprog/manager/trash");

        }
    }

    public function onPdf()
    {
        $file = self::onPdfGenerate(Input::segment(6),post('folder'));
        if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onPdfGenerate($id, $folder)
    {
        View::addLocation('plugins/eprog/manager/controllers/printer');
        $inbox = ModelInbox::find($id);  

        if($inbox){   

            $client = Client::account('default');
            $client->connect();
            $folder = $client->getFolder($folder);

            $message = $folder->query()->getMessage($id);
            $date = Util::dateFormat($message->getDate());
            $title =  iconv('utf-8', 'utf-8//IGNORE', $message->getSubject()->toArray()[0]);
            $body =  iconv('utf-8', 'utf-8//IGNORE', strlen($message->getHtmlBody()) > 0 ? $message->getHtmlBody() : $message->getTextBody());
            if(preg_match("/<body>/",$body)){
                $body = explode('<body>', $body)[1] ?? '';
                $body = explode('</body>', $body)[0] ?? '';
            }

            $from =  iconv('utf-8', 'utf-8//IGNORE', $message->getFrom());
            $to =  iconv('utf-8', 'utf-8//IGNORE', $message->getTo());
            $cc =  iconv('utf-8', 'utf-8//IGNORE', $message->getCc());

            $html = view('inbox', compact('date','title','from', 'to', 'cc', 'body'))->render();

            $filename = trans('eprog.manager::lang.mail_one').' ('.$date.') - '.str_replace("/","_",Str::slug($title)).'.pdf';
  
            $orient = "P";
            $tempDir = storage_path('temp/public');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

            ini_set('memory_limit','1024M');
            ini_set('max_execution_time',600);

            $mpdf = new Mpdf([
                'tempDir' => $tempDir,
                'format' => 'A4-'.$orient,
                'margin_top' => 10,
                'margin_bottom' => 20,
                'default_font' => 'DejaVu Sans',
            ]);

            $mpdf->WriteHTML(file_get_contents('plugins/eprog/manager/assets/css/pdf.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->SetHTMLFooter('<div style="font-weight:normal; font-family:Arial; font-size:8pt; border-top:none; padding-top:2mm;">Wygenerowane w Emode (emode.pl) - Strona {PAGENO} / {nbpg}</div>');
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);


            $file = $tempDir.'/'.$filename;
            $mpdf->Output($file,'F');
            return $file;

        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }


}