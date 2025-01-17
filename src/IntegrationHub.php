<?php

namespace IntegrationHub;

use Exception\FileNotExistsException;
use IntegrationHub\Exception\IntegrationTypeNotExists;
use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\IntegrationModel\Parameters\ParametersModel;
use IntegrationHub\Rules\Payload;
use IntegrationHub\Rules\Validator;

class IntegrationHub {
    private const INTEGRATION_TYPES     = [ 1 => "SGU" ];
    private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    private const NAMESPACE_VALIDATOR   = "\\IntegrationHub\Rules";
    private const NAMESPACE_PARAMETERS  = "\\IntegrationHub\IntegrationModel\Parameters";

    private $integrationModel; 

    public function __construct(array $payload, int $integrationType)
    {
        // Verifica tipo de integração
        if (!in_array($integrationType, array_keys(self::INTEGRATION_TYPES))) {
            throw new IntegrationTypeNotExists("Tipo de integração $integrationType não é válido");
        }

        // Cria as dependencias necessárias
        $integrationName = self::INTEGRATION_TYPES[$integrationType];
        $validator       = $this->checkAndCreateValidator($integrationName);
        $parameters      = $this->checkAndCreateParameters($integrationName);
        $payload         = new Payload($payload);

        // Cria a classe de configuração da integração
        $this->integrationModel = $this->createIntegration($integrationName, $validator, $parameters, $payload);
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

        $file       = __DIR__."/IntegrationModel/{$integrationName}/{$classPrefix}{$integrationName}.php";
        $className  = self::NAMESPACE_INTEGRATION . "\{$integrationName}\{$classPrefix}{$integrationName}"; 
        if (!file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - Validator padrão sendo utilizado!");
            
            $file       = __DIR__."/Rules/{$classPrefix}.php";
            $className  = self::NAMESPACE_VALIDATOR . "\{$classPrefix}";
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
    private function checkAndCreateParameters(string $integrationName):  ParametersModel
    {
        // Verifica se arquivo de configuração de validação existe
        $classPrefix = "Parameters";

        $file       = __DIR__."/IntegrationModel/{$integrationName}/{$classPrefix}{$integrationName}.php";
        $className  = self::NAMESPACE_INTEGRATION . "\{$integrationName}\{$classPrefix}{$integrationName}"; 
        if (!file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - Parameters padrão sendo utilizado!");
            
            $file       = __DIR__."/Rules/{$classPrefix}.php";
            $className  = self::NAMESPACE_PARAMETERS . "\{$classPrefix}";
        }
        
        require_once($file);
        return new $className();
    }

    /**
     * 
     */
    private function createIntegration(
        string $integrationName, 
        Validator $validator, 
        ParametersModel $parameters,
        Payload $payload
        ): AbstractIntegrationModel 
    {
        $file       = __DIR__."/IntegrationModel/{$integrationName}/{$integrationName}.php";
        $className  = self::NAMESPACE_INTEGRATION . "\{$integrationName}\{$integrationName}"; 
        if (!file_exists($file)) {
            syslog(LOG_ERR, "[HUB][ERR] - Arquivo de configuração '$integrationName' não foi configurado");
            throw new FileNotExistsException("Arquivo de configuração '$integrationName' não foi configurado");
        }

        require_once($file);
        return new $className($payload, $parameters, $validator);
    }
}