<?php

namespace IntegrationHub\IntegrationModel;

use IntegrationHub\IntegrationModel\Parameters\ParametersModel;
use IntegrationHub\Rules\{Config, Validator, Payload};

abstract class AbstractIntegrationModel {
    protected Payload           $payload;
    protected ParametersModel   $parameters;
    protected Validator         $validator;
    protected Config            $config;

    public function __construct(Payload $payload, ParametersModel $parameters, Validator $validator, Config $config)
    {
        $this->payload      = $payload;
        $this->parameters   = $parameters;
        $this->validator    = $validator;
        $this->config       = $config;
    }

    public function getConfig(): Config 
    {
        return $this->config;
    }

    abstract public function build(): array;
    abstract public function getType(): int;
    abstract public function send(array $bodyRequest): array; 

}