<?php

namespace IntegrationHub\IntegrationModel;

use IntegrationHub\Rules\Parameters;
use IntegrationHub\Rules\{Config, Validator, Payload};

if (!defined('CUSTOMDIR')) define("CUSTOMDIR", realpath(__DIR__ . '/../../../../custom'));

abstract class AbstractIntegrationModel {
	private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    protected Payload           $payload;
    protected Parameters   $parameters;
    protected Validator         $validator;
    protected Config            $config;

    public static function factory(string $integracaoName, Payload $payload, Parameters $parameters, Validator $validator, Config $config, ?string $operadora = null)
	{
		$operadora = $operadora ?? \ENVIRONMENT\hostname();

        // Formata o nome da operadora
        $operadoraName = str_replace("-", "", $operadora);
        if (is_numeric($operadoraName[0])) {
            $operadoraName = "_" . $operadoraName;
        }

        // Caminho do arquivo
        $file = CUSTOMDIR . "/{$operadora}/svc/integracao/{$integracaoName}/{$operadoraName}{$integracaoName}.php";
        
        // Nome da classe que será instanciada
        $className = self::NAMESPACE_INTEGRATION . "\\$integracaoName\\{$operadoraName}{$integracaoName}";

        if (file_exists($file)) {
            syslog(LOG_NOTICE, "[HUB] - Classe custom encontrada: $className");
            require_once($file);
        } else {
            syslog(LOG_NOTICE, "[HUB] - Classe custom não encontrada, tentando classe padrão.");

            // Classe padrão no namespace correto
            $className = self::NAMESPACE_INTEGRATION . "\\$integracaoName\\{$integracaoName}";

            if (!class_exists($className)) {
                throw new \Exception("Nenhuma implementação encontrada para {$integracaoName}.");
            }
        }

        return new $className($payload, $parameters, $validator, $config);
	}

    public function __construct(Payload $payload, Parameters $parameters, Validator $validator, Config $config)
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