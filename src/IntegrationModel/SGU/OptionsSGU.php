<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Rules\Options;

class OptionsSGU extends Options {
    public function getTipoSocietario(string $porte): string 
    {
        syslog(LOG_NOTICE, "[HUB] - PORTE VINDO DA PROPOSTA ------- " . $porte);
        $porteOrig = $porte;
        $porte = str_replace([" ", "-"], "", $porte);
    
        $tipos = array(
            array(
                "desc" => "MEI - MICRO EMPREENDEDOR INDIVIDUAL",
                "id" => "1"
            ),
            array(
                "desc" => "EPP - EMPRESA DE PEQUENO PORTE",
                "id" => "2"
            ),
            array(
                "desc" => "ME - MICROEMPRESA",
                "id" => "3"
            ),
            array(
                "desc" => "EMPRESA NORMAL",
                "id" => "4"
            )
        );
         
        foreach ($tipos as $tipo) {
            $descPorte = str_replace([" ", "-"], "", $tipo["desc"]);
            if (stristr($descPorte, $porte)) {
                syslog(LOG_NOTICE, "[HUB] - PORTE DE/PARA ------ " . $tipo["desc"]);
                return $tipo["id"];
            }   
        }
    
        syslog(LOG_ERR, "[HUB] - ERRO --- não foi encontrado o ID para o porte: " . $porteOrig);
        return "99";
    }
}