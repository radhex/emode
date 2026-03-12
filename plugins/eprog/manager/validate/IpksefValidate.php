<?php namespace Eprog\Manager\Validate;

class IpksefValidate
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

         $reg = '/^[0-9]{3}[a-zA-Z0-9]{10}$/i';
         if(preg_match($reg, $value)==false)
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
        
        return trans("eprog.manager::lang.valid_ipksef");

    }
}

