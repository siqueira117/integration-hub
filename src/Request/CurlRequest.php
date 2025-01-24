<?php

namespace IntegrationHub\Request;

use IntegrationHub\Exception\CurlRequestException;

class CurlRequest
{
    private $endpoint;
    private $method;
    private $headers;
    private $bodyRequest;
    private $curl;
    private $response;
    private $httpcode;

    public function __construct()
    {
        $this->curl = curl_init();
    }

    public function setEndpoint(string $endpoint, ?array $params = null): self
    {
        if ($params) {
            $endpoint = str_replace(array_keys($params), array_values($params), $endpoint);
        }

        $this->endpoint = $endpoint;
        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function setResponse(?array $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function setHttpCode(int $httpcode): void
    {
        $this->httpcode = $httpcode;
    }

    /**
     * Retorna HTTP CODE da ultima requisição realizada
     * 
     * @return int código http
     */
    public function getHttpCode(): int
    {
        return $this->httpcode;
    }

    public function setBodyRequest(string $bodyRequest): self
    {
        $this->bodyRequest = $bodyRequest;

        return $this;
    }

    public function reset(): void
    {
        $this->endpoint     = null;
        $this->method       = null;
        $this->headers      = null;
        $this->bodyRequest  = null;
    }

    public function send(): ?array
    {
        syslog(LOG_NOTICE, "Preparando requisição...");

        $curl = $this->curl;

        $curlOptions = [
            CURLOPT_URL             => $this->endpoint,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => $this->method,
        ];

        syslog(LOG_NOTICE, ">> Method: " . $this->method);

        if ($this->headers) {
            $curlOptions[CURLOPT_HTTPHEADER] = $this->headers;
            syslog(LOG_NOTICE, ">> Headers: " . json_encode($this->headers));
        }

        if ($this->bodyRequest) {
            $bodyRequest = is_array($this->bodyRequest) ? json_encode($this->bodyRequest) : $this->bodyRequest;
            syslog(LOG_NOTICE, ">> BodyRequest: $bodyRequest");
            
            $curlOptions[CURLOPT_POSTFIELDS] = $this->bodyRequest;
        }

        curl_setopt_array($curl, $curlOptions);

        $responseOriginal = trim(curl_exec($curl));
        syslog(LOG_NOTICE, ">> Response: $responseOriginal");

        $response = json_decode($responseOriginal, true);
        $curlerro = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $httptime = curl_getinfo($curl, CURLINFO_TOTAL_TIME);

        syslog(LOG_NOTICE, ">> Endpoint: " . $this->endpoint);
        syslog(LOG_NOTICE, ">> Tempo de resposta: $httptime");
        syslog(LOG_NOTICE, ">> HTTP Code: $httpcode");

        if (PHP_SAPI === 'cli') {
            print_r(">> Endpoint: " . $this->endpoint."\n");
            print_r(">> BodyRequest: " . $this->bodyRequest."\n");
            print_r(">> Tempo de resposta: $httptime\n");
            print_r(">> HTTP Code: $httpcode\n");
            print_r(">> Response: $responseOriginal\n");
        }

        // Interpretando retorno da API
        if (!empty($curlerro)) {
            throw new CurlRequestException("Curl error # $curlerro");
        }

        if (!in_array($httpcode, [200, 201])) {
            throw new CurlRequestException("Erro ao realizar requisição: HTTP Code $httpcode");
        }

        $this->setResponse($response);
        $this->setHttpCode($httpcode);

        return $response;
    }
}
