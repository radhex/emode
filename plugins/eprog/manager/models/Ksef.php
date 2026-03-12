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
use Eprog\Manager\Classes\Ksef as ClassKsef;
use Eprog\Manager\Classes\Util;


/**
 * Model
 */
class Ksef extends Model
{


    public $table = 'eprog_manager_ksef';
    public $trim = false;

    protected $fillable = [
    'nip',    
    'subject', 
    'saleDate',   
    'issueDate',   
    'permanentStorageDate',     
    'invoicingDate',  
    'acquisitionDate',  
    'ksefNumber',  
    'invoiceNumber',   
    'sellerNip',   
    'sellerName',  
    'buyerIdentifierType',  
    'buyerIdentifierValue',  
    'buyerName',  
    'netAmount',  
    'grossAmount',  
    'vatAmount',  
    'currency',  
    'invoicingMode',  
    'invoiceType',  
    'formCode',  
    'isSelfInvoicing',  
    'hasAttachment',  
    'invoiceHash',     
    'thirdSubjects',  
    'xml',
    'accounting'
    ];  

    public function getInvoiceType()
    {

        return Util::getInvoiceType();

    }

}