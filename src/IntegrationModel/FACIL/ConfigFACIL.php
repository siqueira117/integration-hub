<?php

namespace IntegrationHub\IntegrationModel\FACIL;

use IntegrationHub\Rules\Config;

class ConfigFACIL extends Config {
    public $validatorConfig = [ 
        "sendProposal" => [ "endpoint", "user" , "pass" , "method" ]
    ];
}