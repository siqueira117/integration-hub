<?php

namespace IntegrationHub\IntegrationModel;
use Rules\Payload;

abstract class AbstractModel {
    protected $payload;

    public function __construct(Payload $payload)
    {
        $this->payload = $payload;
    }

    abstract public function build(): array;
    abstract private function getParameters(): 
}