<?php

namespace IntegrationHub\Rules;

class Variables {
    private static $params = [];

    public static function add(string $nome, $valor): void {
        self::$params[$nome] = $valor;
    }
    
    public static function get(string $nome) {
        return self::$params[$nome] ?? null;
    }
}