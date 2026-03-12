<?php namespace Eprog\Manager\Validate;

class CodeValidate
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

        return preg_match('/^[0-9]{2}-?[0-9]{3}$/Du', $value);
              
    }

    /**
     * message gets the validation error message.
     * @return string
     */
    public function message()
    {
     
        return trans("eprog.manager::lang.valid_code");

    }
}