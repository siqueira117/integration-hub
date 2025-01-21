<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Exception\ConfigException;
use IntegrationHub\Rules\Config;

class ConfigSGU extends Config {    
    public function validateJson(array $config): void
    {
        parent::validateJson($config);

        // Verificando se endpoint e token foram enviados
        $enviroment = array_keys($config);

        foreach ($enviroment as $key => $env) {
            if (!array_key_exists("endpoint", $config[$env]) || !$config[$env]["endpoint"]) {
                throw new ConfigException("Endpoint deve ser informado e válido");
            }

            if (!array_key_exists("token", $config[$env]) || !$config[$env]["token"]) {
                throw new ConfigException("Token deve ser informado e válido");
            }

            if (!array_key_exists("method", $config[$env]) || !$config[$env]["method"]) {
                throw new ConfigException("Method deve ser informado e válido");
            }
        }
    }
}