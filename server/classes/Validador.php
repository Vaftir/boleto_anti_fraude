<?php

// Importando as classes necessárias
require_once __DIR__ . '/LimiteRequisicoes.php';
require_once __DIR__ . '/CustomSOAPClient.php';
require_once __DIR__ . '/ViaCep.php';

/**
 * Class Validador
 * Contém métodos para validação de CPF, CNPJ e CEP
 * @package server\classes
 * @author Yago Faria
 * @version 1.0
 */

class Validador {
    
    // Valida se um valor é um CPF ou CNPJ válido
    public static function validarCPFouCNPJ($valor) {
        // Remove todos os caracteres não numéricos
        $valor_formatado = preg_replace('/\D/', '', $valor);

        // Verifica se é CPF (11 dígitos) ou CNPJ (14 dígitos) e chama a validação correta
        if (strlen($valor_formatado) === 11) {
            return self::validarCPF($valor_formatado) ? $valor_formatado : null;
        } elseif (strlen($valor_formatado) === 14) {
            return self::validarCNPJ($valor_formatado) ? $valor_formatado : null;
        }
        return null;
    }

    // Validação de CPF
    private static function validarCPF($cpf) {
        // Elimina CPFs inválidos conhecidos
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;

        // Validação dos dígitos verificadores
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

    // Validação de CNPJ
    private static function validarCNPJ($cnpj) {
        // Remove todos os caracteres não numéricos
        $cnpj = preg_replace('/\D/', '', $cnpj);

        // Verifica se o CNPJ tem exatamente 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Elimina CNPJs inválidos conhecidos (como 11111111111111, etc.)
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Validação dos dígitos verificadores
        for ($t = 12; $t < 14; $t++) {
            $d = 0;
            $p = $t - 7;
            
            for ($c = 0; $c < $t; $c++) {
                $d += $cnpj[$c] * $p;
                $p = ($p === 2) ? 9 : $p - 1;
            }

            $d = ((10 * $d) % 11) % 10;
            if ($cnpj[$t] != $d) {
                return false;
            }
        }
        return true;
    }

    // Validação do CEP usando a classe ViaCep
    public static function verificarCEP($cep) {
        return ViaCep::verificar($cep);
    }
}
