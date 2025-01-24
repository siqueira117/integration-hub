<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Request\CurlRequest;
use IntegrationHub\Exception\CurlRequestException;
use IntegrationHub\Exception\ValidationException;
use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\Rules\Helper;

class SGU extends AbstractIntegrationModel {
    public function build(): array 
    {
        syslog(LOG_NOTICE, __METHOD__);
        
        if ($this->payload->getContratacao() === "ad") {}

        $request = [
            "numero_proposta"   => substr((string)$this->payload->getPropostaID(), -10),
            "empresa"           => $this->payload->getContratacao() === "pj" ? $this->buildEmpresa() : null,
            "contrato_venda"    => $this->buildContratoVenda()
        ];
        
        return $request;
    }

    private function buildContratoVenda(): array 
    {
        syslog(LOG_NOTICE, __METHOD__);

        $contratoVenda = [];
        if ($this->payload->getContratacao() === "pf" || $this->payload->getContratacao() === "pj") {
            $ordemFamilia   = 1;
            $titular        = $this->payload->getTitular($ordemFamilia);
            $tipoProduto    = $this->payload->getTipoProduto();
            
            $contratoVenda[] = [
                "codigo_plano"          => (string)$titular["produtos"][$tipoProduto]["reg_ans"],
                "data_inicio_vigencia"  => $this->payload->getDataVigencia("d/m/Y"),
                "dia_vencimento_fatura" => $this->payload->getDataVigencia("d"),
                "quant_mes_renovacao"   => "24",
                "codigo_vendedor"       => $this->payload->getCorretagem()["vendedor"]["identificador"] ?? null,
                //"contratante_agrupador" => null,
                //"desconto_mensalidade"  => null,
                "data_assinatura"       => $this->payload->getDataAssinatura("d/m/Y"),
                //"copart_opcao_ini"      => null,
                //"rateio"                => [],
                "beneficiarios"         => $this->buildBeneficiarios()
            ];
        }
        
        return $contratoVenda;
    }

    // TODO: Adicionar consulta a API de cep
    private function buildBeneficiarios(): array 
    {
        syslog(LOG_NOTICE, __METHOD__);

        $beneficiarios          = $this->payload->getBeneficiarios();
        $beneficiariosRequest   = [];
        foreach ($beneficiarios as $familiaID => $familia) {
            $titular = $familia[0];
            foreach ($familia as $benID => $ben) {
                $ibge               = array_key_exists("endereco1", $ben) ? $ben["endereco1"]["codigo_ibge"] : $titular["endereco1"]["codigo_ibge"];
                $cidadeNaturalidade = $ibge == -1 ? "." : $ibge;
                
                $benTemp = [
                    "usuario_cartao"            => null,
                    "nome"                      => $ben["nome"],
                    "sexo"                      => $ben["sexo"],
                    "nome_social"               => null,
                    "genero_social"             => null,
                    "data_nasc"                 => date("d/m/Y", strtotime($ben["data_nascimento"])),
                    "nome_mae"                  => $ben["nome_mae"],
                    //"nome_pai"          => null,
                    "estado_civil"              => $this->parameters->getOptionFrom("estadoCivil", $ben["estado_civil"]),
                    "pais"                      => "32", //BRASIL
                    "local_atendimento"         => null,
                    "tipo_colaborador"          => null,
                    "matricula"                 => null,
                    "data_admissao"             => $benID === 0 ? "10/10/2020" : null,
                    "centro_custo"              => null,
                    "data_solic_inclusao"       => $this->payload->getDataVigencia("d/m/Y"),
                    "data_inic_vigencia"        => $this->payload->getDataVigencia("d/m/Y"),
                    "grau_dependencia"          => $benID === 0 ? "00" : $this->parameters->getOptionFrom("parentesco", $ben["parentesco"]),
                    "titular"                   => $benID === 0 ? true : false,
                    "titular_cartao"            => null,
                    "titular_nascimento"        => date("d/m/Y", strtotime($titular["data_nascimento"])),
                    "titular_dat_inic_vigen"    => $this->payload->getDataVigencia("d/m/Y"),
                    "titular_sexo"              => $titular["sexo"],
                    "data_adocao"               => null,
                    "insc_apos_titular"         => false,
                    "origem"                    => "P",
                    "cidade_naturalidade"       => $cidadeNaturalidade,
                    "cartao_plano_anterior"     => null,
                    "data_excl_plano_anterior"  => null,
                    "ex_empregado"              => null,
                    "plano_anterior_era_unimed" => null,
                    "codigo_plano_anterior"     => null,
                    "inclusao_portabilidade"    => false,
                    "novas_coberturas"          => false,
                    "carencias_cumpridas"       => false,
                    "portabilidade_determ_ans"  => false,
                    "exige_ds"                  => null,
                    "preencheu_ds"              => array_key_exists("decsau", $ben) ? true : false,
                    "mora_com_titular"          => null,
                    "cobra_taxa_insc"           => null,
                    //"tipo_integracao"           => null,
                    "documentos"                => $this->buildDocumentos($ben),
                    "enderecos"                 => $this->buildDocumentosBeneficiario($titular, $ben),
                    "servicos_adicionais"       => [],
                    "ocupacoes"                 => [],
                    "cpt"                       => []
                ];

                $beneficiariosRequest[] = $benTemp;
            }
        }

        return $beneficiariosRequest;
    }

    // TODO: Implementar envio de anexos
    private function buildDocumentos(array $beneficiary): array 
    {

        $documents = [];
        if ($beneficiary["rg_numero"]) {
            $documents[] = [
                "numero"            => Helper::tiraSimbolosELetras($beneficiary["rg_numero"]),
                "tipo_documento"    => "1",
                "orgao_emissor"     => "1",
                "data_expedicao"    => !empty($beneficiary["rg_data"]) ? date("d/m/Y", strtotime($beneficiary["rg_data"])) : null
            ];
        }

        if ($beneficiary["cpf"]) {
            $documents[] = [
                "numero"            => Helper::tiraSimbolos($beneficiary["cpf"]),
                "tipo_documento"    => "2",
                "orgao_emissor"     => null,
                "data_expedicao"    => null
            ];
        }

        $cns = $beneficiary["cns"] ?? null;
        if ($cns) {
            $documents[] = [
                "numero"            => Helper::tiraSimbolos(str_replace(" ", "", $cns)),
                "tipo_documento"    => "7",
                "orgao_emissor"     => null,
                "data_expedicao"    => null
            ];
        }

        return $documents;
    }

    private function buildDocumentosBeneficiario(array $titular, array $beneficiary): array
    {
        $addresses = [];
        $addressCount = 1;
        while ($addressCount <= 2) {
            $endereco = $dependente["endereco" . $addressCount] ?? $beneficiary["endereco" . $addressCount];
            if ($endereco) {

                $numeroEndereco = substr(preg_replace('/[^0-9]/', '', $endereco["numero"]), 0, 5); //maximo 5 numeros
                if (empty($numeroEndereco)) {
                    $numeroEndereco = 'S/N';
                }

                $address = [
                    "cep"           => $endereco["cep"],
                    "logradouro"    => $endereco["logradouro"],
                    "numero"        => $numeroEndereco,
                    "complemento"   => array_key_exists("complemento", $endereco) ? Helper::tiraSimbolos(substr($endereco["complemento"], 0, 30)) : "",
                    "cidade"        => Helper::tiraSimbolos($endereco["cidade"]),
                    "bairro"        => $endereco["bairro"],
                    "uf"            => $endereco["uf"],
                    "finalidades"   => [
                        ["tipo" => "C"],
                        ["tipo" => "F"]
                    ],
                    "contatos"      => [
                        [
                            "valor"         => $beneficiary["email"] ? $beneficiary["email"] : "",
                            "tipo_contato"  => "E",
                            "assunto"       => "11"
                        ]
                    ],
                    "tipo_endereco"     => "1",
                    "ponto_referencia"  => null,
                    "caixa_postal"      => null
                ];

                $telCelular = $beneficiary["tel_celular"] ?? $titular["tel_celular"] ?? null;
                if ($telCelular) {
                    $address["contatos"][] = [
                        "valor"         => Helper::limparTexto($telCelular),
                        "tipo_contato"  => "C", //CELULAR
                        "assunto"       => "11" //GERAL
                    ];
                }

                $telFixo = $beneficiary["tel_fixo"] ?? $titular["tel_fixo"] ?? null;
                if ($telFixo) {
                    $address["contatos"][] = [
                        "valor"         => Helper::limparTexto($telFixo),
                        "tipo_contato"  => "T", //FIXO
                        "assunto"       => "11" //GERAL
                    ];
                }

                if (!empty($addresses)) {
                    $first = $addresses[0];
                    if ($first["cep"] != $address["cep"]) {
                        $addresses[] = $address;
                    }
                } else {
                    $addresses[] = $address;
                }
            }

            $addressCount += 1;
        }

        return $addresses;    
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