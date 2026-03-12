<?php namespace Eprog\Manager\Validate;
use Input;

class CurrencyValidate
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

           $value  = preg_replace(["/,/i","/\s/i"], [".", ""], $value);
           return preg_match('/^[0-9\.]+$/', $value) && $value > 0;

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