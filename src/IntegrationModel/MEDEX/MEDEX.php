<?php

namespace IntegrationHub\IntegrationModel\MEDEX;

use IntegrationHub\IntegrationModel\AbstractIntegrationModel;

class MEDEX extends AbstractIntegrationModel {
    public function build(): array
    {
        syslog(LOG_NOTICE, "[HUB] - " . __METHOD__);
        if (PHP_SAPI === 'cli') print_r("Construindo bodyRequest...\n");

        
        return [];
    }

    public function send(array $bodyRequest): array
    {
        return [];
    }

    public function getType(): int { return CONN_API; }

}