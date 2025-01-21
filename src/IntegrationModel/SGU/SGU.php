<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Request\CurlRequest;
use IntegrationHub\Exception\CurlRequestException;
use IntegrationHub\Exception\ValidationException;
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
    
        $validations = $this->validator->validatePayloadEmpresa($empresa);
        if ($validations) {
            throw new ValidationException("[PAYLOAD] Erro na validação de empresa: " . json_encode($validations));
        }
        
        $empTemp = [
            "cnpj"                      => $empresa["cnpj"],
            "razao_social"              => $empresa["razaosocial"],
            "pessoa_responsavel_nome"   => $empresa["responsavel"]["nome"],
            "pessoa_responsavel_cpf"    => $empresa["responsavel"]["cpf"],
            "tipo_societario"           => $this->parameters->getTipoSocietario($empresa["porte"]),
            "nome_fantasia"             => $empresa["nomefantasia"] ?? $empresa["razaosocial"],
            "inscricao_estadual"        => $empresa["inscricaoestadual"],
            "inscricao_municipal"       => $empresa["inscricaomunicipal"],
            "cnae"                      => $this->buildCNAE($empresa["cnae"]),
            "enderecos"                 => $this->buildEnderecos($empresa)
        ];
        
        return $empTemp;
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

    private function buildEnderecos(array $empresa): array 
    {
        return [
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
        ];
    }

    public function getType(): int { return CONN_API; }

    public function send(array $bodyRequest): array 
    {
        syslog(LOG_NOTICE, __METHOD__);
        if (PHP_SAPI === 'cli') print_r("Realizando requisição...\n");

        $envData = $this->getConfig()->getEnvData();
        
        $curl       = new CurlRequest();
        $response   = $curl
            ->setEndpoint($envData["endpoint"])
            ->setMethod($envData["method"])
            ->setHeaders([
                "Content-Type application/json", 
                "Authorization: Bearer {$envData['token']}"
            ])
            ->setBodyRequest(json_encode($bodyRequest))
            ->send();
        
        if (array_key_exists("msg_erro", $response)) {
            throw new CurlRequestException("ERRO ao incluir proposta no SGU");
        }

        return $response;
    }       
}