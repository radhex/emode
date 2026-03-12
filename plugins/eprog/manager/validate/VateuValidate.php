<?php namespace Eprog\Manager\Validate;
use Input;

class VateuValidate
{
    /**
     * validate determines if the validation rule passes.
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool
     */
    public function validate($attribute, $value, $params)
    {

         $check = true;
         if(sizeof($params) > 1)
         $check = Input::get($params[0]) == $params[1] ? true : false;
         if(!$check) return true;

         $vateuWithoutDashes = preg_replace("/-/","",$value);
         $reg = '/^((AT)(U\d{8})|(BE)(0\d{9})|(BG)(\d{9,10})|(CY)(\d{8}[LX])|(CZ)(\d{8,10})|(DE)(\d{9})|(DK)(\d{8})|(EE)(\d{9})|(EL|GR)(\d{9})|(ES)([\dA-Z]\d{7}[\dA-Z])|(FI)(\d{8})|(FR)([\dA-Z]{2}\d{9})|(HU)(\d{8})|(IE)(\d{7}[A-Z]{2})|(IT)(\d{11})|(LT)(\d{9}|\d{12})|(LU)(\d{8})|(LV)(\d{11})|(MT)(\d{8})|(NL)(\d{9}(B\d{2}|BO2))|(PL)(\d{10})|(PT)(\d{9})|(RO)(\d{2,10})|(SE)(\d{12})|(SI)(\d{8})|(SK)(\d{10}))$/';
         if(preg_match($reg, $vateuWithoutDashes)==false)
             return false;
         else
             return true;
         
    }

    /**
     * message gets the validation error message.
     * @return string
     */
    public function message()
    {
        
        return trans("eprog.manager::lang.valid_vateu");
        
    }
}