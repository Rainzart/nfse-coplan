<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use NFePHP\Common\Certificate;

use HaDDeR\NfseCoplan\Rps;
use HaDDeR\NfseCoplan\Tools;

try {

    $config = new stdClass();
    $config->cnpj = '12345678000199';
    $config->im = '12345'; // Inscrição Municial
    $config->cmun = '1234567'; // Código IBGE
    $config->razao = 'Razão Social';
    $config->tpamb = 2; //1 - Produção, 2 - Homologação
//    $config->formatOutput = true; // Para debug retorna XML formatado

    $configJson = json_encode($config);
    $content = file_get_contents('certs/certificado.pfx');
    $password = 'senha_certificado';
    $cert = Certificate::readPfx($content, $password);

    $tools = new Tools($configJson, $cert);

    $response = $tools->cancelarNfseEnvio('202100000000001');
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;
    $dom->loadXML($response);
    foreach ($dom->getElementsByTagName('MensagemRetorno') as $key => $value) {
        dump($value->getElementsByTagName('Codigo')->item(0)->nodeValue);
        $msg = $value->getElementsByTagName('Mensagem')->item(0)->nodeValue.'<br>';
        $msg .= $value->getElementsByTagName('Correcao')->item(0)->nodeValue;
        echo ($msg);
    }
//    dump($dom->getElementsByTagName('ListaMensagemRetorno')->item(0)->textContent);
    dd($response,'fim');

} catch (Exception $e) {
    echo $e->getMessage();
}