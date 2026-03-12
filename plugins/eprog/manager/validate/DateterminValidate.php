<?php namespace Eprog\Manager\Validate;

class DateterminValidate
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

       if(sizeof($params) > 0 && $value != ""){

            if($params[0] == 1) $from = "2020-01-01";
            if($params[0] == 2) $from = "2006-01-01";
            if($params[0] == 3) $from = "2016-07-01";

            $date = date("Y-m-d",strtotime($value));

            if($date < $from || $date > "2050-01-01")
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
        
            return trans("eprog.manager::lang.ksef.valid_date");
        
    }
}

