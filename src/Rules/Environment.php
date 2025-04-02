<?php

namespace IntegrationHub\Rules;

class Environment {
    public static function isTest(): bool {
        return (\ENVIRONMENT\is_test_environment() || \ENVIRONMENT\is_dev_environment());
    }
}