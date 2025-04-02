<?php

namespace IntegrationHub\IntegrationModel\SGU;

use IntegrationHub\Rules\Config;

class ConfigSGU extends Config {
    protected $validatorConfig = [ "sendProposal" => [ "endpoint", "token", "method" ] ];
}