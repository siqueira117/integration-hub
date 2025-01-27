<?php

namespace IntegrationHub;

use IntegrationHub\Exception\FileNotExistsException;
use IntegrationHub\Exception\ConectionTypeNotExists;
use IntegrationHub\Exception\IntegrationTypeNotExists;
use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\IntegrationModel\Parameters\Parameters;
use IntegrationHub\Rules\Config;
use IntegrationHub\Rules\Payload;
use IntegrationHub\Rules\Validator;

class IntegrationHub {
    // CONSTANTS
    private const INTEGRATION_TYPES     = [ 1 => "SGU" ];
    private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    private const NAMESPACE_VALIDATOR   = "\\IntegrationHub\Rules";
    private const NAMESPACE_CONFIG      = "\\IntegrationHub\Rules";
    private const NAMESPACE_PARAMETERS  = "\\IntegrationHub\IntegrationModel\Parameters";

    // CONFIG
    private Payload $payload;
    private Validator $validator;
    private Parameters $parameters;
    private Config $config;
    private string $integrationName;

    // MODELO DE INTEGRAÇÃO
    private AbstractIntegrationModel $integrationModel; 

    public function __construct(int $integrationType, ?array $payload, ?array $jsonConfig, ?array $options = null)
    {
        // Verifica tipo de integração
        if (!in_array($integrationType, array_keys(self::INTEGRATION_TYPES))) {
            throw new IntegrationTypeNotExists("Tipo de integração $integrationType não é válido");
        }

        // Cria as dependencias necessárias
        $this->integrationName = self::INTEGRATION_TYPES[$integrationType];
        
        $this->validator    = $this->checkAndCreateValidator($this->integrationName);
        $this->payload      = new Payload($payload);
        $this->parameters   = $this->checkAndCreateParameters($this->integrationName, $options);
        $this->config       = $this->checkAndCreateConfig($this->integrationName, $jsonConfig);
    }

    /**
     * Retorna classe de validação correspondente ao tipo de integração informado
     * Se a classe não existir, retorna a classe de validação padrão
     * 
     * @param string $integrationName Nome da integração a qual a classe pertence
     * 
     * @return Validator Classe de validação instaciada
     */
    private function checkAndCreateValidator(string $integrationName): Validator
    {
        $classPrefix = "Validator";

        $file       = __DIR__."/IntegrationModel/$integrationName/$classPrefix$integrationName.php";
        $className  = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$classPrefix$integrationName"; 
        if (!file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - Validator padrão sendo utilizado!");
            
            $file       = __DIR__."/Rules/$classPrefix.php";
            $className  = self::NAMESPACE_VALIDATOR . "\\$classPrefix";
        }
        
        if (PHP_SAPI === 'cli') {
            print_r("Validator file: $file\n");
            print_r("Validator className: $className\n");
        }
        
        require_once($file);
        return new $className();
    }

    /**
     * Retorna classe de parametros correspondente ao tipo de integração informado
     * Se a classe não existir, retorna a classe de parametros padrão
     * 
     * @param string $integrationName Nome da integração a qual a classe pertence
     * 
     * @return Parameters Classe de parametros instaciada
     */
    private function checkAndCreateParameters(string $integrationName, array $options):  Parameters
    {
        // Verifica se arquivo de configuração de validação existe
        $classPrefix = "Parameters";

        $file       = __DIR__."/IntegrationModel/$integrationName/$classPrefix$integrationName.php";
        $className  = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$classPrefix$integrationName"; 
        if (!file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - Parameters padrão sendo utilizado!");
            
            $file       = __DIR__."/Rules/$classPrefix.php";
            $className  = self::NAMESPACE_PARAMETERS . "\\$classPrefix";
        }
        
        if (PHP_SAPI === 'cli') {
            print_r("Parameters file: $file\n");
            print_r("Parameters className: $className\n");
        }

        require_once($file);
        return new $className($options);
    }

    private function checkAndCreateConfig(string $integrationName, array $jsonConfig): Config 
    {
        // Verifica se arquivo de configuração de validação existe
        $classPrefix = "Config";
        $file       = __DIR__."/IntegrationModel/$integrationName/$classPrefix$integrationName.php";
        $className  = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$classPrefix$integrationName"; 
        if (!file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - Config padrão sendo utilizado!");
            
            $file       = __DIR__."/Rules/$classPrefix.php";
            $className  = self::NAMESPACE_CONFIG . "\\$classPrefix";
        }
        
        if (PHP_SAPI === 'cli') {
            print_r("Config file: $file\n");
            print_r("Config className: $className\n");
        }

        require_once($file);
        return new $className($jsonConfig);
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

        require_once($file);
        return new $className($this->payload, $this->parameters, $this->validator, $this->config);
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

    private function runApi() 
    {
        $json = $this->integrationModel->build();
        $this->integrationModel->send($json);
    }
}