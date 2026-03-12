<?php namespace Eprog\Manager\Validate;

class SwiftValidate
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
        
         $reg = '/[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?/i';
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
        
        return trans("eprog.manager::lang.valid_swift");

    }
}

