<?php

namespace IntegrationHub\Rules;

class Logger {
    private static $pid     = null;
    private static $logs    = [
        0 => "[EMERG]",
        3 => "[ERR]",
        5 => "[NOTICE]",
        4 => "[WARN]"
    ];

    /**
     * Loga uma mensagem no padrão do HUB
     * 
     * @param int $logType Tipo do log a ser exibido, sendo eles:
     * 0 - EMERGENCY,
     * 3 - ERROR,
     * 5 - NOTICE,
     * 4 - WARN
     * 
     * @param string $message Mensagem a ser logada
     * 
     * @return void
     */
    public static function message(int $logType, string $message): void
    {
        $pid = self::getPID();
        $propostaID = Variables::get("PROPOSTA_ID");

        $id = "[HUB][$pid]".self::$logs[$logType];
        if ($propostaID) {
            $id .= "[$propostaID]";
        }

        syslog($logType, "$id - $message");
    }

    /**
     * Gera um PID unico para o processo executado
     * 
     * @return string PID a ser utilizado
     */
    private static function getPID(): string
    {
        if (self::$pid) {
            return self::$pid;
        }

        $timestamp = time();
        $pid = substr(md5($timestamp), -5);
        self::$pid = $pid;
        
        return $pid;
    }
}