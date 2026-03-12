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
use Lang;

class Trash extends ImapController
{

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'inbox',  'trash');
        $this->folder = config("imap.folders.trash.folder");
        
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
                        $message = $this->connect()->query()->getMessageByUid($Id)->delete();
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

    public function preview_onTrash()
    {

        $id = Input::segment(6);

        if($id > 0){

            $message = $this->connect()->query()->getMessageByUid($id)->delete();
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to("/".config('cms.backendUri')."/eprog/manager/trash");

        }
    }

}