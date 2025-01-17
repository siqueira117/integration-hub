<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Rules\Validator;

class ValidatorSGU extends Validator
{
    private function getCustomRulesToEmpresa(): array
    {
        return [
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
    }
}
