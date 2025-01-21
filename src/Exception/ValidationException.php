<?php

namespace IntegrationHub\Exception;

class ValidationException extends \Exception
{
    const ERROR = 550;
    
    public function __construct($message, \Throwable $previous = null) {
        if (is_array($message)) $message = json_encode($message);
        parent::__construct($message, self::ERROR, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
}