<?php namespace Eprog\Manager\Validate;

class BdoValidate
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

        return strlen($value) <= 9;
              
    }

    /**
     * message gets the validation error message.
     * @return string
     */
    public function message()
    {
     
        return trans("eprog.manager::lang.ksef.valid_bdo");
        
    }
}