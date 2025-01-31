<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Rules\Validator;

class ValidatorSGU extends Validator
{
    protected function getEmpresaRules(): array
    {
        $validations = parent::getEmpresaRules();
        $customValidation = [
            "porte" => [
                "type"      => "string",
                "required"  => true
            ],
            "inscricaoestadual" => [
                "type"      => "string",
                "required"  => true,
                "max"       => 45
            ],
            "inscricaomunicipal" => [
                "type"      => "string",
                "required"  => true,
                "max"       => 45
            ]
        ];

        return array_merge($validations, $customValidation); 
    }
}
