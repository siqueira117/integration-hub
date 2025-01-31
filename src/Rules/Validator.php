<?php

namespace IntegrationHub\Rules;

use IntegrationHub\Exception\ValidationTypeNotExistsException;

class Validator extends AuxValidator
{
    const V_DADOS_GERAIS    = 1;
    const V_CONTRATO        = 2;
    const V_EMPRESA         = 3;
    const V_BENEFICIARIOS   = 4;
    const V_PAYLOAD         = 5;

    /**
     * Recebe dados que devem ser validados, juntamente com o 
     * tipo de validação que deve ser realizada
     * 
     * @param array $dataToValidate Array com dados que devem ser validados
     * @param int $tipoValidacao Tipo de validação que deve ser realizada, contempla cinco tipos, sendo eles:
     * - V_DADOS_GERAIS    = 1;
     * - V_CONTRATO        = 2;
     * - V_EMPRESA         = 3;
     * - V_BENEFICIARIOS   = 4;
     * - V_PAYLOAD         = 5;
     * 
     * @return array Array com todos os erros encontrados. 
     * Se o array estiver vazio, todas as validações foram realizadas sem erro.
     */
    public function validatePayload(array $dataToValidate, int $tipoValidacao): array
    {
        // TODO: Realizar validação de todo payload
        switch ($tipoValidacao) {
            case self::V_DADOS_GERAIS:
                $validations = $this->getDadosGeraisRules();
                break;
            case self::V_CONTRATO:
                $validations = $this->getContratoRules();
                break;
            case self::V_EMPRESA:
                $validations = $this->getEmpresaRules();
                break;
            case self::V_BENEFICIARIOS:
                $validations = $this->getBeneficiariosRules();
                break;
            default:
                throw new ValidationTypeNotExistsException("Tipo de validação informado [$tipoValidacao] não existe");
                break;
        }

        return $this->validate_data($dataToValidate, $validations);
    }
    // =====================================================================

    // DADOS GERAIS
    protected function getDadosGeraisRules(): array
    {
        return [
            "contrato" => [
                "type"          => "array_associative",
                "required"      => true,
            ],
            "beneficiarios" => [
                "type"          => "array_associative",
                "required"      => true,
            ],
            "propostaID" => [
                "type"  => "integer",
                "required" => true
            ]
        ];
    }
    // =====================================================================

    // EMPRESA
    protected function getEmpresaRules(): array
    {
        return [
            "cnpj" => [
                "type"      => "string",
                "required"  => true,
                "max"       => 14
            ],
            "razaosocial" => [
                "type"      => "string",
                "required"  => true
            ],
            "responsavel" => [
                "type"          => "array_associative",
                "required"      => true,
                "itens"         => [
                    "nome"  => [
                        "type"      => "string",
                        "required"  => true
                    ],
                    "cpf"   => [
                        "type"      => "string",
                        "required"  => true,
                        "max"       => 11
                    ]
                ]
            ],
            "endereco1" => [
                "type"          => "array_associative",
                "required"      => true,
                "itens"         => [
                    "cep"   => [
                        "type"  => "string",
                        "max"   => "8",
                        "required" => true
                    ],
                    "logradouro" => [
                        "type" => "string",
                        "required" => true,
                    ],
                    "numero" => [
                        "type" => "string",
                        "required" => true
                    ],
                    "bairro" => [
                        "type" => "string",
                        "required" => true
                    ]
                ]
            ]
        ];
    }
    // =====================================================================

    // CONTRATO
    protected function getContratoRules(): array
    {
        return [];
    }
    // =====================================================================

    // BENEFICIARIOS
    protected function getBeneficiariosRules(): array
    {
        return [];
    }
    // =====================================================================
}
