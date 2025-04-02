<?php

namespace IntegrationHub\IntegrationModel\CBS;

use IntegrationHub\Rules\Config;

class ConfigCBS extends Config {
    protected $validatorConfig = [ 
        "sendBeneficiary"   => [ "endpoint", "method" ],
        "getToken"          => [ "endpoint", "user", "pass", "clientID", "clientSecret", "method" ],
        "uploadDocument"    => [ "endpoint", "method" ],
        "docType"           => [ "endpoint", "method" ],
        "disconnectSession" => [ "endpoint", "method" ],
        "putIdDocument"     => [ "endpoint", "method" ],
        "interview"         => [ "url" ]
    ];
}