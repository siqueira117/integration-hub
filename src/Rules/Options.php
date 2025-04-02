<?php

namespace IntegrationHub\Rules;

use IntegrationHub\Exception\OptionIDNotExists;
use IntegrationHub\Exception\OptionNotExists;
use IntegrationHub\Exception\RequiredOptionNotInformed;

if (!defined('CUSTOMDIR')) define("CUSTOMDIR", realpath(__DIR__ . '/../../../../custom'));

class Options {
    private const NAMESPACE_INTEGRATION = "\\IntegrationHub\IntegrationModel";
    protected $requiredOptions = ["estadoCivil", "parentesco"];
    private $options;

    public static function factory(string $integracaoName, ?string $operadora = null, ?array $options = null)
	{
		$operadora = $operadora ?? \ENVIRONMENT\hostname();

        // Formata o nome da operadora
        $operadoraName = str_replace("-", "", $operadora);
        if (is_numeric($operadoraName[0])) {
            $operadoraName = "_" . $operadoraName;
        }

        // Caminho do arquivo
        $file = CUSTOMDIR . "/{$operadora}/svc/integracao/{$integracaoName}/{$operadoraName}Options{$integracaoName}.php";
        
        // Nome da classe que será instanciada
        $className = self::NAMESPACE_INTEGRATION . "\\$integracaoName\\{$operadoraName}Options{$integracaoName}";

        if (file_exists($file)) {
            Logger::message(LOG_NOTICE, "Classe custom encontrada: $className");
            require_once($file);
        } else {
            // Classe padrão no namespace correto
            $className = self::NAMESPACE_INTEGRATION . "\\$integracaoName\\Options{$integracaoName}";

            Logger::message(LOG_NOTICE, "Classe custom não encontrada, tentando classe padrão: $className");

            if (!class_exists($className)) {
                throw new \Exception("Nenhuma implementação encontrada para {$integracaoName} - "  . __CLASS__);
            }
        }

        return new $className($options);
	}

    public function __construct(?array $options = null)
    {
        if ($options) {
            $this->validateOptions($options);
            $this->options = $options;
        }
    }

    /**
     * Valida as opções [de-para] de acordo com as definidas em $this->requiredOptions.
     * 
     * @param array $options Opções a serem validadas de acordo com as regras definidas
     * 
     * @throws RequiredOptionNotInformed Caso um campo necessário não exista nas opções passadas
     * 
     * @return void
     */
    private function validateOptions(array $options): void 
    {
        $requiredOptions = $this->getRequiredOptions();
        if ($requiredOptions) {
            foreach ($requiredOptions as $optionIndex => $optionName) {
                if (!array_key_exists($optionName, $options)) {
                    throw new RequiredOptionNotInformed("DE-PARA obrigatório não foi informado: $optionName");
                }
    
                if (!array_key_exists("de-para", $options[$optionName])) {
                    throw new RequiredOptionNotInformed("DE-PARA obrigatório não foi informado corretamente: $optionName | Lista de opções faltando");
                }
            }
        }
    }

    /**
     * Verifica se determinado de-para existe na lista passada, se existir, retorna o de-para da opção
     * 
     * @param string $optionName Opção a ser verificada
     * 
     * @throws OptionNotExists Caso a opção não seja encontrada
     * 
     * @return array Dados do de-para da opção informada
     */
    private function getOptions(string $optionName): array 
    {
        if (!array_key_exists($optionName, $this->options)) {
            throw new OptionNotExists("DE-PARA não existe: $optionName");
        }
        
        return $this->options[$optionName];
    }

    /**
     * Verifica se determinada opção existe no de-para informado
     * 
     * @param string $optionName Nome do de-para em que a opção deve ser buscada
     * @param mixed $optionToSearch Opção a ser retornada do de-para
     * @param mixed $parameter Caso informado, verifica se opção buscado é um array de subopções e caso o valor exista, retorna a opção.
     * Por exemplo:
     * 
     * ID
     * 1 => [
     *  "M" => "10",
     *  "F" => "20"
     * ]
     * 
     * Nesse caso, $optionToSearch seria 1 e $parameter seria "M" ou "F".
     * 
     * @throws OptionIDNotExists Caso opção no de-para não seja encontrada
     * @throws RequiredOptionNotInformed Se o de-para for um array e $parameter não tiver sido informado
     * 
     * @return mixed Valor encontrado no de-para
     */
    public function getOptionFrom(string $optionName, $optionToSearch, $parameter = null) 
    {
        $optionsList = $this->getOptions($optionName);
        if (!array_key_exists($optionToSearch, $optionsList["de-para"])) {
            if (PHP_SAPI === 'cli') print_r("[HUB] - ID não existe no de-para informado: {{$optionName}}->{{$optionToSearch}}");
            
            if (array_key_exists("default", $optionsList)) {
                return $optionsList["default"];
            }

            Logger::message(LOG_ERR, "ID não existe no de-para informado: {{$optionName}}->{{$optionToSearch}}");
            throw new OptionIDNotExists("ID não existe no de-para informado: {{$optionName}}->{{$optionToSearch}}");
        }

        // Caso no de-para exista um array de opções para a mesma categoria
        // Por exemplo: Filho - 10, Filha 20
        if (is_array($optionsList["de-para"][$optionToSearch])) {
            if (!$parameter) {
                Logger::message(LOG_ERR, "DE-PARA obrigatório não foi informado corretamente: $optionName | Parametros faltando");
                throw new RequiredOptionNotInformed("DE-PARA obrigatório não foi informado corretamente: $optionName | Parametros faltando");
            }

            if (!array_key_exists($parameter, $optionsList["de-para"][$optionToSearch])) {
                if (PHP_SAPI === 'cli') print_r("[HUB] - Parametro opcional não encontrado no DE-PARA: {{$optionName}}->{{$optionToSearch}}->{{$parameter}}");

                Logger::message(LOG_ERR, "Parametro opcional não encontrado no DE-PARA: {{$optionName}}->{{$optionToSearch}}->{{$parameter}}");
                throw new OptionIDNotExists("Parametro opcional não encontrado no DE-PARA: {{$optionName}}->{{$optionToSearch}}->{{$parameter}}");
            }

            return $optionsList["de-para"][$optionToSearch][$parameter];
        }
        // =================

        return $optionsList["de-para"][$optionToSearch];
    }

    /**
     * 'Seta' as opções informadas e as valida
     * 
     * @see Options::validateOptions Valida as opções
     * 
     * @param array $options Opções a serem guardadas
     * 
     * @return void
     */
    public function setOptions(array $options): void 
    {
        $this->validateOptions($options);
        $this->options = $options;
    }

    /**
     * Retorna as regras de opções
     * 
     * @return array Regras das opções a serem informadas
     */
    protected function getRequiredOptions(): array 
    {
        return $this->requiredOptions;
    }
}