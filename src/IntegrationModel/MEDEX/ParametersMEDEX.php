<?php

namespace IntegrationHub\IntegrationModel\MEDEX;

use IntegrationHub\IntegrationModel\Parameters\Parameters;

class ParametersMEDEX extends Parameters {
    public function getRequiredOptions(): array
    {
        return ["perguntasDS"];
    }
}