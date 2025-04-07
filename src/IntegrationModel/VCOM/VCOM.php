<?php

namespace IntegrationHub\IntegrationModel\VCOM;

use IntegrationHub\IntegrationModel\AbstractIntegrationModel;
use IntegrationHub\Request\CurlRequest;
use IntegrationHub\Rules\Helper;
use IntegrationHub\Rules\Logger;

class VCOM extends AbstractIntegrationModel
{
    public function build(): array
    {
        Logger::message(LOG_NOTICE, __METHOD__ . " - START");
        if (PHP_SAPI === 'cli') print_r("[HUB] - Construindo bodyRequest...\n");

        $proposal = [
            "cabecalho"                 => $this->buildCabecalho(),
            "dadosEmpresaContratante"   => $this->buildDadosEmpresaContratante(),
            "dadosPlano"                => $this->buildDadosPlano()
        ];

        return $proposal;
    }

    protected function buildCabecalho(): array
    {
        Logger::message(LOG_NOTICE, __METHOD__ . " - START");
        return [
            "codigoControleTransacao"   => "", //TODO: Verificar o que deve ser enviado
            "codigoVersaoPTU"           => "1", //TODO: Verificar quais dos campos é o correto
            "numeroVersaoPTU"           => "1",
            "unimed"                    => [ "codigoUnimedOrigem" => $this->options->getOptionFrom("unimed", "codigoUnimedOrigem") ],
            "dataGeracao"               => date("Y/m/d H:i:s")
        ];
    }

    protected function buildDadosEmpresaContratante(): array
    {
        Logger::message(LOG_NOTICE, __METHOD__ . " - START");

        $contratante = $this->payload->getContratante();

        $empresaContratante = [
            "tipoContratante"           => $this->buildTipoContratante(),
            "nomeEmpresa"               => "",
            "endereco"                  => $this->buildEndereco($contratante["endereco1"]),
            "indicadorCessaoRede"       => "",  //TODO: Verificar regra para envio
            "registroANSAutoGestao"     => "",  //TODO: Verificar regra para envio
            "indicadorAdmBeneficios"    => "",  //TODO: Verificar regra para envio
            "registroANSAdmBeneficios"  => ""   //TODO: Verificar regra para envio
        ];

        return $empresaContratante;
    }

    /**
     * Retorna objeto do tipo de contratante
     * 
     * @return array Dados do tipo de contratante, tendo os possíveis retornos:
     * pj: { "cnpj": "00000000000000" }
     * ad e pf: { "cpf": "00000000000" }
     */
    protected function buildTipoContratante(): array
    {
        Logger::message(LOG_NOTICE, __METHOD__ . " - START");

        $contratante = $this->payload->getContratante();
        $tipoContratacao = $this->payload->getContratacao();

        $tipoContratante = [];
        if ($tipoContratacao === "pj") {
            $tipoContratacao["cnpj"] = $contratante["cnpj"];
        } else {
            $tipoContratacao["cpf"] = $contratante["cpf"];
        }

        return $tipoContratante;
    }

    /**
     * Constroi objeto de endereço para requisição, a partir do endereço fornecido por parametro
     * 
     * @param array $endereco Endereço vindo do payload
     * 
     * @return array Endereco tratado para ser enviado na requisição
     */
    protected function buildEndereco(array $endereco): array
    {
        Logger::message(LOG_NOTICE, __METHOD__ . " - START");

        $enderecoRequest = [
            "logradouroPrincipal"   => $endereco["logradouro"]  ?? "",
            "numeroLogradouro"      => $endereco["numero"]      ?? 0,
            "bairro"                => $endereco["bairro"]      ?? "",
            "codigoMunicipio"       => Helper::splitIBGE($endereco["codigo_ibge"])["MUNICIPIO"],
            "codigoUf"              => Helper::splitIBGE($endereco["codigo_ibge"])["ESTADO"]
        ];

        return $enderecoRequest;
    }

    protected function buildDadosPlano(): array
    {
        Logger::message(LOG_NOTICE, __METHOD__ . " - START");

        $tipoContratacao    = $this->payload->getContratacao();
        $tipoProduto        = $this->payload->getTipoProduto();
        $titular            = $this->payload->getTitular();

        $dadosPlano = [
            "tipoContratacao" => "",
            "tipoAbrangencia" => $titular["produtos"][$tipoProduto]["abrangencia"] 
        ];

        return $dadosPlano;
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
