<?php

namespace IntegrationHub\IntegrationModel\VCOM;

use IntegrationHub\Rules\Options;

class OptionsVCOM extends Options {
    protected function getTipoAbrangencia(string $abrangencia)
    {
        $tipos = [

        ];

        return $tipos[$abrangencia];
    }

    protected function getTipoContratacao()
    {

    }
}