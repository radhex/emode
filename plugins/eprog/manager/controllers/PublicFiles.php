<?php namespace Eprog\Manager\Controllers;

use App;
use Backend;
use System\Models\File as FileModel;
use Backend\Classes\Controller;
use ApplicationException;
use Exception;
use Url;
use Auth;
use BackendAuth;

/**
 * Backend files controller
 *
 * Used for delivering protected system files, and generating URLs
 * for accessing them.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class PublicFiles extends Controller
{


    public static function getAfterFilters() {return [];}
    public static function getBeforeFilters() {return [];}
    public function getMiddleware() {return [];}
    public function callAction($method, $parameters=false) {
        return call_user_func_array(array($this, $method), $parameters);
    }

    /**
     * Output file, or fall back on the 404 page
     */
    public function get($code = null)
    {


    	$file = $this->findFileObject($code);
    	//if($file->attachment_id == Auth::getUser()->id){
            	try {
                	echo $this->findFileObject($code)->output();
                	exit;
            	}
            	catch (Exception $ex) {}
    	//}

        return App::make('Cms\Classes\Controller')->setStatusCode(404)->run('/404');
    }

    /**
     * Output thumbnail, or fall back on the 404 page
     */

    public function thumb($code = null, $width = 100, $height = 100, $mode = 'auto', $extension = 'auto')
    {

    	$file = $this->findFileObject($code);
    	//if($file->attachment_id == Auth::getUser()->id){
            	try {
                	echo $this->findFileObject($code)->outputThumb(
                    	$width,
                    	$height,
                    	compact('mode', 'extension')
                	);
                	exit;
            	}
            	catch (Exception $ex) {}
    	//}
	

        return App::make('Cms\Classes\Controller')->setStatusCode(404)->run('/404');
    }

    /**
     * Returns the URL for downloading a system file.
     * @param $file System\Models\File
     * @return string
     */

    public static function getDownloadUrl($file)
    {
        return Url::to('client/download/' . self::getUniqueCode($file));
    }


    public static function getAdminDownloadUrl($file)
    {
        return Url::to('backend/download/' . self::getUniqueCode($file));
    }

    /**
     * Returns the URL for downloading a system file.
     * @param $file System\Models\File
     * @param $width int
     * @param $height int
     * @param $options array
     * @return string
     */

    public static function getThumbUrl($file, $width, $height, $options)
    {
        return Url::to('client/thumb/' . self::getUniqueCode($file)) . '/' . $width . '/' . $height . '/' . $options['mode'] . '/' . $options['extension'];
	
    }


    /**
     * Returns a unique code used for masking the file identifier.
     * @param $file System\Models\File
     * @return string
     */

    public static function getUniqueCode($file)
    {
        if (!$file) {
            return null;
        }

        $hash = md5($file->file_name . '!' . $file->disk_name);
        return base64_encode($file->id . '!' . $hash);
    }

    /**
     * Locates a file model based on the unique code.
     * @param $code string
     * @return System\Models\File
     */
    protected static function findFileObject($code)
    {

        if (!$code) {
            throw new ApplicationException('Missing code');
        }

        $parts = explode('!', base64_decode($code));
        if (count($parts) < 2) {
            throw new ApplicationException('Invalid code');
        }

        list($id, $hash) = $parts;

        if (!$file = FileModel::find((int) $id)) {
            throw new ApplicationException('Unable to find file');
        }

        $verifyCode = self::getUniqueCode($file);
        if ($code != $verifyCode) {
            throw new ApplicationException('Invalid hash');
        }

        return $file;
    }

}
