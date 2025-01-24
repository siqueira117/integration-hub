<?php

namespace IntegrationHub\IntegrationModel\Parameters;

use IntegrationHub\Exception\OptionIDNotExists;
use IntegrationHub\Exception\OptionNotExists;
use IntegrationHub\Exception\RequiredOptionNotInformed;

class ParametersModel {
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

    public function getOptionFrom(string $optionName, $optionToSearch) 
    {
        $optionsList = $this->getOptions($optionName);
        if (!array_key_exists($optionToSearch, $optionsList["options"])) {
            if (PHP_SAPI === 'cli') print_r("ID não existe no de-para informado: {{$optionName}}->{{$optionToSearch}}");
            
            if (array_key_exists("default", $optionsList)) {
                return $optionsList["default"];
            }

            throw new OptionIDNotExists("ID não existe no de-para informado: {{$optionName}}->{{$optionToSearch}}");
        }

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