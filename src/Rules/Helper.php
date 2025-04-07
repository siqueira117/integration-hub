<?php

namespace IntegrationHub\Rules;

class Helper {
    public static function tiraSimbolos(String $string): String
    {
        $stringLimpa = "";
        $palavras = explode(" ", $string);
        foreach ($palavras as $palavra) {
            if ($stringLimpa === "") {
                $stringLimpa = self::tiraAcento($palavra);
            } else {
                $stringLimpa = $stringLimpa . " " . self::tiraAcento($palavra);
            }
        }
        $stringLimpa = preg_replace("/,/", "", $stringLimpa);
        $stringLimpa = preg_replace("/[^a-zA-Z0-9[:space:]]+/", "", $stringLimpa);

        return $stringLimpa;
    }

    public static function tiraSimbolosELetras(String $string): String {
        $stringLimpa = self::tiraSimbolos($string);
        $stringLimpa = preg_replace("/[^0-9]/", "", $stringLimpa);
        return $stringLimpa;
    }

    public static function tiraAcento(String $string): String {
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/"),explode(" ","a A e E i I o O u U n N c C"),$string);
    }

    public static function limparTexto($str)
    {
        return preg_replace("/[^0-9]/", "", $str);
    }

    public static function splitIBGE(int $codigoIBGE): array
    {
        return [
            "ESTADO"        => substr($codigoIBGE, 0, 2),
            "MUNICIPIO"     => substr($codigoIBGE, 2, 4),
            "VERIFICADOR"   => substr($codigoIBGE, 6, 1)
        ];
    }
}