<?php

use IntegrationHub\IntegrationHub;

require_once(__DIR__."/vendor/autoload.php");

function integraMedex() {
    try {
        $config         = json_decode(file_get_contents(__DIR__."/config.json"), true);
        $payload        = json_decode(file_get_contents(__DIR__."/proposta.json"), true);
        $options        = json_decode(file_get_contents(__DIR__."/options.json"), true);
        $configMedex    = json_decode(file_get_contents(__DIR__."/config_medex.json"), true);
    
        $hub = new IntegrationHub(TYPE_MEDEX, $payload, $configMedex, $options);
        $hub->run();
    } catch (Exception $e) {
        if (PHP_SAPI === 'cli') {
            print_r("[HUB][ERR]: $e");
        }
    }
}

function integraSGU() 
{
    try {
        $config         = json_decode(file_get_contents(__DIR__."/config.json"), true);
        $payload        = json_decode(file_get_contents(__DIR__."/proposta.json"), true);
        $options        = json_decode(file_get_contents(__DIR__."/options.json"), true);
    
        $hub = new IntegrationHub();
        $hub->setIntegrationType(TYPE_SGU);
        $hub->setPayload($payload);
        $hub->setConfig($config);
        $hub->setParameters($options);
        $hub->run();

    } catch (Exception $e) {
        if (PHP_SAPI === 'cli') {
            print_r("[HUB][ERR]: $e");
        }
    }
}

integraMedex();