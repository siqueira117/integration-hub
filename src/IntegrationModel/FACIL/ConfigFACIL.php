<?php

namespace IntegrationHub\IntegrationModel\FACIL;

use IntegrationHub\Rules\Config;

class ConfigFACIL extends Config {
    protected $validatorConfig = [ 
        "sendProposal" => [ "endpoint", "user" , "pass" , "method" ]
    ];
}