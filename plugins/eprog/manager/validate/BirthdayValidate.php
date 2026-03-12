<?php namespace Eprog\Manager\Validate;

class BirthdayValidate
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

         $reg = '/([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))/';
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

       return trans("eprog.manager::lang.valid_birthday");
       
    }
}

