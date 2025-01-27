<?php

namespace IntegrationHub\Rules;

use IntegrationHub\Exception\ConfigException;

abstract class Config {
    public $validatorConfig = [];
    private $jsonConfig;

    public function __construct(array $config)
    {
        $this->validateJson($config);
        $this->jsonConfig = $config;
    }

    public function validateJson(array $config): void 
    {
        // 1 - Verifica ao menos um ambiente está definido
        if (!array_key_exists("hml", $config) && !array_key_exists("prd", $config)) {
            throw new ConfigException("Ao menos um ambiente deve ser definido no JSON de configuração");
        }

        // 2 - Verifica regras de validação
        $enviroments = array_keys($config);

        foreach ($enviroments as $env) {
            $requiredFields = array_keys($this->validatorConfig);
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $config[$env])) {
                    if (PHP_SAPI === 'cli') print_r("Campo {{$env}}->{{$field}} é obrigatório e deve ser informado\n");
                    
                    throw new ConfigException("Campo {{$env}}->{{$field}} é obrigatório e deve ser informado");
                }

                foreach ($this->validatorConfig[$field] as $option) {
                    if (!array_key_exists($option, $config[$env][$field])) {
                        if (PHP_SAPI === 'cli') print_r("Campo {{$env}}->{{$field}}->{{$option}} é obrigatório e deve ser informado\n");

                        throw new ConfigException("Campo {{$env}}->{{$field}}->{{$option}} é obrigatório e deve ser informado");
                    }
                }
            }
        }
    }

    public function getEnvData(bool $istest = true): array 
    {
        $env = $istest ? "hml" : "prd";
        return $this->jsonConfig[$env];
    }
}