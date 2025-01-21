<?php

use IntegrationHub\IntegrationHub;

require_once(__DIR__."/vendor/autoload.php");

try {
    $config     = json_decode(file_get_contents(__DIR__."/config.json"), true);
    $payload    = json_decode(file_get_contents(__DIR__."/proposta.json"), true);
    
    $hub = new IntegrationHub($payload, $config, TYPE_SGU);
    $hub->run();
} catch (Exception $e) {
    if (PHP_SAPI === 'cli') {
        print_r("[HUB][ERR]: $e");
    }
}