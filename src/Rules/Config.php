<?php

namespace IntegrationHub\Rules;

use IntegrationHub\Exception\ConfigException;

abstract class Config {
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
    }

    public function getEnvData(bool $istest = true): array 
    {
        $env = $istest ? "hml" : "prd";
        return $this->jsonConfig[$env];
    }
}