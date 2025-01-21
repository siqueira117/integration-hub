<?php

namespace IntegrationHub\Exception;

class FileNotExistsException extends \Exception
{
    const ERROR = 300;
    
    public function __construct($message, \Throwable $previous = null) {
        parent::__construct($message, self::ERROR, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
}