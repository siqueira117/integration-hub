<?php

namespace Tests;

use IntegrationHub\Exception\IntegrationTypeNotExists;
use IntegrationHub\IntegrationHub;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IntegrationHubTest extends TestCase {
    private $payload;

    protected function setUp(): void
    {
        $this->payload = [];
    }

    public function test_erro_ao_nao_encontrar_tipo_de_integracao(): void
    {
        $this->expectException(IntegrationTypeNotExists::class);
        $this->expectExceptionCode(200);
        
        new IntegrationHub($this->payload, 1);
    }

}
