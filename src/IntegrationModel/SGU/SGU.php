<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\IntegrationModel\AbstractIntegrationModel;

class SGU extends AbstractIntegrationModel {
    public function build(): array 
    {
        syslog(LOG_NOTICE, __METHOD__);
        
        $request = [
            "empresa" => $this->payload->getContratacao() === "pj" ? $this->buildEmpresa() : null
        ];
        
        return $request;
    }

    private function buildEmpresa(): array 
    {
        syslog(LOG_NOTICE, __METHOD__);
        $empresa = $this->payload->getEmpresa(); 
    
        $this->validator->validatePayloadEmpresa();
        
        $empTemp = [
            "cnpj"                      => $empresa["cnpj"],
            "razao_social"              => $empresa["razaosocial"],
            "pessoa_responsavel_nome"   => $empresa["responsavel"]["nome"],
            "pessoa_responsavel_cpf"    => $empresa["responsavel"]["cpf"],
            "tipo_societario"           => $this->parameters->getTipoSocietario($empresa["porte"]),
            "nome_fantasia"             => $empresa["nomefantasia"] ?? $empresa["razaosocial"],
            "inscricao_estadual"        => "",
            "inscricao_municipal"       => "",
            "cnae"                      => $this->buildCNAE($empresa["cnae"]),
            "enderecos"                 => $this->buildEnderecos($empresa)
        ];           
    }

    private function buildCNAE(string $cnae): array 
    {
        return [
            [
                "codigo"            => $cnae,
                "data_inic_vigen"   => $this->payload->getDataVigencia("d/m/Y"),
                "data_fim_vigen"    => null
            ]
        ];
    }

    private function buildEnderecos(array $entidade): array 
    {
        [
            [
                "cep"               => $empresa["endereco1"]["cep"],
                "logradouro"        => $empresa["endereco1"]["logradouro"],
                "numero"            => $empresa["endereco1"]["numero"],
                "complemento"       => (string)$empresa["endereco1"]["complemento"] ?? "",
                "bairro"            => $empresa["endereco1"]["bairro"],
                "cidade"            => $empresa["endereco1"]["cidade"],
                "uf"                => $empresa["endereco1"]["uf"],
                "ponto_referencia"  => null,
                "caixa_postal"      => null,
                "contatos"          => [
                    "nome"      => substr($empresa["responsavel"]["nome"], 0, 25), #Verificar
                    "telefone"  => $empresa["responsavel"]["tel_fixo"] ?? "", #Verificar
                    "celular"   => $empresa["responsavel"]["tel_celular"] ?? "", #Verificar
                    "email"     => $empresa["responsavel"]["email"] #Verificar
                ]
            ]
        ]
    }
}