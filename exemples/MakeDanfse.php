<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include __DIR__ . '/../vendor/autoload.php';

use HaDDeR\NfseCoplan\Danfse\Danfse;

try {
    //Considerando que praticamente nenhuma prefeitura implementa a tag de nome da cidade, uma das soluções é informar os códigos IBGEs envolvidos
    $cidades = [
        '5100201' => 'Água Boa',
        '3157807' => 'SANTA LUZIA',
    ];

    $xml = file_get_contents('certs/XML+202100000000004.xml');
    $pdf = new Danfse($xml, '', $cidades);
//    header('Content-Type: application/pdf');
    return $pdf->render();
//    $dom = new DOMDocument();
//    $dom->preserveWhiteSpace = false;
//    $dom->formatOutput = false;
//    $dom->loadXML($response);
//    foreach ($dom->getElementsByTagName('MensagemRetorno') as $key => $value) {
//        dump($value->getElementsByTagName('Codigo')->item(0)->nodeValue);
//        $msg = $value->getElementsByTagName('Mensagem')->item(0)->nodeValue.'<br>';
//        $msg .= $value->getElementsByTagName('Correcao')->item(0)->nodeValue;
//        echo ($msg);
//    }
    dd($xml,'fim');

} catch (Exception $e) {
    echo $e->getMessage();
}