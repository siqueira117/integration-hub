<?php

namespace IntegrationHub\Rules;

use IntegrationHub\Exception\ConfigException;

if (!defined('CUSTOMDIR')) define("CUSTOMDIR", realpath(__DIR__ . '/../../../../custom'));

abstract class Config {
    private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    protected $validatorConfig = [];
    private $jsonConfig;

    public static function factory(string $integracaoName, ?string $operadora = null, array $config)
	{
		$operadora = $operadora ?? \ENVIRONMENT\hostname();

        // Formata o nome da operadora
        $operadoraName = str_replace("-", "", $operadora);
        if (is_numeric($operadoraName[0])) {
            $operadoraName = "_" . $operadoraName;
        }

        // Caminho do arquivo
        $file = CUSTOMDIR . "/{$operadora}/svc/integracao/{$integracaoName}/{$operadoraName}Config{$integracaoName}.php";
        
        // Nome da classe que será instanciada
        $className = self::NAMESPACE_INTEGRATION . "\\$integracaoName\\{$operadoraName}Config{$integracaoName}";

        if (file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - Classe custom encontrada: $className");
            require_once($file);
        } else {
            // Classe padrão no namespace correto
            $className = self::NAMESPACE_INTEGRATION . "\\$integracaoName\\Config{$integracaoName}";

            Logger::message(LOG_NOTICE, "Classe custom não encontrada, tentando classe padrão: $className");

            if (!class_exists($className)) {
                throw new \Exception("Nenhuma implementação encontrada para {$integracaoName} - " . __CLASS__ );
            }
        }

        return new $className($config);
	}

    public function __construct(array $config)
    {
        $this->validateJson($config);
        $this->jsonConfig = $config;
    }

    private function validateJson(array $config): void 
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
                    if (PHP_SAPI === 'cli') print_r("[HUB] - Campo {$env}->{$field} é obrigatório e deve ser informado\n");
                    
                    throw new ConfigException("Campo {$env}->{$field} é obrigatório e deve ser informado no array de configuração");
                }

                foreach ($this->validatorConfig[$field] as $option) {
                    if (!array_key_exists($option, $config[$env][$field])) {
                        if (PHP_SAPI === 'cli') print_r("[HUB] - Campo {$env}->{$field}->{$option} é obrigatório e deve ser informado\n");

                        throw new ConfigException("Campo {$env}->{$field}->{$option} é obrigatório e deve ser informado no array de configuração");
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