<?php namespace Eprog\Manager\Validate;
use Input;

class NipValidate
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

  
         $nipWithoutDashes = preg_replace("/-/","",$value);
         $reg = '/^[0-9]{10}$/';
         if(preg_match($reg, $nipWithoutDashes)==false)
             return false;
         else
         {
             $digits = str_split($nipWithoutDashes);
             $checksum = (6*intval($digits[0]) + 5*intval($digits[1]) + 7*intval($digits[2]) + 2*intval($digits[3]) + 3*intval($digits[4]) + 4*intval($digits[5]) + 5*intval($digits[6]) + 6*intval($digits[7]) + 7*intval($digits[8]))%11;
        
             return (intval($digits[9]) == $checksum);
         }

    }

    /**
     * message gets the validation error message.
     * @return string
     */
    public function message()
    {
        
        return trans("eprog.manager::lang.valid_nip");
        
    }
}