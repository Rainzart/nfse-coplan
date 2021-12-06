<?php

namespace HaDDeR\NfseCoplan;

/**
 * Classe de comunicação com NFSe
 *
 */

use DOMDocument;
use Exception;
use HaDDeR\NfseCoplan\Common\Signer;
use HaDDeR\NfseCoplan\Common\Tools as BaseTools;
use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;

class Tools extends BaseTools
{
    protected $xsdpath;
    protected $nsxsi = 'http://www.w3.org/2001/XMLSchema-instance';
    protected $nsxsd = 'http://www.w3.org/2001/XMLSchema';
    protected $algorithm = OPENSSL_ALGO_SHA1;

    /**
     * Constructor
     * @param string $config
     * @param Certificate $cert
     * @throws Exception
     */
    public function __construct($config, Certificate $cert)
    {
        parent::__construct($config, $cert);
        $path = realpath(
            __DIR__ . '/../storage/schemes'
        );
        $this->xsdpath = $path;
    }

    /**
     * @param $rps
     * @param string $lote
     * @return string
     * @throws Exception
     */
    public function gerarNfseEnvio($rps, string $lote)
    {
        $xsd = 'nfse_v2_02.xsd';
        $operation = 'Gerarnfse';
        $rpstxt = null;
        $rpstxt .= $rps->render();
        $content = "<GerarNfseEnvio "
            . "xmlns=\"{$this->wsobj->msgns}\">"
            . "<Rps>"
            . $rpstxt
            . "</Rps>"
            . "</GerarNfseEnvio>";
        $content = $this->canonize($content);
        $content = $this->sign($content, 'InfDeclaracaoPrestacaoServico', '', 'Rps');
        Validator::isValid($content, "$this->xsdpath/{$xsd}");
        return $this->send($content, $operation);
    }

    /**
     * @param $numero
     * @param int $codigo_cancelamento - Código de cancelamento com base na tabela de Erros e alertas.
     * 1 – Erro na emissão
     * 2 – Serviço não prestado
     * 3 – Erro de assinatura
     * 4 – Duplicidade da nota
     * 5 - Erro de processamento
     * Importante: Os códigos 3 (Erro de assinatura) e 5 (Erro de processamento) são de uso restrito da Administração Tributária Municipal
     * @return string
     * @throws Exception
     */
    public function cancelarNfseEnvio($numero, int $codigo_cancelamento = 2)
    {
        $xsd = 'nfse_v2_02.xsd';
        $operation = 'Cancelarnfse';
        $content = "<CancelarNfseEnvio "
            . "xmlns=\"{$this->wsobj->msgns}\">"
            . "<Pedido>"
            . "<InfPedidoCancelamento>"
            . "<IdentificacaoNfse>"
            . "<Numero>{$numero}</Numero>"
            . "<CpfCnpj>";
        if (!empty($this->config->cnpj)) {
            $content .= "<Cnpj>{$this->config->cnpj}</Cnpj>";
        } else {
            $content .= "<Cpf>{$this->config->cpf}</Cpf>";
        }
        $content .= "</CpfCnpj>"
            . "<InscricaoMunicipal>{$this->config->im}</InscricaoMunicipal>"
            . "<CodigoMunicipio>{$this->config->cmun}</CodigoMunicipio>"
            . "</IdentificacaoNfse>"
            . "<CodigoCancelamento>{$codigo_cancelamento}</CodigoCancelamento>"
            . "</InfPedidoCancelamento>"
            . "</Pedido>"
            . "</CancelarNfseEnvio>";
        $content = $this->canonize($content);
        $content = $this->sign($content, 'InfPedidoCancelamento', '', 'Pedido');
        Validator::isValid($content, "$this->xsdpath/{$xsd}");
        return $this->send($content, $operation);
    }

    /**
     * @param $numero
     * @param $serie
     * @param $tipo
     * @return string
     * @throws Exception
     */
    public function consultarNfseRpsEnvio($numero, $serie, $tipo)
    {
        $xsd = 'nfse_v2_02.xsd';
        $operation = 'Consultarnfseporrps';
        $content = "<ConsultarNfseRpsEnvio "
            . "xmlns=\"{$this->wsobj->msgns}\">"
            . "<IdentificacaoRps>"
            . "<Numero>{$numero}</Numero>"
            . "<Serie>{$serie}</Serie>"
            . "<Tipo>{$tipo}</Tipo>"
            . "</IdentificacaoRps>"
            . "<Prestador>"
            . "<CpfCnpj>";
        if (!empty($this->config->cnpj)) {
            $content .= "<Cnpj>{$this->config->cnpj}</Cnpj>";
        } else {
            $content .= "<Cpf>{$this->config->cpf}</Cpf>";
        }
        $content .= "</CpfCnpj>"
            . "<InscricaoMunicipal>{$this->config->im}</InscricaoMunicipal>"
            . "</Prestador>"
            . "</ConsultarNfseRpsEnvio>";
        $content = $this->canonize($content);
        Validator::isValid($content, "$this->xsdpath/{$xsd}");
        return $this->send($content, $operation);
    }

    protected function canonize($content)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($content);
        return $dom->C14N(false, false, null, null);
    }

}
