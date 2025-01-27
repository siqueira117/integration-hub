<?php

namespace IntegrationHub\IntegrationModel\MEDEX;

use IntegrationHub\Rules\Config;

class ConfigMEDEX extends Config {
    public $validatorConfig = [ 
        "auth"          => [ "endpoint", "user" , "pass" , "method" ],
        "sendHealth"    => [ "endpoint", "hash" , "method" ] 
    ];
}