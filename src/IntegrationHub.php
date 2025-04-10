<?php

namespace IntegrationHub;

use IntegrationHub\Exception\{FileNotExistsException, ConectionTypeNotExists};
use IntegrationHub\Exception\IntegrationHub\{IntegrationTypeNotExists, InvalidClassException};
use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\Rules\{Options, Config, Logger, Payload, Validator, Variables};

class IntegrationHub {
    // CONSTANTS
    private const INTEGRATION_TYPES     = [ 1 => "SGU", 2 => "MEDEX", 3 => "FACIL", 4 => "CBS" ];
    private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    private const NAMESPACE_RULES       = "\\IntegrationHub\Rules";

    // CONFIG
    private Payload $payload;
    private array $originalPayload;
    private array $newPayload;
    private Validator $validator;
    private Options $options;
    private Config $config;
    private string $integrationName;
    private string $operadora;

    // MODELO DE INTEGRAÇÃO
    private AbstractIntegrationModel $integrationModel; 

    public function __construct(int $integrationType, string $operadora, ?array $payload = null, ?array $jsonConfig = null, ?array $options = null)
    {
        Logger::message(LOG_NOTICE, "Construindo classe...");
        if ($operadora)         $this->setOperadora($operadora);
        if ($integrationType)   $this->setIntegrationType($integrationType);
        if ($payload)           $this->setPayload($payload);
        if ($options)           $this->setOptions($options);
        if ($jsonConfig)        $this->setConfig($jsonConfig);

        $this->validator = $this->checkAndCreateObject("Validator");    
    }

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

    public function replaceAndAddFieldsOnPayload(array $fields): self
    {
        if (!isset($this->originalPayload)) {
            throw new InvalidClassException("Payload deve ser informado");
        }

        $this->newPayload = array_merge($this->originalPayload, $fields);
        $this->payload  = new Payload($this->newPayload);
        return $this;
    }

    public function getBodyRequest(): array
    {
        if (!isset($this->integrationModel)) $this->integrationModel = $this->createIntegration();

        return $this->integrationModel->build();
    }

    private function checkAndCreateObject(string $classPrefix, $optionsToObject = null) 
    {
        if (PHP_SAPI === 'cli') print_r("[HUB] - Criando objeto $classPrefix\n");

        $integrationName = $this->integrationName;

        $file       = __DIR__."/IntegrationModel/$integrationName/$classPrefix$integrationName.php";
        $className  = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$classPrefix$integrationName"; 
        if (!file_exists($file)) {
            Logger::message(LOG_NOTICE, "$classPrefix padrão sendo utilizado!");
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

        if ($optionsToObject) {
            if (method_exists($className, "factory")) {
                return $className::factory($this->integrationName, $this->operadora, $optionsToObject);
            } else {
                return new $className($optionsToObject);
            }
        }

        return new $className();
    }

    private function createIntegration(): AbstractIntegrationModel 
    {
        $integrationName    = $this->integrationName;
        $file               = __DIR__."/IntegrationModel/$integrationName/$integrationName.php";
        $className          = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$integrationName"; 
        if (!file_exists($file)) {
            Logger::message(LOG_ERR, "Arquivo de configuração '$integrationName' não foi configurado");
            throw new FileNotExistsException("Arquivo de configuração '$integrationName' não foi configurado");
        }

        $this->validateClasses();
        require_once($file);
        return $className::factory($this->integrationName, $this->payload, $this->options, $this->validator, $this->config, $this->operadora);
    }

    private function validateClasses(): void
    {
        $classes = [
            "Payload"   => isset($this->payload),
            "Options"   => isset($this->options),
            "Validator" => isset($this->validator),
            "Config"    => isset($this->config),
            "Operadora" => isset($this->operadora)
        ];

        $classes = array_filter($classes, function ($v) {
            return (!$v);
        });

        if (in_array(null, $classes)) {
            $keys = array_keys($classes);
            throw new InvalidClassException("Parametros não foram informadas: " . json_encode($keys));
        }
    }

    private function runApi(): array 
    {
        $json = $this->integrationModel->build();
        return $this->integrationModel->send($json);
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

        Variables::add("PROPOSTA_ID", $payload["propostaID"]);
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = $this->checkAndCreateObject("Options", $options);
        return $this;
    }

    public function setConfig(array $jsonConfig): self
    {
        $this->config = $this->checkAndCreateObject("Config", $jsonConfig);
        return $this;
    }

    public function setOperadora(string $operadora): self
    {
        $operadoraName = str_replace("-", "", $operadora);
        if (is_numeric($operadoraName[0])) {
            $operadoraName = "_" . $operadoraName;
        }

        $this->operadora = $operadoraName;
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

    public function getOperadora(): string
    {
        return $this->operadora;
    }
}