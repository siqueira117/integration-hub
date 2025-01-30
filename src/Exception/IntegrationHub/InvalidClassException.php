<?php

namespace IntegrationHub\Exception\IntegrationHub;

class InvalidClassException extends \Exception
{
    const ERROR = 620;
    
    public function __construct($message, \Throwable $previous = null) {
        parent::__construct($message, self::ERROR, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
}