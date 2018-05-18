<?php
/**
 * Description of Error
 *
 * @author khoaht
 */
namespace App;

class Error
{
    private $errors = [];

    public function __construct()
    {
    }

    public function setError($code, $message)
    {
        $error = new \stdClass();
        $error->code = $code;
        $error->message = $message;
        $this->errors[] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
