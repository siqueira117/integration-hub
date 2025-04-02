<?php

namespace IntegrationHub\IntegrationModel\FACIL;

use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\Request\CurlRequest;

class FACIL extends AbstractIntegrationModel
{
    protected function build(): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);
        if (PHP_SAPI === 'cli') print_r("[HUB] - Construindo bodyRequest...\n");

        $tipo_contratacao = $this->payload->getContratacao();

        $proposal = [];

        if ($tipo_contratacao == 'pj') {
            $proposal = [
                'numero_proposta'        => $this->payload->getPropostaID(),
                'data_atual'             => $this->payload->getDataAtual(),
                'proposta_url'           => $this->payload->getPdfUrl(),
                'contrato'               => $this->buildContrato(),
                'corretagem'             => $this->buildCorretagem(),
                'empresa'                => $this->buildEmpresa(),
                'beneficiarios'          => $this->buildBeneficiarios(),
                'planos'                 => $this->buildPlanos(),
                // 'aditivos'               => $this->getAditivos(
                //     $proposta["beneficiarios"][0][0], 
                //     $proposta["contrato"], 
                //     date("m/Y"))
            ];
        }

        return $proposal;
    }

    protected function buildContrato(): array
    {
        return [
            'total_valor'           => $this->payload->getValorContrato() ?? '',
            'total_beneficiarios'   => intval($this->payload->getNumVidas() ?? 0),
            'data_vigencia'         => $this->payload->getDataVigencia(),
            'vencimento'            => intval($this->payload->getDataVigencia('d'))
        ];
    }

    protected function buildCorretagem(): array
    {
        $corretagem = $this->payload->getCorretagem();

        return [
            'vendedor'       => [
                "identificador" => (int)$corretagem['vendedor']["identificador"],
                "cpf"           => $corretagem['vendedor']["cpf"],
                "nome"          => $corretagem['vendedor']["nome"],
                "email"         => $corretagem['vendedor']["email"],
                "telefone"      => $corretagem['vendedor']["telefone"]
            ],
            'corretora'      => [
                "identificador" => (int)$corretagem['corretora']["identificador"],
                "cnpj"          => $corretagem['corretora']["cnpj"],
                "nome"          => $corretagem['corretora']["nome"],
                "email"         => $corretagem['corretora']["email"]
            ]

        ];
    }

    protected function buildEmpresa(): array
    {
        $empresa = $this->payload->getContratante();

        return [
            'razaosocial'       => $empresa['razaosocial']              ?? '',
            'nomefantasia'      =>
            substr(
                ($empresa['nomefantasia'] && $empresa['nomefantasia'] !== "")
                    ? $empresa['nomefantasia']
                    : $empresa['razaosocial'],
                0,
                30
            ),
            'cnpj'              => $empresa['cnpj']                     ?? '',
            'inscricaoestadual' => $empresa['inscricaoestadual']        ?? '',
            'cnae'              => $empresa['cnae']                     ?? '',
            'cnj_codigo'        => $empresa['cnj_codigo']               ?? '',
            'mei'               => $empresa['mei']                      ?? '',
            'data_vencimento'   => (string)$this->payload->getDataVigencia('d'),
            'endereco'          => [
                'cep'         => $empresa['endereco1']['cep']           ?? '',
                'logradouro'  => $empresa['endereco1']['logradouro']    ?? '',
                'numero'      => $empresa['endereco1']['numero']        ?? '',
                'complemento' => substr($empresa['endereco1']['complemento'] ?? '', 0, 40),
                'bairro'      => $empresa['endereco1']['bairro']        ?? '',
                'cidade'      => $empresa['endereco1']['cidade']        ?? '',
                'uf'          => $empresa['endereco1']['uf']            ?? ''
            ],
            'responsavel'       => [
                'nome'        => $empresa['responsavel']['nome']        ?? '',
                'cpf'         => $empresa['responsavel']['cpf']         ?? '',
                'email'       => $empresa['responsavel']['email']       ?? '',
                'tel_fixo'    => $empresa['responsavel']['tel_fixo']    ?? '0000000000',
                'tel_celular' => $empresa['responsavel']['tel_celular'] ?? '0000000000'
            ]
        ];
    }

    protected function buildBeneficiarios(): array
    {
        $beneficiarios = $this->payload->getBeneficiarios();
        foreach ($beneficiarios as $famKey => $familia) {
            $titular = $familia[0];
            foreach ($familia as $benKey => $beneficiario) {
                $newBeneficiarios[$famKey][$benKey] = [
                    'nome'            => $beneficiario['nome']                                   ?? '',
                    'cpf'             => $beneficiario['cpf']                                    ?? '',
                    'email'           => $beneficiario['email']                                  ?? '',
                    'data_nascimento' => $beneficiario['data_nascimento']                        ?? '',
                    'plano'           => [
                        'codigo_plano'   => (int)$beneficiario['plano_id'],
                        // 'data_tblpreco'  => $titular['produtos']['saude']['data_tblpreco']  ?? '',
                        // 'nome'           => $titular['produtos']['saude']['nome']           ?? '',
                        // 'reg_ans'        => $titular['produtos']['saude']['reg_ans']        ?? '',
                        // 'acomodacao'     => $titular['produtos']['saude']['acomodacao']     ?? '',
                        // 'coparticipacao' => $titular['produtos']['saude']['coparticipacao'] ?? '',
                        // 'segmentacao'    => $titular['produtos']['saude']['segmentacao']    ?? '',
                        // 'abrangencia'    => $titular['produtos']['saude']['abrangencia']    ?? '',
                        // 'valor'          => $titular['produtos']['saude']['valor']          ?? '',
                        // 'codigo_premium'   => (int)$titular['record_plano']['codigo_premium']   ?? ''
                    ],
                    'sexo'            => $beneficiario['sexo']                                   ?? '',
                    'estado_civil'    => $this->options->getOptionFrom('estadoCivil', $beneficiario['estado_civil']),
                    'nome_mae'        => $beneficiario['nome_mae']                               ?? '',
                    'tipo'            => ($benKey === 0) ? "1" : "2",
                    // 'grau_parentesco' => (int)$beneficiario["parentesco"] ?? 10,
                    'codigo_fam'      => $titular["cpf"],
                    'tel_fixo'        => $beneficiario['tel_fixo']                               ?? '0000000000',
                    'tel_celular'     => $beneficiario['tel_celular']                            ?? '0000000000',
                    'endereco_residencial' => [
                        'cep'         => $titular['endereco1']['cep']                       ?? '',
                        'logradouro'  => $titular['endereco1']['logradouro']                ?? '',
                        'numero'      => $titular['endereco1']['numero']                    ?? '',
                        'complemento' => substr($titular['endereco1']['complemento'] ?? '', 0, 40),
                        'bairro'      => $titular['endereco1']['bairro']                    ?? '',
                        'cidade'      => $titular['endereco1']['cidade']                    ?? '',
                        'uf'          => $titular['endereco1']['uf']                        ?? '',
                        'codigo_ibge' => $titular['endereco1']['codigo_ibge']               ?? ''
                    ],
                    'peso'            => $beneficiario['peso']                                   ?? '',
                    'altura'          => $beneficiario['altura']                                 ?? '',
                    'decsau'          => $beneficiario['decsau']                                 ?? '',
                    //'tipo_carencia'   => $beneficiario['tipo_carencia'] 						 ?? '',


                ];
                if (array_key_exists("rg_numero", $beneficiario)) {
                    $newBeneficiarios[$famKey][$benKey]["rg_numero"] = $beneficiario['rg_numero'];
                }

                if (array_key_exists("cns", $beneficiario)) {
                    $newBeneficiarios[$famKey][$benKey]["cns"] = $beneficiario["cns"];
                }

                // if (array_key_exists("dropdown0", $beneficiario)) {
                //     $newBeneficiarios[$famKey][$benKey]["orgao_emissor"] = $beneficiario['dropdown0'];
                // }

                // if (array_key_exists("naturalidade", $beneficiario)) {
                //     $newBeneficiarios[$famKey][$benKey]["naturalidade"] = $beneficiario["naturalidade"] . "/" . $titular["dropdown1"];
                // }

                if ($benKey !== 0) {
                    $newBeneficiarios[$famKey][$benKey]["grau_parentesco"] = intval($beneficiario["parentesco"] ?? 10);
                }
            }
        }

        return $newBeneficiarios;
    }

    protected function buildPlanos(): array {
        $vigencia   = $this->payload->getDataVigencia('m/Y');
        $titulares  = [];
        $planos     = [];
        foreach ($this->payload->getBeneficiarios() as $familia) {
            $titular = $familia[0];
            if (!array_key_exists($titular["plano_id"], $titulares)) {
                $titulares[$titular["plano_id"]] = $familia[0];
                $planos[] = [
                    'codigo'      => (int)$titular["plano_id"],
                    'tabelas_preco'   => [
                        [
                            'vigencia'    => $vigencia,
                            'faixas'      => $this->getFaixasPlanos($titular),
                        ]
                    ]
                    // 'grupo_carencia' => 4
                ];                
            }
        }

        return $planos;
    }

    protected function getFaixas(): array 
    {
        $faixas = [
            [
                "inicial" => 0,
                "final" => 18
            ],
            [
                "inicial" => 19,
                "final" => 23
            ],
            [
                "inicial" => 24,
                "final" => 28
            ],
            [
                "inicial" => 29,
                "final" => 33
            ],
            [
                "inicial" => 34,
                "final" => 38
            ],
            [
                "inicial" => 39,
                "final" => 43
            ],
            [
                "inicial" => 44,
                "final" => 48
            ],
            [
                "inicial" => 49,
                "final" => 53
            ],
            [
                "inicial" => 54,
                "final" => 58
            ],
            [
                "inicial" => 59,
                "final" => 100
            ],
        ];

        return $faixas;
    }

    protected function getFaixasPlanos(array $titular): array 
    {
        $codigoProduto = (int)$titular["plano_id"];
        $recordPreco = $titular["record_preco"];
        $contrato = $this->payload->getContrato();

        $faixas = $this->getFaixas();

        if (array_key_exists("taxa_extra", $contrato) && $contrato["taxa_extra"]) {
            $taxa_extra = (float)$contrato["taxa_extra"];
        } else {
            $taxa_extra = 0.0;
        }

        $temp = [];
        foreach ($recordPreco as $preco) {
            if ($preco["btxplano"] == $codigoProduto) {
                foreach($preco["preco"] as $key => $valor) {
                    array_push($temp, [
                        'idade_inicial'     => $faixas[$key]["inicial"],
                        'idade_final'       => $faixas[$key]["final"],
                        'valor_adesao'      => $taxa_extra,
                        'valor_mensalidade' => $preco["preco"][$key],
                    ]); 
                }
            }
        }
        return $temp;
    }

    public function send(array $bodyRequest): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);
        try {
            $curl = new CurlRequest();
            $envData = $this->getConfig()->getEnvData();

            // Envia a Proposta
            $basic = base64_encode($envData["sendProposal"]["user"] . ":" . $envData["sendProposal"]["pass"]);
            $response = $curl
                ->setEndpoint($envData["sendProposal"]["endpoint"])
                ->setMethod($envData["sendProposal"]["method"])
                ->setBodyRequest(json_encode($bodyRequest))
                ->setHeaders(["Content-Type: application/json", "Authorization: Basic $basic"])
                ->send();
            // ============================

            return ["retcode" => 0, "message" => $response];
        } catch (\IntegrationHub\Exception\CurlRequestException $e) {
            syslog(LOG_NOTICE, "[HUB][ERR] - " . $e->getMessage());
            return ["retcode" => -1, "message" => $e->getMessage()];
        }
    }

    public function getType(): int
    {
        return CONN_API;
    }
}
