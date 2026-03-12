<?php namespace Eprog\Manager\Validate;
use Input;

class KsefValidate
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

        else if(sizeof($params) > 0 && in_array($params[0],["_pay_info"])){

            $value  = preg_replace(["/,/i","/\s/i"], [".", ""], $value);
            $reg = '/^([1-9]\d{0,15}|0)(\.\d{1,2})$/';
            if(preg_match($reg, $value) == false || $value <= 0)
              return false;
            else
              return true;
        }

        else if(sizeof($params) > 0 && in_array($params[0],["buyer_type","_addbuyer_type"])){
            
            $reg = '/^[a-zA-Z0-9]{1,50}$/';
            if(preg_match($reg, $value) == false)
              return false;
            else
              return true;
        }

        else
           return true;

    }

    /**
     * message gets the validation error message.
     * @return string
     */
    public function message()
    {
      
          return trans("eprog.manager::lang.valid_currency");

    }
}