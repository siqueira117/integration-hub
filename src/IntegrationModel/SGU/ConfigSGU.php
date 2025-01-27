<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Rules\Config;

class ConfigSGU extends Config {
    public $validatorConfig = [ "sendProposal" => [ "endpoint", "token", "method" ] ];
}