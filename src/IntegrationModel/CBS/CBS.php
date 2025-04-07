<?php

namespace IntegrationHub\IntegrationModel\CBS;

use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\Request\CurlRequest;
use IntegrationHub\Rules\Environment;
use IntegrationHub\Rules\Helper;
use IntegrationHub\Rules\Logger;

class CBS extends AbstractIntegrationModel
{
    public function build(): array {
        syslog(LOG_NOTICE, __FUNCTION__);

        $dados = [
            "documento"     => $this->buildDocument(),
            "beneficiarios" => $this->buildBeneficiarios()
        ];

        return $dados;
    }

    protected function buildDocument(): array 
    {
        Logger::message(LOG_NOTICE, __METHOD__);

        $filename = $this->getLocalFile();

        file_put_contents($filename, 'texto');
        $body = [
            "fileExtension" => 'txt',
            "fileSize"      => filesize($filename),
        ];

        // if ($willSendDocProposta) {

        //     $filename = "/tmp/arquivo_padrao.pdf";
        //     file_put_contents($filename, \BITIX\file_get_contents_curl(Helpers::getPDFLink($proposta)));

        //     $body = [
        //         "fileExtension" => 'pdf',
        //         "fileSize"      => filesize($filename),
        //     ];

        // }

        return $body;
    }

    protected function buildBeneficiarios()
    {
        Logger::message(LOG_NOTICE, __METHOD__);

        $beneficiarios      = $this->payload->getBeneficiarios();
        $corretagem         = $this->payload->getCorretagem();
        $tipoContratacao    = $this->payload->getContratacao();
        $contratante        = $this->payload->getContratante();

        $beneficiariosArr = [];
        foreach ($beneficiarios as $familia) {
            $titular = $familia[0];
            foreach ($familia as $ben) {

                if (!$this->beneficiarioAptoParaAgendamento($ben, $titular)){
                    Logger::message(LOG_NOTICE, "Beneficiario {$ben['nome']} não é apto para o agendamento");
                    continue;
                }

                $nomeFantasia = (empty($contratante['nomefantasia'])) ? $contratante['razaosocial'] : $contratante['nomefantasia'];

                $beneficiarioTratado = [
                    "nomeCorretora"         => $corretagem["corretora"]["nome"],
                    "codigoCorretora"       => $corretagem["corretora"]["identificador"],
                    "nomeVendedor"          => $corretagem["vendedor"]["nome"],
                    "cpfVendedor"           => $corretagem["vendedor"]["cpf"],
                    "emailVendedor"         => $corretagem["vendedor"]["email"],
                    "isPJ"                  => $tipoContratacao == "pj" ? true : false,
                    "nomeFantasia"          => $tipoContratacao == "pj" ? $nomeFantasia : "",
                    "telefoneEmpresa"       => $tipoContratacao == "pj" ? Helper::limparTexto($contratante["responsavel"]["tel_celular"]) : "",
                    "nome"                  => $ben["nome"],
                    "cpf"                   => Helper::limparTexto($ben["cpf"]),
                    "dataNascimento"        => $ben["data_nascimento"],
                    "sexo"                  => $ben["sexo"],
                    "genero"                => '',
                    "estadoCivil"           => $this->options->getOptionFrom("estadoCivil", $ben["estado_civil"]),
                    "escolaridade"          => '',
                    "logradouro"            => $contratante["endereco1"]["logradouro"],
                    "numero"                => $contratante["endereco1"]["numero"],
                    "bairro"                => $contratante["endereco1"]["bairro"],
                    "cep"                   => $contratante["endereco1"]["cep"],
                    "cidade"                => $contratante["endereco1"]["cidade"],
                    "uf"                    => $contratante["endereco1"]["uf"],
                    "telefone"              => $ben["tel_celular"] ?? $ben["tel_fixo"] ?? $titular["tel_celular"],
                    "email"                 => $ben["email"],
                    "nomeContratante"       => $tipoContratacao == "pj" ? $contratante["responsavel"]["nome"] : $contratante["nome"],
                    "cpfContratante"        => $tipoContratacao == "pj" ? $contratante["responsavel"]["cpf"] : $contratante["cpf"],
                    "emailContratante"      => $tipoContratacao == "pj" ? $contratante["responsavel"]["email"] : $contratante["email"],
                    "telefoneContratante"   => $tipoContratacao == "pj" ? Helper::limparTexto($contratante["responsavel"]["tel_celular"]) : Helper::limparTexto($contratante["tel_celular"] ?? $contratante["tel_celular"]),
                    "enderecoContratante"   => $contratante["endereco1"]["logradouro"],
                    "isEntrevista"          => true,
                    "parentesco"            => strtoupper($this->options->getOptionFrom("parentesco", $ben["parentesco"] ?? "0"))
                ];

                $beneficiariosArr[$ben["cpf"]] = $beneficiarioTratado;
            }
        }

        Logger::message(LOG_NOTICE, "Beneficiarios tratados com sucesso!");
        return $beneficiariosArr;
    }

    protected function buildRequestBeneficiario(array $beneficiario, array $dadosDocumentos): array
    {
        $documentTypeId = Environment::isTest() ? "947" : "974";
        $fileTypeId     = '1';

        $body = [
            "documentTypeId"    => $documentTypeId,
            "storeAsNew"        => true,
            "fileTypeId"        => $fileTypeId,
            "documentDate"      => date("Y-m-d"),
            "uploads" => [ [ "id" => $dadosDocumentos['id'] ] ],
            "keywordCollection" => [
                "keywordGuid"   => $dadosDocumentos['keywordGuid'],
                "items"         => $this->buildItems($beneficiario)
            ]
        ];

        return $body;
    }

    protected function buildItems(array $beneficiario): array {
        $arrayItems = [];
                    
        $beneficiarioKeywords = [];
    
        $idade = \BITIX\calcage(date('Y-m-d'), $beneficiario["dataNascimento"]);
        $isTest = Environment::isTest();
        //$entrevistasAnteriores = Helpers::getIDsEntrevistasAnterioresPropostasCanceladas(Helpers::limparTexto($beneficiario["cpf"]));
    
        $dePara = [
            1628 => $this->options->getOptionFrom("dadosCbs", "eqOperadora"), // EQ-Operadora
            1667 => $this->options->getOptionFrom("dadosCbs", "eqLocalOperadora"), // EQ-LocalOperadora
            1623 => $beneficiario['nomeVendedor'] , // EQ-NomeIntermediario
            1666 => Helper::limparTexto($beneficiario['cpfVendedor']), // EQ-CPFIntermediario
            1624 => $beneficiario['emailVendedor'], // EQ-EmailIntermediario
            ($isTest ? 2707 : 2697) => ($beneficiario['isPJ']) ? 'SIM' : "NÃO", // EQ - Plano Empresarial?
            ($isTest ? 2683 : 2675) => ($beneficiario['isPJ']) ? $beneficiario["nomeFantasia"] : '', // EQ-EmpresaNMFantasia
            ($isTest ? 2684 : 2676) => ($beneficiario['isPJ']) ? Helper::limparTexto($beneficiario["telefoneEmpresa"]) : '', // EQ-TelefoneEmpresa
            ($isTest ? 2685 : 2677) => '', // EQ-TelefoneEmpresa2
            1607 => ($idade < 18) ? 'SIM' : 'NÃO', // EQ-Dependente - Menor de idade, deficiente, etc.
            1608 => $beneficiario["nome"], // EQ-Nome
            1609 => Helper::limparTexto($beneficiario["cpf"]), // EQ-CPF
            1610 => $beneficiario["dataNascimento"], // EQ-DataNascimento
            1611 => $beneficiario["sexo"], // EQ-SexoBiológico
            1612 => $beneficiario["genero"], // EQ-Gênero
            1613 => $beneficiario["estadoCivil"], // EQ-EstadoCivil
            1614 => $beneficiario["escolaridade"], // EQ-Escolaridade
            1615 => $beneficiario["logradouro"], // EQ-Endereço
            1700 => $beneficiario["numero"], // EQ-Numero
            1701 => $beneficiario["bairro"], // EQ-Bairro
            1704 => str_replace(".", "", $beneficiario["cep"]), // EQ-CEP
            1702 => $beneficiario["cidade"], // EQ-Cidade
            1703 => $beneficiario["uf"], // EQ-UF
            1620 => Helper::limparTexto($beneficiario["telefone"] ?? ''), // EQ-Telefone
            ($isTest ? 2412 : 2408) => '', // EQ-Telefone2
            1616 => $beneficiario["email"], // EQ-Email
            1691 => $beneficiario["nomeContratante"], // EQ-NomeResponsavel
            1692 => Helper::limparTexto($beneficiario["cpfContratante"]), // EQ-CPFResponsavel
            1606 => $beneficiario["emailContratante"], // EQ-EmailResponsavel
            1694 => Helper::limparTexto($beneficiario["telefoneContratante"]), // EQ-TelefoneResponsavel
            ($isTest ? 2413 : 2409) => '', // EQ-TelefoneResponsavel2
            1693 => $beneficiario["enderecoContratante"], // EQ-EndereçoResponsavel
            1629 => ($beneficiario['isEntrevista']) ? 'SIM' : 'NÃO', // EQ-Entrevista?
            1618 => ($beneficiario['isEntrevista']) ? date('Y-m-d H:i:s') : "", // EQ-DataAgendamento   
            ($isTest ? 3033 : 3074) => $beneficiario['nomeCorretora'] ?? "", // EQ - CorretoraSK 
            1633 => $beneficiario["parentesco"], //EQ - Entrevista Grau Parentesco
            //($isTest ? 3019 : 3120) => $entrevistasAnteriores[0]["entrevista_id"] ?? "", //EQ - IDs Entrevistas anteriores
            ($isTest ? 3097 : 3119) => $this->propostaID ?? "", //EQ - PropostaPlanium
        ];
    
        // if (!empty($dePara[3019]) || !empty($dePara[3120])) {
        //     $dePara[1699] = $entrevistasAnteriores[0]["status"];
        // }

        foreach ($dePara as $key => $value) {
            $beneficiarioKeywords[] = [
                "typeId" => (string) $key,
                "values" => [["value" => (string) $value]]
            ];
        }

        $arrayItems[] = ["keywords" => $beneficiarioKeywords];

        return $arrayItems;
    }

    /**
     * Realiza validações para definir se beneficiario é apto para o agendamento
     * 
     * @param array $beneficiario Dados do beneficiario a ser validado
     * @param array $titular Titular do beneficiario a ser validado
     * @return bool Deve retornar true caso o beneficiario seja apto para o agendamento
     */
    protected function beneficiarioAptoParaAgendamento(array $beneficiario, array $titular): bool
    {
        return true;
    }

    public function send(array $bodyRequest): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);
        try {
            $token              = $this->getTokenForAPI();
            $beneficiarios      = $bodyRequest["beneficiarios"];
            $curl               = new CurlRequest();
            $envData            = $this->getConfig()->getEnvData();
            foreach ($beneficiarios as $ben) {
                Logger::message(LOG_NOTICE, "Enviando {$ben['nome']} para CBS...");

                $dadosDocumentos    = $this->sendDocumentAndGetData($bodyRequest["documento"], $token);
                $requestBody        = $this->buildRequestBeneficiario($ben, $dadosDocumentos);
                $curl
                    ->setEndpoint($envData["sendBeneficiary"]["endpoint"])
                    ->setMethod($envData["sendBeneficiary"]["method"])
                    ->setHeaders([ 
                        "Content-Type: application/json", 
                        "Hyland-License-Type: QueryMetering", 
                        "Authorization: Bearer {$token}"
                    ])
                    ->setBodyRequest(json_encode($requestBody))
                    ->send();

                $response = $curl->getResponse();
                $headers = $curl->getResponseHeaders();
                $dadosEntrevista[$ben["cpf"]] = [
                    "cpf"               => $ben["cpf"],
                    "status"            => "AGUARDANDO AGENDAMENTO",
                    "entrevista_id"     => $response['id'],
                    "entrevista_link"   => $envData['interview']['url'] . "{$response['id']}"
                ];

                $this->disconnectSession($dadosDocumentos["cookie"], $token);
            }

            return [ 
                "retcode"                   => 0, 
                "message"                   => "OK", 
                "dados_agendamentos"        => isset($dadosEntrevista) ? $dadosEntrevista : [],
                "aptos"                     => isset($dadosEntrevista) ? count($dadosEntrevista) : 0,
                "jaAgendadosAnteriormente"  => 0,   //TODO: Verificar fluxo de agendados anteriormente
                "documentos_agendamentos"   => [],  //TODO: Verificar fluxo de documentos
                "beneficiarios_criados"     => $beneficiarios
            ];
        } catch (\IntegrationHub\Exception\CurlRequestException $e) {
            syslog(LOG_NOTICE, "[HUB][ERR] - " . $e->getMessage());
            return [ "retcode" => -1, "message" => $e->getMessage() ];
        }
    }

    private function getTokenForAPI()
    {
        $envData = $this->getConfig()->getEnvData();
        $body = http_build_query([
            "tenant"        => "OnBase",
            "grant_type"    => "password",
            "scope"         => "evolution",
            "client_id"     => $envData["getToken"]["clientID"],
            "client_secret" => $envData["getToken"]["clientSecret"],
            "username"      => $envData["getToken"]["user"],
            "password"      => $envData["getToken"]["pass"],
        ]);

        $curl = new CurlRequest();
        $response = $curl
            ->setEndpoint($envData["getToken"]["endpoint"])
            ->setMethod($envData["getToken"]["method"])
            ->setBodyRequest($body)
            ->setHeaders(["Content-Type: application/x-www-form-urlencoded"])
            ->send();

        Logger::message(LOG_NOTICE, "Token retornado com sucesso!");
        return $response['access_token'];
    }

    private function getIdAndCookieForDocument(array $body, string $token): array 
    {
        $envData = $this->getConfig()->getEnvData();
        
        $curl = new CurlRequest();
        $curl
            ->setEndpoint($envData["uploadDocument"]["endpoint"])
            ->setMethod($envData["uploadDocument"]["method"])
            ->setBodyRequest(json_encode($body))
            ->setHeaders(["Content-Type: application/json", "Hyland-License-Type: QueryMetering", "Authorization: Bearer $token"])
            ->send();
      
        $headers = $curl->getResponseHeaders();
        $headers['Set-Cookie'] = trim($headers['Set-Cookie']);

        $parsedResponse = $curl->getResponse();
        return [
            'id'        => $parsedResponse['id'],
            'cookie'    => trim($headers['Set-Cookie']),
        ];
    }

    private function putIdForDocument(string $id, string $token): void
    {
        $envData = $this->getConfig()->getEnvData();
        
        $filename = $this->getLocalFile();
        $endpoint = str_replace('{{ID}}', $id, $envData["putIdDocument"]["endpoint"]);

        $curl = new CurlRequest();
        $curl
            ->setEndpoint($endpoint)
            ->setMethod($envData["putIdDocument"]["method"])
            ->setBodyRequest(file_get_contents($filename))
            ->setHeaders([ 
                "Content-Type: application/octet-stream", 
                "Hyland-License-Type: QueryMetering", 
                "Authorization: Bearer $token" 
            ])
            ->setAllowedMethods([ 204, 200, 201 ])
            ->send();
    }

    private function getKeywordForDocument(string $token): string 
    {
        $envData = $this->getConfig()->getEnvData();
        $tipoContratacao = $this->payload->getContratacao();
        $documentTypeId = $this->options->getOptionFrom("dadosCbs", "docType", $tipoContratacao);

        $endpoint = str_replace('{{DOC-TYPE}}', $documentTypeId, $envData["docType"]["endpoint"]);

        $curl = new CurlRequest();
        $curl
            ->setEndpoint($endpoint)
            ->setMethod($envData["docType"]["method"])
            ->setHeaders([ "Hyland-License-Type: QueryMetering", "Authorization: Bearer $token" ])
            ->send();

        return $curl->getResponse()['keywordGuid'];
    }

    private function sendDocumentAndGetData(array $bodyDocument, ?string $token = null): array
    {
        $token          = $token ? $token : $this->getTokenForAPI();
        $idAndCookie    = $this->getIdAndCookieForDocument($bodyDocument, $token);
        $this->putIdForDocument($idAndCookie["id"], $token);
        $keywordGuid    = $this->getKeywordForDocument($token);

        return [ 'id' => $idAndCookie['id'], 'cookie' => $idAndCookie['cookie'], 'keywordGuid' => $keywordGuid ];
    }

    private function disconnectSession(string $cookie, string $token)
    {
        $envData = $this->getConfig()->getEnvData();
        $curl = new CurlRequest();
        $curl
            ->setEndpoint($envData["disconnectSession"]["endpoint"])
            ->setMethod($envData["disconnectSession"]["method"])
            ->setHeaders([ 
                "Authorization: Bearer $token",
                "Content-Type: application/json",
                "Hyland-License-Type: QueryMetering",
                "Cookie: $cookie",
                "Content-Length: 0",
            ])
            ->setAllowedMethods([ 204, 200, 201 ])
            ->send();

        Logger::message(LOG_NOTICE, "Sessão desconectada: $cookie");
    }

    private function getLocalFile(): string
    {
        $tmpDir     = sys_get_temp_dir();
        $filename   = "$tmpDir/arquivo_padrao.txt";

        return $filename;
    }

    public function getType(): int
    {
        return CONN_API;
    }
}
