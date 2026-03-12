<?php namespace Eprog\Manager\Validate;

class RegonValidate
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
         $reg = '/^[0-9]{9}$/';
         if(preg_match($reg, $value)==false)
             return false;
         else
         {
             $digits = str_split($value);
             $checksum = (8*intval($digits[0]) + 9*intval($digits[1]) + 2*intval($digits[2]) + 3*intval($digits[3]) + 4*intval($digits[4]) + 5*intval($digits[5]) + 6*intval($digits[6]) + 7*intval($digits[7]))%11;
             if($checksum == 10) 
                 $checksum = 0;
        
             return (intval($digits[8]) == $checksum);
         }
    }

    /**
     * message gets the validation error message.
     * @return string
     */
    public function message()
    {
        
        return trans("eprog.manager::lang.ksef.valid_regon");

    }
}