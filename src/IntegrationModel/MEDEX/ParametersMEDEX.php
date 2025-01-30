<?php

namespace IntegrationHub\IntegrationModel\MEDEX;

use IntegrationHub\Rules\Parameters;

class ParametersMEDEX extends Parameters {
    public function getRequiredOptions(): array
    {
        return ["perguntasDS"];
    }
}