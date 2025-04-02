<?php

namespace IntegrationHub\Request;

use IntegrationHub\Exception\CurlRequestException;
use IntegrationHub\Rules\Logger;

class CurlRequest
{
    private $endpoint;
    private $method;
    private $headers;
    private $bodyRequest;
    private $curl;
    private $response;
    private $httpcode;
    private $responseHeaders;
    private $allowedMethods;

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

    private function setResponse(?array $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    private function setHttpCode(int $httpcode): void
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

    public function setAllowedMethods(array $methods): self
    {
        $this->allowedMethods = $methods;
        return $this;
    }

    private function getAllowedMethods(): ?array
    {
        return $this->allowedMethods;
    }

    private function setResponseHeaders(array $headers): void
    {
        $this->responseHeaders = $headers;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
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

    private function headersToArray(string $str): array {
        $headers = array();
        $headersTmpArray = explode( "\r\n" , $str );
        for ( $i = 0 ; $i < count( $headersTmpArray ) ; ++$i )
        {
            // we dont care about the two \r\n lines at the end of the headers
            if ( strlen( $headersTmpArray[$i] ) > 0 )
            {
                // the headers start with HTTP status codes, which do not contain a colon so we can filter them out too
                if ( strpos( $headersTmpArray[$i] , ":" ) )
                {
                    $headerName = substr( $headersTmpArray[$i] , 0 , strpos( $headersTmpArray[$i] , ":" ) );
                    $headerValue = substr( $headersTmpArray[$i] , strpos( $headersTmpArray[$i] , ":" )+1 );
                    $headers[$headerName] = $headerValue;
                }
            }
        }
        return $headers;
    }

    public function send(): ?array
    {
        Logger::message(LOG_NOTICE, "Preparando requisição...");

        $curl = $this->curl;

        $curlOptions = [
            CURLOPT_URL             => $this->endpoint,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER          => true,
            CURLOPT_CUSTOMREQUEST   => $this->method,
        ];

        Logger::message(LOG_NOTICE, ">> Method: " . $this->method);

        if ($this->headers) {
            $curlOptions[CURLOPT_HTTPHEADER] = $this->headers;
            Logger::message(LOG_NOTICE, ">> Headers: " . json_encode($this->headers));
        }

        if ($this->bodyRequest) {
            $bodyRequest = is_array($this->bodyRequest) ? json_encode($this->bodyRequest) : $this->bodyRequest;
            Logger::message(LOG_NOTICE, ">> BodyRequest: $bodyRequest");
            
            $curlOptions[CURLOPT_POSTFIELDS] = $this->bodyRequest;
        }

        curl_setopt_array($curl, $curlOptions);

        $responseOriginal = trim(curl_exec($curl));
        
        $curlerro   = curl_error($curl);
        $httpcode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $httptime   = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
        
        // HEADER
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headerStr  = substr( $responseOriginal , 0 , $headerSize );
        $response   = substr( $responseOriginal , $headerSize );
        $headers    = $this->headersToArray($headerStr);

        $response = json_decode($response, true);

        Logger::message(LOG_NOTICE, ">> Endpoint: " . $this->endpoint);
        Logger::message(LOG_NOTICE, ">> Tempo de resposta: $httptime");
        Logger::message(LOG_NOTICE, ">> HTTP Code: $httpcode");
        Logger::message(LOG_NOTICE, ">> Response: " . json_encode($response) . "\n");

        if (PHP_SAPI === 'cli') {
            print_r("[HUB] - >> Endpoint: " . $this->endpoint."\n");
            print_r("[HUB] - >> BodyRequest: " . $this->bodyRequest."\n");
            print_r("[HUB] - >> Tempo de resposta: $httptime\n");
            print_r("[HUB] - >> HTTP Code: $httpcode\n");
            print_r("[HUB] - >> Response: " . json_encode($response) . "\n");
        }

        // Interpretando retorno da API
        if (!empty($curlerro)) {
            throw new CurlRequestException("Curl error # $curlerro");
        }

        $allowedMethods = $this->getAllowedMethods() ?? [200, 201]; 
        if (!in_array($httpcode, $allowedMethods)) {
            throw new CurlRequestException("Erro ao realizar requisição: HTTP Code $httpcode");
        }

        $this->setResponse($response);
        $this->setHttpCode($httpcode);
        $this->setResponseHeaders($headers);

        return $response;
    }
}
