<?php

namespace IntegrationHub\IntegrationModel\MEDEX;

use IntegrationHub\Rules\Config;

class ConfigMEDEX extends Config {
    protected $validatorConfig = [ 
        "auth"          => [ "endpoint", "user" , "pass" , "method" ],
        "sendHealth"    => [ "endpoint", "hash" , "method" ] 
    ];
}