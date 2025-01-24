<?php

namespace IntegrationHub;

use IntegrationHub\Exception\FileNotExistsException;
use IntegrationHub\Exception\ConectionTypeNotExists;
use IntegrationHub\Exception\IntegrationTypeNotExists;
use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\IntegrationModel\Parameters\ParametersModel;
use IntegrationHub\Rules\Config;
use IntegrationHub\Rules\Payload;
use IntegrationHub\Rules\Validator;

class IntegrationHub {
    private const INTEGRATION_TYPES     = [ 1 => "SGU" ];
    private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    private const NAMESPACE_VALIDATOR   = "\\IntegrationHub\Rules";
    private const NAMESPACE_CONFIG      = "\\IntegrationHub\Rules";
    private const NAMESPACE_PARAMETERS  = "\\IntegrationHub\IntegrationModel\Parameters";

    private AbstractIntegrationModel $integrationModel; 

    public function __construct(array $payload, array $jsonConfig, int $integrationType, ?array $options = null)
    {
        // Verifica tipo de integração
        if (!in_array($integrationType, array_keys(self::INTEGRATION_TYPES))) {
            throw new IntegrationTypeNotExists("Tipo de integração $integrationType não é válido");
        }

        // Cria as dependencias necessárias
        $integrationName = self::INTEGRATION_TYPES[$integrationType];
        $validator       = $this->checkAndCreateValidator($integrationName);
        $parameters      = $this->checkAndCreateParameters($integrationName, $options);
        $config          = $this->checkAndCreateConfig($integrationName, $jsonConfig);
        $payload         = new Payload($payload);

        // Cria a classe de configuração da integração
        $this->integrationModel = $this->createIntegration($integrationName, $validator, $parameters, $payload, $config);
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
     * @return ParametersModel Classe de parametros instaciada
     */
    private function checkAndCreateParameters(string $integrationName, array $options):  ParametersModel
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
    private function createIntegration(
        string $integrationName, 
        Validator $validator, 
        ParametersModel $parameters,
        Payload $payload,
        Config $config
        ): AbstractIntegrationModel 
    {
        $file       = __DIR__."/IntegrationModel/$integrationName/$integrationName.php";
        $className  = self::NAMESPACE_INTEGRATION . "\\$integrationName\\$integrationName"; 
        if (!file_exists($file)) {
            syslog(LOG_ERR, "[HUB][ERR] - Arquivo de configuração '$integrationName' não foi configurado");
            throw new FileNotExistsException("Arquivo de configuração '$integrationName' não foi configurado");
        }

        require_once($file);
        return new $className($payload, $parameters, $validator, $config);
    }

    public function run() 
    {
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