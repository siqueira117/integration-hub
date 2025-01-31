<?php

namespace IntegrationHub;

use IntegrationHub\Exception\FileNotExistsException;
use IntegrationHub\Exception\ConectionTypeNotExists;
use IntegrationHub\Exception\IntegrationHub\IntegrationTypeNotExists;
use IntegrationHub\Exception\IntegrationHub\InvalidClassException;
use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\Rules\Parameters;
use IntegrationHub\Rules\Config;
use IntegrationHub\Rules\Payload;
use IntegrationHub\Rules\Validator;

class IntegrationHub {
    // CONSTANTS
    private const INTEGRATION_TYPES     = [ 1 => "SGU", 2 => "MEDEX" ];
    private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    private const NAMESPACE_RULES       = "\\IntegrationHub\Rules";

    // CONFIG
    private Payload $payload;
    private array $originalPayload;
    private array $newPayload;
    private Validator $validator;
    private Parameters $parameters;
    private Config $config;
    private string $integrationName;

    // MODELO DE INTEGRAÇÃO
    private AbstractIntegrationModel $integrationModel; 

    public function __construct(int $integrationType, ?array $payload = null, ?array $jsonConfig = null, ?array $parameters = null)
    {
        if ($integrationType)   $this->setIntegrationType($integrationType);
        if ($payload)           $this->setPayload($payload);
        if ($parameters)        $this->setParameters($parameters);
        if ($jsonConfig)        $this->setConfig($jsonConfig);

        $this->validator = $this->checkAndCreateObject("Validator");    
    }

    /**
     * 
     */
    public function run() 
    {
        // Cria a classe de configuração da integração
        $this->integrationModel = $this->createIntegration();
        
        $type = $this->integrationModel->getType();
        switch ($type) {
            case CONN_API:
                return $this->runApi();
                break;
            default:
                throw new ConectionTypeNotExists("{$type} não é um tipo de conexão válido");
                break;
        }
    }

    public function replaceAndAddFieldsOnPayload(array $fields): array
    {
        if (!isset($this->originalPayload)) {
            throw new InvalidClassException("Payload deve ser informado");
        }

        $this->newPayload = array_merge($this->originalPayload, $fields);
        $this->payload  = new Payload($this->newPayload);
        return $this->newPayload;
    }

    public function getBodyRequest(): array
    {
        if (!isset($this->integrationModel)) $this->integrationModel = $this->createIntegration();

        return $this->integrationModel->build();
    }

    private function checkAndCreateObject(string $classPrefix, $parametersToObject = null) 
    {
        if (PHP_SAPI === 'cli') print_r("[HUB] - Criando objeto $classPrefix\n");

        $integrationName = $this->integrationName;

        $file       = __DIR__."/IntegrationModel/$integrationName/$classPrefix$integrationName.php";
        $className  = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$classPrefix$integrationName"; 
        if (!file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - $classPrefix padrão sendo utilizado!");
            if (PHP_SAPI === 'cli') print_r("[HUB] - $classPrefix padrão sendo utilizado!\n");
            
            $file = __DIR__."/Rules/$classPrefix.php";
            if (!file_exists($file)) {
                throw new FileNotExistsException("Arquivo $file não existe, verifique se classPrefix foi informado corretamente");
            }

            $className = self::NAMESPACE_RULES . "\\$classPrefix";
        }
        
        if (PHP_SAPI === 'cli') {
            print_r("[HUB] - $classPrefix file: $file\n");
            print_r("[HUB] - $classPrefix className: $className\n");
        }
        
        require_once($file);

        if ($parametersToObject) return new $className($parametersToObject); 
        return new $className();
    }

    /**
     * 
     */
    private function createIntegration(): AbstractIntegrationModel 
    {
        $integrationName = $this->integrationName;
        $file       = __DIR__."/IntegrationModel/$integrationName/$integrationName.php";
        $className  = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$integrationName"; 
        if (!file_exists($file)) {
            syslog(LOG_ERR, "[HUB][ERR] - Arquivo de configuração '$integrationName' não foi configurado");
            throw new FileNotExistsException("Arquivo de configuração '$integrationName' não foi configurado");
        }

        $this->validateClasses();
        require_once($file);
        return new $className($this->payload, $this->parameters, $this->validator, $this->config);
    }

    private function validateClasses(): void
    {
        $classes = [
            "Payload"   => isset($this->payload),
            "Parameter" => isset($this->parameters),
            "Validator" => isset($this->validator),
            "Config"    => isset($this->config)
        ];

        $classes = array_filter($classes, function ($v) {
            return (!$v);
        });

        if (in_array(null, $classes)) {
            $keys = array_keys($classes);
            throw new InvalidClassException("Parametros não foram informadas: " . json_encode($keys));
        }
    }

    private function runApi() 
    {
        $json = $this->integrationModel->build();
        $this->integrationModel->send($json);
    }

    // SETTERS
    public function setIntegrationType(int $integrationType): self
    {
        // Verifica tipo de integração
        if ($integrationType && !in_array($integrationType, array_keys(self::INTEGRATION_TYPES))) {
            throw new IntegrationTypeNotExists("Tipo de integração $integrationType não é válido");
        }

        // Cria as dependencias necessárias
        $this->integrationName = self::INTEGRATION_TYPES[$integrationType];
        return $this;
    }

    public function setPayload(array $payload): self
    {
        $this->originalPayload = $payload;
        $this->payload = new Payload($payload);
        return $this;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $this->checkAndCreateObject("Parameters", $parameters);
        return $this;
    }

    public function setConfig(array $jsonConfig): self
    {
        $this->config = $this->checkAndCreateObject("Config", $jsonConfig);
        return $this;
    }
    // ===============

    // GETTERS
    public function getOriginalPayload(): array
    {
        return $this->originalPayload;
    }

    public function getNewPayload(): array
    {
        if (!isset($this->newPayload)) throw new InvalidClassException("Payload não foi modificado");
         
        return $this->newPayload;
    }
}