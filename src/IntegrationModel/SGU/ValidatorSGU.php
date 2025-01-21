<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Rules\Validator;

class ValidatorSGU extends Validator
{
    protected function getRulesToEmpresa(): ?array
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
