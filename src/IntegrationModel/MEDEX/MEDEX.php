<?php

namespace IntegrationHub\IntegrationModel\MEDEX;

use IntegrationHub\Exception\CurlRequestException;
use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\Request\CurlRequest;

class MEDEX extends AbstractIntegrationModel {
    public function build(): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);
        if (PHP_SAPI === 'cli') print_r("Construindo bodyRequest...\n");

        $body = [
            "numero_proposta"   => $this->payload->getPropostaID(),
            "data_assinatura"   => $this->payload->getDataAssinatura(),
            "contrato"          => $this->buildContrato(),
            "beneficiarios"     => $this->buildBeneficiarios(),
        ];

        return $body;
    }

    private function buildContrato(): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);
        
        $produto = $this->payload->getTipoProduto();
        $titular = $this->payload->getTitular(1);

        $arrContrato[$produto] = [
            "nome"      => $titular['produtos'][$produto]['nome'],
            "reg_ans"   => $titular['produtos'][$produto]['reg_ans'],
        ];

        return $arrContrato;
    }

    private function buildBeneficiarios(): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);

        $beneficiarios = $this->payload->getBeneficiarios();
        $beneficiariosRequest = [];
        foreach ($beneficiarios as $famKey => $familia) {
            $titular = $familia[0];
            foreach ($familia as $benKey => $ben) {
                $tempBen = [
                    "seqFamilia"        => (string)($famKey+1),
                    "nome"              => $ben['nome'],
                    "cpf"               => $ben['cpf'],
                    "email"             => $ben['email'],
                    "sexo"              => $ben['sexo'],
                    "parentesco"        => $ben['parentesco'] ?? 0, // 0 quando é titular
                    "data_nascimento"   => $ben['data_nascimento'],
                    "tel_celular"       => $ben['tel_celular'] ?? $titular['tel_celular'],
                    "peso"              => $ben['peso']     ?? null,
                    "altura"            => $ben['altura']   ?? null,
                ];

                if (!array_key_exists("decsau", $ben) || !array_key_exists("perguntas", $ben["decsau"])) {
                    $benTemp["decsau"]              = (object)[];
                    $benTemp["sem_preenchimento"]   = true;
                } else {
                    $benTemp["decsau"] = $this->buildDS($ben);
                }

                $beneficiariosRequest[] = $tempBen;
            }
        }

        return $beneficiariosRequest;
    }

    private function buildDS(array $ben): array 
    {        
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);

        $contratacao = $this->payload->getContratacao();
        $arrayDS = [
            "orientacaomedica" => [
                "optionid"  => $ben['decsau']['orientacaomedica']['optionid'],
            ],
            "perguntas" => []
        ];

        foreach ($ben['decsau']['perguntas'] as $pergunta) {
            $idPergunta = (int) $pergunta['id'];
            $arrayDS['perguntas'][] = [
                "id"                    => $idPergunta,
                "pergunta"              => $this->parameters->getOptionFrom("perguntasDS", $contratacao, $idPergunta),
                "resposta"              => $this->buildResposta($pergunta)
            ];
        }

        return $arrayDS;
    }

    private function buildResposta(array $pergunta) 
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);

        if ($pergunta['resposta']) {
            return [
                'descricao' => $pergunta['descricao']   ?? "",
                'ano'       => $pergunta['ano']         ?? "",
            ];
        }

        return false;
    }

    public function send(array $bodyRequest): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);
        $curl = new CurlRequest();
        $envData = $this->getConfig()->getEnvData();

        // RECUPERA TOKEN
        $token = base64_encode($envData["auth"]["user"].":".$envData["auth"]["pass"]);
        $response = $curl
            ->setEndpoint($envData["auth"]["endpoint"])
            ->setMethod($envData["auth"]["method"])
            ->setHeaders([
                "Content-Type: application/x-www-form-urlencoded",
                "Authorization: Basic $token",
            ])
            ->setBodyRequest('grant_type=client_credentials&scope=operations%2Fform')
            ->send();
        
        $accessToken = $response["access_token"];
        // ============================

        // ENVIA DS
        $response = $curl
            ->setEndpoint($envData["sendHealth"]["endpoint"])
            ->setMethod($envData["sendHealth"]["method"])
            ->setHeaders([
                "Content-Type: application/json",
                "x-partner-hash: " . $envData["sendHealth"]["hash"],
                "authorization: Bearer $accessToken"
            ])
            ->setBodyRequest(json_encode($bodyRequest))
            ->send();
        // ============================

        return ["retcode" => -1, "message" => $response];
    }

    public function getType(): int { return CONN_API; }

}