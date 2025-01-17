<?php

namespace IntegrationHub\IntegrationModel;

use IntegrationHub\IntegrationModel\Parameters\ParametersModel;
use IntegrationHub\Rules\{Validator, Payload};

abstract class AbstractIntegrationModel {
    protected $payload;
    protected $parameters;
    protected $validator;

    public function __construct(Payload $payload, ParametersModel $parameters, Validator $validator)
    {
        $this->payload      = $payload;
        $this->parameters   = $parameters;
        $this->validator    = $validator;
    }

    abstract public function build(): array;
}