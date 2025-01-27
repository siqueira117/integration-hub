<?php

namespace IntegrationHub\IntegrationModel\Parameters;

use IntegrationHub\Exception\OptionIDNotExists;
use IntegrationHub\Exception\OptionNotExists;
use IntegrationHub\Exception\RequiredOptionNotInformed;

class Parameters {
    private $options;

    public function __construct(?array $options = null)
    {
        if ($options) {
            $this->validateOptions($options);
            $this->options = $options;
        }
    }

    private function validateOptions(array $options): void 
    {
        $requiredOptions = $this->getRequiredOptions();
        foreach ($requiredOptions as $optionIndex => $optionName) {
            if (!array_key_exists($optionName, $options)) {
                throw new RequiredOptionNotInformed("DE-PARA obrigatório não foi informado: $optionName");
            }

            if (!array_key_exists("options", $options[$optionName])) {
                throw new RequiredOptionNotInformed("DE-PARA obrigatório não foi informado corretamente: $optionName | Lista de opções faltando");
            }
        }
    }

    private function getOptions(string $optionName): array 
    {
        if (!array_key_exists($optionName, $this->options)) {
            throw new OptionNotExists("DE-PARA não existe: $optionName");
        }
        
        return $this->options[$optionName];
    }

    public function getOptionFrom(string $optionName, $optionToSearch, $parameter = null) 
    {
        $optionsList = $this->getOptions($optionName);
        if (!array_key_exists($optionToSearch, $optionsList["options"])) {
            if (PHP_SAPI === 'cli') print_r("ID não existe no de-para informado: {{$optionName}}->{{$optionToSearch}}");
            
            if (array_key_exists("default", $optionsList)) {
                return $optionsList["default"];
            }

            throw new OptionIDNotExists("ID não existe no de-para informado: {{$optionName}}->{{$optionToSearch}}");
        }

        // Caso no de-para exista um array de opções para a mesma categoria
        // Por exemplo: Filho - 10, Filha 20
        if (is_array($optionsList["options"][$optionToSearch])) {
            if (!$parameter) {
                throw new RequiredOptionNotInformed("DE-PARA obrigatório não foi informado corretamente: $optionName | Parametros faltando");
            }

            if (!array_key_exists($parameter, $optionsList["options"][$optionToSearch])) {
                if (PHP_SAPI === 'cli') print_r("Parametro opcional não encontrado no DE-PARA: {{$optionName}}->{{$optionToSearch}}->{{$parameter}}");

                throw new OptionIDNotExists("Parametro opcional não encontrado no DE-PARA: {{$optionName}}->{{$optionToSearch}}->{{$parameter}}");
            }

            return $optionsList["options"][$optionToSearch][$parameter];
        }
        // =================

        return $optionsList["options"][$optionToSearch];
    }

    public function setOptions(array $options): void 
    {
        $this->validateOptions($options);
        $this->options = $options;
    }

    public function getRequiredOptions(): array 
    {
        return ["estadoCivil", "parentesco"];
    }
}