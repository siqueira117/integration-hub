<?php

namespace Tests\Integration\SGU;

use IntegrationHub\IntegrationModel\SGU\ParametersSGU;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase {
    private $parameters;

    protected function setUp(): void
    {
        $this->parameters = new ParametersSGU();
    }

    #[DataProvider('mei')]
    public function test_tipo_societario_mei(string $valor): void
    {
        $valorEsperado  = "1";
        $tipoSocietario = $this->parameters->getTipoSocietario($valor);
        $this->assertEquals($valorEsperado, $tipoSocietario);
    }

    public static function mei() 
    {
        return [["MEI"], ["mei"], ["M E I"], ["mEi"]];
    }
}
