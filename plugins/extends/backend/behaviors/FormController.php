<?php namespace Extends\Backend\Behaviors;

use Db;
use Str;
use Lang;
use Flash;
use Event;
use Redirect;
use Backend;
use Backend\Classes\ControllerBehavior;
use Winter\Storm\Router\Helper as RouterHelper;
use ApplicationException;
use Exception;
use Backend\Behaviors\FormController as BehaviorsFormController;
use October\Rain\Exception\ValidationException;
use Mail;
use Session;
use Input;
use Eprog\Manager\Classes\Google;

/**
 * Adds features for working with backend forms. This behavior
 * will inject CRUD actions to the controller -- including create,
 * update and preview -- along with some relevant AJAX handlers.
 *
 * Each action supports a custom context code, allowing fields
 * to be displayed or hidden on a contextual basis, as specified
 * by the form field definitions or some other custom logic.
 *
 * This behavior is implemented in the controller like so:
 *
 *     public $implement = [
 *         \Backend\Behaviors\FormController::class,
 *     ];
 *
 *     public $formConfig = 'config_form.yaml';
 *
 * The `$formConfig` property makes reference to the form configuration
 * values as either a YAML file, located in the controller view directory,
 * or directly as a PHP array.
 *
 * @see https://wintercms.com/docs/backend/forms Back-end form documentation
 * @package winter\wn-backend-module
 * @author Alexey Bobkov, Samuel Georges
 */
class FormController extends BehaviorsFormController
{
   

    /**
     * AJAX handler "onSave" called from the create action and
     * primarily used for creating new records.
     *
     * This handler will invoke the unique controller overrides
     * `formBeforeCreate` and `formAfterCreate`.
     *
     * @param string $context Form context
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function create_onSave($context = null)
    {
        $this->context = strlen($context) ? $context : $this->getConfig('create[context]', self::CONTEXT_CREATE);
 
        $model = $this->controller->formCreateModelObject();
        $model = $this->controller->formExtendModel($model) ?: $model;

        $this->initForm($model);

        $this->controller->formBeforeSave($model);
        $this->controller->formBeforeCreate($model);

        $modelsToSave = $this->prepareModelsToSave($model, $this->formWidget->getSaveData());

        if(!(Input::segment(4) == "inbox" && Input::segment(5) == "create") && !(Input::segment(4) == "drive" && Input::segment(5) == "create")){
          Db::transaction(function () use ($modelsToSave) {
              foreach ($modelsToSave as $modelToSave) {
                  $modelToSave->save(null, $this->formWidget->getSessionKey());
              }
          });
        }

        $this->controller->formAfterSave($model);
        $this->controller->formAfterCreate($model);
        if(Input::segment(4) == "inbox" && Input::segment(5) == "create" && isset($modelsToSave[0]))
        return $this->sentMail($modelsToSave[0]);
        else if(Input::segment(4) == "drive" && Input::segment(5) == "create" && isset($modelsToSave[0]))
        return $this->uploadFiles($modelsToSave[0]);
        else
        Flash::success($this->getLang("{$this->context}[flashSave]", 'backend::lang.form.create_success'));

        if ($redirect = $this->makeRedirect($this->context, $model)) {
            return $redirect;
        }
    }

    public function sentMail($model)
    {
       
        if(!preg_match("/;/i",$model->to))
          $to = [trim($model->to)];
        else
          $to = explode(";",$model->to);

        $to = array_unique($to);
        if(sizeof($to) > 0){ 
            foreach($to as $t){
              if(filter_var(trim($t), FILTER_VALIDATE_EMAIL) == false) 
                throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_field_to")]);
            }
        }      


        if(strlen($model->cc) > 0){
            if(!preg_match("/;/i",$model->cc))
              $cc = [trim($model->cc)];
            else
              $cc = explode(";",$model->cc);

            if(sizeof($cc) > 0){ 
                foreach($cc as $c){
                  if(filter_var(trim($c), FILTER_VALIDATE_EMAIL) == false) 
                    throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_field_cc")]);
                }
            }                
        }

        $data = [
              "id" => $model->id,
              "title" => $model->title,
              "body" => $model->body,
              "email" => $to,
              "cc" => $cc ?? [],
              "files" =>  $model->files
        ];


        foreach ($model->files as $file) {
            if(!$file->exists()){
                $model->delete();  
                Flash::error(Lang::get('eprog.manager::lang.email_error'));
                return Redirect::to("/".config('cms.backendUri')."/eprog/manager/inbox/create");
            }          
        }

        $send = Mail::send('eprog.manager::mail.mailing', $data, function($message) use ($data) {
              $message->to($data['email']);
              if(sizeof($data['cc']) > 0)
              $message->cc($data['cc']);
              $message->subject($data['title']);
              foreach($data['files']  as $file) 
              $message->attach($file->getLocalPath(), ['as' => $file->file_name]);
        });

        if($send) {
          
          $stream = imap_open(
              "{".env('IMAP_HOST', 'smtp.mailgun.org')."/imap/ssl/novalidate-cert}",
              env('IMAP_USERNAME', 'username'),
              env('IMAP_PASSWORD', 'password'),
              null,
              1,
              ['DISABLE_AUTHENTICATOR' => 'GSSAPI']
          );
          
          imap_append(
              $stream,
              "{".env('IMAP_HOST', 'localhost')."/imap/ssl/novalidate-cert}" . env('IMAP_SENT', 'Sent'),
              $send->toString()."\r\n",
              "\\Seen" 
          );
          
          imap_close($stream);

        }


        foreach ($model->files as $file) {

          $file && $file->delete();

        }
     
        Session::forget("inbox.email");             
        Session::forget("inbox.title");
        Session::forget("inbox.body");


        $model->delete();  
        Flash::success(Lang::get('eprog.manager::lang.email_sent_success'));
        return Redirect::to("/".config('cms.backendUri')."/eprog/manager/sent");
                

    }

   

   public function uploadFiles($model)
   {
      
      $client = Google::connect();
      if($client->getAccessToken()){
        $service = new \Google_Service_Drive($client);


          foreach ($model->files as $file) {
              if(!$file->exists()){
                  $model->delete();  
                  Flash::error(Lang::get('eprog.manager::lang.email_error'));
                  return Redirect::to("/".config('cms.backendUri')."/eprog/manager/drive/create");
              }                      
          }

          foreach ($model->files as $file) {
              if($file->exists()){

                  $uplodFile = new \Google\Service\Drive\DriveFile();
                  $uplodFile->setName($file->file_name);
                  if(Input::filled('folder'))
                  $uplodFile->setParents(array(Input::get('folder')));
                  $result = $service->files->create(
                      $uplodFile,
                      [
                          'data' => file_get_contents($file->getLocalPath()),
                          'mimeType' => 'application/octet-stream',
                          'uploadType' => 'multipart'
                      ]
                  );
              }          
          }

          foreach ($model->files as $file)
          $file && $file->delete();
          $model->delete();
          Flash::success(Lang::get('eprog.manager::lang.drive_upload_sucess')); 


      }


   }
}
