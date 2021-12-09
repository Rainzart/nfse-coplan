<?php
if (!function_exists('listExigibilidadeIss')) {
    function listExigibilidadeIss(int $id = null)
    {
        $dados = [
            1 => 'Exigível',
            2 => 'Não incidência',
            3 => 'Isenção',
            4 => 'Exportação',
            5 => 'Imunidade',
            6 => 'Exigibilidade Suspensa por Decisão Judicial',
            7 => 'Exigibilidade Suspensa por Processo Administrativo',
        ];
        if (!empty($id)) {
            return $dados[$id];
        } else {
            return $id;
        }
    }
}
if (!function_exists('listItensServicos')) {
    function listItensServicos(int $id = null)
    {
        $dados = [
            2501 => '25.01 - Funerais, inclusive fornecimento de caixão, urna ou esquifes; aluguel de capela; transporte do corpo cadavérico; fornecimento de flores, coroas e outros paramentos; desembaraço de certidão de óbito; fornecimento de véu, essa e outros adornos; embalsamento, embelezamento, conservação ou restauração de cadáveres.',
        ];
        if (!empty($id)) {
            return $dados[$id];
        } else {
            return $id;
        }
    }
}
if (!function_exists('listSimNao')) {
    function listSimNao($id = null)
    {
        $dados = [
            1 => 'SIM',
            2 => 'NÃO',
        ];
        if (!empty($id)) {
            return $dados[$id];
        } else {
            return $id;
        }
    }
}