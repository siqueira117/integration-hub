<?php

namespace IntegrationHub\Rules;

class Validator
{
    // DEFAULT PAYLOAD RULES
    private const PAYLOAD_EMPRESA_REGRAS = [
        "cnpj" => [
            "type"      => "string",
            "required"  => true,
            "max"       => 14
        ],
        "razaosocial" => [
            "type"      => "string",
            "required"  => true
        ],
        "responsavel" => [
            "type"          => "array_associative",
            "required"      => true, 
            "itens"         => [
                "nome"  => [
                    "type"      => "string",
                    "required"  => true
                ],
                "cpf"   => [
                    "type"      => "string",
                    "required"  => true,
                    "max"       => 11
                ]
            ]
        ],
        "endereco1" => [
            "type"          => "array_associative",
            "required"      => true, 
            "itens"         => [
                "cep"   => [
                    "type"  => "string",
                    "max"   => "8",
                    "required" => true
                ],
                "logradouro" => [
                    "type" => "string",
                    "required" => true,
                ],
                "numero" => [
                    "type" => "string",
                    "required" => true
                ],
                "bairro" => [
                    "type" => "string",
                    "required" => true
                ]
            ]
        ]
    ];

    // EMPRESA
    public function getPayloadRules(): array 
    {
        $rules = $this->getRulesToEmpresa();
        if ($rules) {
            return array_merge(self::PAYLOAD_EMPRESA_REGRAS, $rules);
        }

        return self::PAYLOAD_EMPRESA_REGRAS;
    }

    public function validatePayloadEmpresa(array $dataToValidate): array 
    {
        $validations = $this->getPayloadRules();
        return $this->validate_data($dataToValidate, $validations);
    }

    // Usado em casos de validções customizadas
    protected function getRulesToEmpresa(): ?array 
    {
        return null;
    }
    // =====================================================================

    protected function validate_data(array $data, array $validations, string $prefix = ""): array
    {
        $errors = [];
        $prefix = $prefix ? $prefix . '.' : '';

        foreach ($validations as $key => $value) {
            // Verifica se campo é required
            if (isset($value['required']) && $value['required'] && !array_key_exists($key, $data)) {
                if (!empty($value['messages']['required'])) {
                    $errors[] = $value['messages']['required'];
                } else {
                    $errors[] = "{$prefix}{$key} não fornecido";
                }
            }

            if (!array_key_exists($key, $data)) {
                continue;
            }
            // ==============================

            // Verifica o tipo do campo
            if (!empty($value['type'])) {
                $message = !empty($value['messages']['type']) ? $value['messages']['type'] : null;
                switch ($value['type']) {
                    case 'string':
                        if (!is_string($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo string";
                        }
                        break;
                    case 'integer':
                        if (!is_integer($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo integer";
                        }
                        break;
                    case 'boolean':
                        if (!is_bool($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo boolean";
                        }
                        break;
                    case 'array':
                        if (!is_array($data[$key]) || !array_is_list($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo array";
                        }
                        break;
                    case 'array_associative':
                        if (!is_array($data[$key]) || array_is_list($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo array associativo";
                        }
                        break;
                    case 'float':
                        if (!is_float($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo float";
                        }
                        break;
                    case 'date':
                        $date = $this->validate_date($data[$key], $value['format'] ?? 'Y-m-d');
                        if ($date === false) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser uma data válida";
                        }
                        break;
                    case 'url':
                        if (!filter_var($data[$key], FILTER_VALIDATE_URL)) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser uma url válida";
                        }
                        break;
                    case 'email':
                        if (!$this->validate_emaildomain($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser um email válido";
                        }
                        break;
                    case 'number':
                        if (!is_int($data[$key]) && !is_float($data[$key])) {
                            $errors[] = !empty($message) ? $message : "{$prefix}{$key} deve ser um número";
                        }
                        break;
                }
            }

            //Enum
            if (!empty($value['enum']) && !in_array($data[$key], $value['enum'])) {
                $errors[] = !empty($value['messages']['enum']) ? $value['messages']['enum'] : "{$prefix}{$key} inválido. Deve ser um dos valores: " . implode(', ', $value['enum']);
            }

            if (!empty($value['min'])) {
                if (is_array($data[$key]) && count($data[$key]) < $value['min']) {
                    $errors[] = !empty($value['messages']['min']) ? $value['messages']['min'] : "{$prefix}{$key} deve ter pelo menos " . $value['min'] . " itens";
                } else if (is_integer($data[$key]) && $data[$key] < $value['min']) {
                    $errors[] = !empty($value['messages']['min']) ? $value['messages']['min'] : "{$prefix}{$key} deve ter valor mínimo de " . $value['min'];
                } else if (is_string($data[$key]) && strlen($data[$key]) < $value['min']) {
                    $errors[] = !empty($value['messages']['min']) ? $value['messages']['min'] : "{$prefix}{$key} deve ter pelo menos " . $value['min'] . " caracteres";
                }
            }

            if (!empty($value['max'])) {
                if (is_array($data[$key]) && count($data[$key]) > $value['max']) {
                    $errors[] = !empty($value['messages']['max']) ? $value['messages']['max'] : "{$prefix}{$key} deve ter no máximo " . $value['min'] . " itens";
                } else if (is_integer($data[$key]) && $data[$key] > $value['max']) {
                    $errors[] = !empty($value['messages']['max']) ? $value['messages']['max'] : "{$prefix}{$key} deve ter valor máximo de " . $value['max'];
                } else if (is_string($data[$key]) && strlen($data[$key]) > $value['max']) {
                    $errors[] = !empty($value['messages']['max']) ? $value['messages']['max'] : "{$prefix}{$key} deve ter no máximo " . $value['max'] . " caracteres";
                }
            }

            if (!empty($value['length'])) {
                if (is_array($data[$key]) && count($data[$key]) != $value['length']) {
                    $errors[] = !empty($value['messages']['length']) ? $value['messages']['length'] : "{$prefix}{$key} deve ter " . $value['min'] . " itens";
                }
            }

            if (!empty($value['format'])) {
                switch ($value['format']) {
                    case 'cnpj':
                        $res = $this->validateCNPJ($data[$key]);
                        if (!$res) {
                            $errors[] = !empty($value['messages']['format']) ? $value['messages']['format'] : "{$prefix}{$key} não é um cnpj válido";
                        }
                        break;
                    case 'cnae':
                        $res = $this->validateCNAE($data[$key]);
                        if (!$res) {
                            $errors[] = !empty($value['messages']['format']) ? $value['messages']['format'] : "{$prefix}{$key} não é um cnae válido";
                        }
                        break;
                    case 'caepf':
                        $res = $this->validateCAEPF($data[$key]);
                        if (!$res) {
                            $errors[] = !empty($value['messages']['format']) ? $value['messages']['format'] : "{$prefix}{$key} não é um caepf válido";
                        }
                        break;
                    case 'cpf':
                        $res = $this->validateCPF($data[$key]);
                        if (!$res) {
                            $errors[] = !empty($value['messages']['format']) ? $value['messages']['format'] : "{$prefix}{$key} não é um cpf válido";
                        }
                        break;
                }
            }

            //Array Lista
            if (isset($value['type']) && $value['type'] === 'array') {
                if (!empty($value['array_type'])) {
                    //Array de valores
                    $message = !empty($value['messages']['array_type']) ? $value['messages']['array_type'] : null;
                    switch ($value['array_type']) {
                        case 'string':
                            $message = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo string";
                            $validate = 'is_string';
                            break;
                        case 'integer':
                            $message = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo integer";
                            $validate = 'is_integer';
                            break;
                        case 'boolean':
                            $message = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo boolean";
                            $validate = 'is_bool';
                            break;
                        case 'float':
                            $message = !empty($message) ? $message : "{$prefix}{$key} deve ser do tipo float";
                            $validate = 'is_float';
                            break;
                        case 'array':
                            foreach ($data[$key] as $itemIndex => $item) {
                                if (!is_array($item) || !array_is_list($item)) {
                                    $errors[] = "{$prefix}{$key} deve ser um array";
                                }

                                if (!isset($value['items'])) {
                                    continue;
                                }

                                foreach ($item as $subItemIndex => $subItem) {
                                    $res = $this->validate_data($subItem, $value['items'], "{$prefix}{$key}[{$itemIndex}][{$subItemIndex}]");
                                    if (!empty($res)) {
                                        $errors = array_merge($errors, $res);
                                    }
                                }
                            }

                            $validate = null;
                            break;
                        case 'associative':
                            foreach ($data[$key] as $itemIndex => $item) {
                                if (!is_array($item) || array_is_list($item)) {
                                    $errors[] = "{$prefix}{$key} deve ser um array associativo";
                                }

                                if (!isset($value['items'])) {
                                    continue;
                                }

                                $res = $this->validate_data($item, $value['items'], "{$prefix}{$key}[{$itemIndex}]");
                                if (!empty($res)) {
                                    $errors = array_merge($errors, $res);
                                }
                            }

                            $validate = null;
                            break;
                        default:
                            $validate = null;
                    }

                    if ($validate) {
                        $countArray = count($data[$key]);
                        for ($i = 0; $i < $countArray; $i++) {
                            if (!$validate($data[$key][$i])) {
                                $errors[] = $message;
                            }
                        }
                    }
                }

                if (!empty($value['array_enum'])) {
                    $message = !empty($value['messages']['array_format']) ? $value['messages']['array_format'] : null;

                    $countArray = count($data[$key]);
                    for ($i = 0; $i < $countArray; $i++) {
                        if (!in_array($data[$key][$i], $value['array_enum'])) {
                            $errors[] = "{$prefix}{$key}[{$i}]: {$data[$key][$i]} inválido. Deve ser um dos valores: " . implode(', ', $value['array_enum']);
                        }
                    }
                }

                if (!empty($value['array_format'])) {
                    //Array de valores
                    $message = !empty($value['messages']['array_format']) ? $value['messages']['array_format'] : null;
                    switch ($value['array_format']) {
                        case 'cpf':
                            $message = !empty($message) ? $message : "não é um CPF válido.";
                            $validate = 'BITIX\validateCPF';
                            break;
                        case 'cnpj':
                            $message = !empty($message) ? $message : "não é um CNPJ válido.";
                            $validate = 'BITIX\validateCNPJ';
                            break;
                        case 'caepf':
                            $message = !empty($message) ? $message : "não é um CAEPF válido.";
                            $validate = 'BITIX\validateCAEPF';
                            break;
                        case 'email':
                            $message = !empty($message) ? $message : "não é um email válido.";
                            $validate = 'BITIX\validate_emaildomain';
                            break;
                        case 'cnae':
                            $message = !empty($message) ? $message : "não é um CNAE válido.";
                            $validate = 'BITIX\validateCNAE';
                            break;
                        default:
                            $validate = null;
                    }

                    if ($validate) {
                        $countArray = count($data[$key]);
                        for ($i = 0; $i < $countArray; $i++) {
                            if (!$validate($data[$key][$i])) {
                                $errors[] = "{$prefix}{$key}[{$i}]: {$data[$key][$i]} {$message}";
                            }
                        }
                    }
                }
            }

            //Array Associativo
            if (isset($value['type']) && $value['type'] === 'array_associative') {
                if (!empty($value['fields'])) {
                    $res = $this->validate_data($data[$key], $value['fields'], "{$prefix}{$key}");
                    if (!empty($res)) {
                        $errors = array_merge($errors, $res);
                    }
                }
            }

            if (isset($value['refine']) && is_callable($value['refine'])) {
                $res = $value['refine']($data[$key], $data);
                if (is_array($res) && count($res)) {
                    foreach ($res as $r) {
                        if (is_string($r) && strlen($r)) {
                            $errors[] = "{$prefix}{$key}: {$r}";
                        }
                    }
                }
                if (is_string($res) && strlen($res)) {
                    $errors[] = "{$prefix}{$key}: {$res}";
                }
            }
        }

        return $errors;
    }

    protected function validate_date($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    protected function validateCNPJ($cnpj)
    {
        if (empty($cnpj)) {
            return false;
        }

        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;
        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
            return false;
        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }

    protected function validateCNAE($cnae)
    {
        if (empty($cnae)) {
            return false;
        }

        if (strlen($cnae) != 7) {
            return false;
        }

        return true;
    }

    protected function validateCPF($cpf, $applyRegexToRemoveMask = true)
    {

        // Verifica se um número foi informado
        if (empty($cpf)) {
            return false;
        }

        // Elimina possivel mascara
        if ($applyRegexToRemoveMask) {
            $cpf = preg_replace('/[^0-9]/', '', $cpf);
            $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
        }

        // Verifica se o numero de digitos informados é igual a 11 
        if (strlen($cpf) != 11) {
            return false;
        }
        // Verifica se nenhuma das sequências invalidas abaixo 
        // foi digitada. Caso afirmativo, retorna falso
        else if (
            $cpf == '00000000000' ||
            $cpf == '11111111111' ||
            $cpf == '22222222222' ||
            $cpf == '33333333333' ||
            $cpf == '44444444444' ||
            $cpf == '55555555555' ||
            $cpf == '66666666666' ||
            $cpf == '77777777777' ||
            $cpf == '88888888888' ||
            $cpf == '99999999999'
        ) {
            return false;
            // Calcula os digitos verificadores para verificar se o
            // CPF é válido
        } else {

            for ($t = 9; $t < 11; $t++) {

                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    return false;
                }
            }

            return true;
        }
    }

    protected function validateDDD($ddd)
    {

        $ddds = [11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 24, 27, 28, 31, 32, 33, 34, 35, 37, 38, 41, 42, 43, 44, 45, 46, 47, 48, 49, 51, 53, 54, 55, 61, 62, 63, 64, 65, 66, 67, 68, 69, 71, 73, 74, 75, 77, 79, 81, 82, 83, 84, 85, 86, 87, 88, 89, 91, 92, 93, 94, 95, 96, 97, 98, 99];
        if (gettype($ddd) === "string") {
            $ddd = intval($ddd);
        }

        return in_array($ddd, $ddds);
    }

    protected function validateCelular($cel)
    {

        $cel = trim(str_replace('/', '', str_replace(' ', '', str_replace('-', '', str_replace(')', '', str_replace('(', '', $cel))))));

        if (!$this->validateDDD(substr($cel, 0, 2))) {
            return false;
        }

        $regexCel = '/[0-9]{2}[6789][0-9]{3,4}[0-9]{4}/';

        return !!preg_match($regexCel, $cel);
    }

    protected function validateCAEPF($caepf)
    {

        /*
    
        No caso do CAEPF, o DV módulo 11 corresponde ao resto da divisão por 11 do somatório da multiplicação de cada algarismo da base respectivamente por 9, 8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6 e 5, a partir da unidade.
        O resto 10 é considerado 0. Veja, abaixo, exemplo de cálculo de DV módulo 11 para o CAEPF nº 293118610001:
    
        2    9    3   1   1    8    6   1   0   0   0   1
        x    x    x   x   x    x    x   x   x   x   x   x
        6    7    8   9   2    3    4   5   6   7   8   9
        --   --   --   -   -   --   --   -   -   -   -   -
        12 + 63 + 24 + 9 + 2 + 24 + 24 + 5 + 0 + 0 + 0 + 9 = 172 ÷ 11 = 15, com resto 7
    
        2    9    3   1   1    8    6   1   0   0   0   1    7
        x    x    x   x   x    x    x   x   x   x   x   x    x
        5    6    7   8   9    2    3   4   5   6   7   8    9
        --   --   --   -   -   --   --   -   -   -   -   -   --
        10 + 54 + 21 + 8 + 9 + 16 + 18 + 4 + 0 + 0 + 0 + 8 + 63 = 211 ÷ 11 = 19, com resto 2
    
        Portanto, o CAEPF+DV seria 293.118.610/001-72.
     
        Mas há um senão: estaria sendo somado 12 ao DV encontrado. 
        E se o resultado da soma for maior do  que 99, diminui-se 100. 
        No exemplo, o DV será 72+12=84. 
    
        http://www.cadcobol.com.br/calcula_cpf_cnpj_caepf.htm
        */

        $caepf = preg_replace('/[^0-9]/', '', $caepf);

        if (strlen($caepf) != 14) {
            return false;
        }

        $digito_recebido = substr($caepf, -2);
        $caepf = substr($caepf, 0, -2);

        $multiplicador = [9, 8, 7, 6, 5, 4, 3, 2, 9, 8, 7, 6, 5];

        for ($x = 0; $x < 2; $x++) {

            $digito = 0;

            for ($i = 0; $i < strlen($caepf); $i++) {

                $digito = $digito + ((int)$caepf[$i] * $multiplicador[strlen($caepf) - ($i + 1)]);
            }

            $resto = $digito % 11;
            $caepf .= $resto == 10 ? 0 : $resto;
        }

        $digito_calculado = substr($caepf, -2) + 12;
        $digito_calculado = $digito_calculado > 99 ? $digito_calculado - 100 : $digito_calculado;

        if ($digito_recebido == $digito_calculado) {
            return true;
        }

        return false;
    }

    protected function validate_emaildomain($email): bool
    {
        // se sintaxe ERRADA NAO ACEITAR O EMAIL
        if (!$this->validateEmailSyntax($email))
            return false;

        // se TEM MX record ACEITAR O EMAIL
        if ($this->validateEmailDomainMXrecord($email))
            return true;

        // se nao tem MX record, o dominuio rtem que resolver em um IP address
        return $this->validateEmailDomain($email);
    }

    protected function validateEmailSyntax($email): bool
    {
        return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    protected function validateEmailDomainMXrecord($email): bool
    {
        $parts = explode("@", $email);
        if (count($parts) == 2) {
            $domain = array_pop($parts);
            return checkdnsrr($domain, "MX");
        }
        return false;
    }

    protected function validateEmailDomain($email): bool
    {
        $parts = explode("@", $email);
        if (count($parts) == 2) {
            $domain = array_pop($parts);
            if (filter_var(gethostbyname($domain), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)) {
                return true;
            }
        }
        return false;
    }
}
