<?php

namespace Tests;

use IntegrationHub\Exception\IntegrationHub\IntegrationTypeNotExists;
use IntegrationHub\IntegrationHub;
use PHPUnit\Framework\TestCase;

class IntegrationHubTest extends TestCase {
    private $payload;
    private $config;

    protected function setUp(): void
    {
        $this->payload  = [];
        $this->config   = [];
    }

    public function test_erro_ao_nao_encontrar_tipo_de_integracao(): void
    {
        $this->expectException(IntegrationTypeNotExists::class);
        $this->expectExceptionCode(200);
        
        $tipoIntegracaoNaoExiste = 100;
        new IntegrationHub($tipoIntegracaoNaoExiste, $this->payload, $this->config);
    }

}
