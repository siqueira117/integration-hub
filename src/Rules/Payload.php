<?php

namespace Rules;

use DateTime;
use Exception\FieldNotExistsException;

class Payload
{
    private $payload;
    private $contratante;
    private $respFin;
    private $operadora;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->operadora = $this->setOperadora($payload);
        $this->setMainKeys($payload);
    }

    /**
     * Recupera operadora da proposta, a partir do hostname setado no payload
     * 
     * @param array $payload Payload da proposta
     * 
     * @return string Operadora recuperada
     */
    private function setOperadora(array $payload): string 
    {
        if (!array_key_exists("hostname", $payload)) {
            throw new FieldNotExistsException("Campo 'hostname' não existe no payload informado");
        }

        $hostname = $payload["hostname"];

        return explode(".", $hostname, 1)[0];
    }

    /**
     * Recupera operadora do payload processado
     * 
     * @return string Operadora recuperada
     */
    private function getOperadora(): string 
    {
        return $this->operadora;
    }

    private function setMainKeys(array $payload): void
    {
        $this->setContratante($payload);
        $this->setRespFin($payload);
    }

    private function setContratante(array $payload): void
    {
        if ($payload["contrato"]["tipo_contratacao"] == "pj") {
            $this->contratante = $payload["empresa"];
        }else {
            $this->contratante = array_key_exists("contratante", $payload) ? $payload["contratante"] : $payload["beneficiarios"][0][0];
        }
    }

    public function getContratacao(): string 
    {
        return $this->payload["contrato"]["tipo_contratacao"];
    }

    public function getEmpresa(): array
    {
        if (!array_key_exists("empresa", $this->payload)) {
            throw new FieldNotExistsException("Campo 'empresa' não existe no payload informado");
        }

        return $this->payload["empresa"];
    }
  
    public function getIdadeByDataNasc(string $dataNasc): int {
        $date       = new DateTime($dataNasc);
        $interval   = $date->diff( new DateTime( date('Y-m-d') ) );
        return (int)$interval->format('%Y');
    }

    public function getRepresentanteLegal(): array 
    {
        return $this->payload["representante_legal"] ?? $this->payload["contratante"];
    }

    public function getContratante(): array
    {
        return $this->contratante;
    }

    private function setRespFin(array $payload): void
    {
        if (array_key_exists("responsavel_financeiro", $payload)) {
            $this->respFin = $payload['responsavel_financeiro'];
        } else if (array_key_exists("contratante", $payload)) {
            $this->respFin = $payload['contratante'];
        } else {
            $this->respFin = $payload['beneficiarios'][0][0];
        }
    }

    public function getRespFin(): array
    {
        return $this->respFin;
    }

    public function getPropostaID(): int
    {
        return $this->payload["propostaID"];
    }

    public function getDataAssinatura(string $format = "Y-m-d"): string
    {
        $data = DateTime::createFromFormat("Y-m-d", $this->payload["data_assinatura"]);
        return $data->format($format);
    }

    public function getDataVigencia(string $format = "Y-m-d"): string
    {
        $data = DateTime::createFromFormat("Y-m-d", $this->payload["contrato"]["data_vigencia"]);
        return $data->format($format);
    }

    public function getTipoProduto(): string
    {
        return $this->payload["contrato"]["produtos"][0];
    }

    public function getCnpjOperadora(): string
    {
        $produto = $this->getTipoProduto();
        return $this->payload["contrato"][$produto]["cnpjoperadora"];
    }

    public function getTitular(): array
    {
        return $this->payload["beneficiarios"][0][0];
    }

    public function getPlanoContrato(): array
    {
        $produto = $this->getTipoProduto();
        return $this->payload["contrato"][$produto];
    }

    public function getCnpjEntidade(): string
    {
        return $this->payload["contrato"]["entidade"]["cnpj"];
    }

    public function getContrato(): array
    {
        return $this->payload["contrato"];
    }

    public function getFormaPagamento(): string
    {
        return $this->payload["contrato"]["forma_pagamento"]["tipo"];
    }

    public function getCorretagem(): array
    {
        return $this->payload["corretagem"];
    }

    public function getBeneficiarios(): array
    {
        return $this->payload["beneficiarios"];
    }

    public function getProposal(): array
    {
        return $this->payload;
    }

    public function getSupervisor()
    {
        return $this->payload['datacuringa_1']['codigoSupervisor'] ?? null;
    }

    public function getSuperior()
    {
        return $this->payload['datacuringa_1']['codigoSuperior'] ?? null;
    }

    public function getPdfUrl(): string
    {
        $operadora  = $this->getOperadora();
        $proposal   = $this->payload;

        if (array_key_exists("proposta_pdf_url", $proposal)) { // Verifica se o link do PDF já está no payload
            syslog(LOG_NOTICE, ">>>>>>> Payload possui a chave 'proposta_pdf_url' ");
            return $proposal["proposta_pdf_url"];
        } else { // Se não tiver, montamos ele a partir dos dados da proposta.
            syslog(LOG_NOTICE, ">>>>>>> Não possui a chave 'proposta_pdf_url' ");
            $svcurl = $proposal["hostname"];
            $data_atual = $proposal["data_atual"];
            $year  = substr($data_atual, 0, 4);
            $month = substr($data_atual, 5, 2);
            $numproposal = strval($proposal["propostaID"]);

            //entra no loop até 2 se o PDF da proposta nao for encontrado
            for ($cont = 0; $cont < 2; $cont++) {
                $pdf = "https://" . $svcurl . "/svc/common/s3redirect.api?propnum=" . $numproposal . "&date=" . $year . $month . "&tipo=proposta&token=" . md5('mobisell' . $operadora . $numproposal);
                if (!file_get_contents($pdf)) {
                    // tenta primeiro com o mes seguinte, depois com o mes anterior a data_assinatura
                    if ($cont == 0) {
                        $anoMes = date("Y-m", strtotime("+1 MONTHS", strtotime("{$year}-{$month}")));
                    } elseif ($cont == 1) {
                        $anoMes = date("Y-m", strtotime("-2 MONTHS", strtotime("{$year}-{$month}")));
                    }
                    $anoMes = explode('-', $anoMes);
                    $year   = $anoMes[0];
                    $month  = $anoMes[1];
                } else {
                    $cont = 2;
                }
            }
        }

        //Verifica se PDF é acessivel
        if (!file_get_contents($pdf)) {
            throw new \Exception(__METHOD__ . " : Erro ao acessar PDF - $pdf");
        }

        if (Environment::isTest()) {
            syslog(LOG_NOTICE, ">>>>>>> PDF: $pdf");
        }

        return $pdf;
    }

    public function getValorContrato(): float 
    {
        return $this->payload["contrato"]["total_valor"];
    }

    /**
     * Recupera URL de anexo da proposta
     * 
     * @param string $imageref Campo 'imageref' do anexo
     * @param string $hostname Hostname da proposta
     * 
     * @return string URL montada a partir dos dados informados
     */
    public function getURLImage(String $imageref, String $hostname): String
    {
        $filename_url = 'https://' . $hostname . '/svc/common/s3redirect.api?image=' . $imageref;

        return $filename_url;
    }

    public function getBeneficiariosAnexos($downloadFile = false): array
    {
        $anexos = [];
        $beneficiarios = $this->payload["beneficiarios"];
        foreach ($beneficiarios as $familia) {
            foreach ($familia as $ben) {
                foreach ($ben["documentos"] as $documento) {
                    $label = str_replace(" ", "_", $this->tiraSimbolos($documento["label"]));
                    $nome =  str_replace(" ", "_", $ben["nome"]);
                    $ext = pathinfo($documento["imageref"], PATHINFO_EXTENSION);
                    $anexo = [
                        "nomeArquivo"   => strtoupper($nome . "_" . $label) . "." . $ext,
                        "url"           => $this->getURLImage($documento["imageref"], $this->payload["hostname"])
                    ];

                    if ($downloadFile) {
                        $conteudo = file_get_contents($anexo["url"]);
                        if (!$conteudo) {
                            throw new \Exception("Erro ao recuperador conteudo da URL: {$anexo['url']}");
                        }

                        $localPath = "/tmp/{$anexo['nomeArquivo']}";
                        if (!file_put_contents($localPath, $conteudo)) {
                            throw new \Exception("Erro ao realizar download do arquivo: {$anexo['url']}");
                        }

                        $anexo["localPath"] = $localPath;
                    }

                    $anexos[] = $anexo;
                }
            }
        }

        return $anexos;
    }

    public function tiraAcento(String $string): String
    {
        return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/", "/(ç)/", "/(Ç)/"), explode(" ", "a A e E i I o O u U n N c C"), $string);
    }

    public function tiraSimbolos(String $string): String
    {

        $stringLimpa = "";
        $palavras = explode(" ", $string);
        foreach ($palavras as $palavra) {
            if ($stringLimpa === "") {
                $stringLimpa = self::tiraAcento($palavra);
            } else {
                $stringLimpa = $stringLimpa . " " . self::tiraAcento($palavra);
            }
        }
        $stringLimpa = preg_replace("/,/", "", $stringLimpa);
        $stringLimpa = preg_replace("/[^a-zA-Z0-9[:space:]]+/", "", $stringLimpa);

        return $stringLimpa;
    }

    public function getPlanoTitular(string $uuid): string
    {
        $beneficiarios = $this->payload["beneficiarios"];

        foreach ($beneficiarios as $familia) {
            foreach ($familia as $benKey => $ben) {
                if ($benKey === 0 && $ben["uuid"] === $uuid) {
                    return (string)$ben["produtos"][$this->getTipoProduto()]["btxplan"];
                }
            }
        }
    }

    public function getNumVidas(): int {
        return $this->payload["contrato"]["total_beneficiarios"];
    }

    public function isCopart(): bool {
        return $this->payload["contrato"]["coparticipacao"];
    }
}
