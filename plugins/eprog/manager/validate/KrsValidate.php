<?php namespace Eprog\Manager\Validate;

class KrsValidate
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

        return preg_match('/^[0-9]{10}$/Du', $value);
              
    }

    /**
     * message gets the validation error message.
     * @return string
     */
    public function message()
    {
     
        return trans("eprog.manager::lang.ksef.valid_krs");
        
    }
}