<?php

namespace IntegrationHub\IntegrationModel\CBS;

use IntegrationHub\Rules\Options;

class OptionsCBS extends Options {
    protected $requiredOptions = [ "parentesco", "dadosCbs", "estadoCivil" ];
}