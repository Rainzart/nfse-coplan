<?php

namespace HaDDeR\NfseCoplan\Danfse;

use Exception;
use NFePHP\Common\DOMImproved as Dom;

class Danfse
{
    /**
     * @var Dom
     */
    protected $dom;
    /**
     * @var PdfBase
     */
    private $pdf;
    private $infNfse;
    private $infDecServico;
    /**
     * @var object
     */
    private $wsobj;
    /**
     * @var mixed|string|null
     */
    private $brasao;
    /**
     * @var array
     */
    private $cidades_ibge = [];

    protected $desc = 4; // altura célula descrição
    protected $fdes = 9; // tamanho fonte descrição

    protected $cell = 4; // altura célula dado
    protected $fcel = 10; // tamanho fonte célula
    private $logo;


    /**
     * @throws Exception
     */
    public function __construct($xml, string $logo = null, array $cidades_ibge = [])
    {
        $this->setCidades($cidades_ibge);
        $this->loadDoc($xml);
        $this->pdf = new PdfBase();
        $this->pdf->AddPage();
        $this->pdf->SetAutoPageBreak(true);
        $this->wsobj = $this->loadWsobj($this->infNfse->OrgaoGerador->CodigoMunicipio);
        $this->brasao = realpath(__DIR__ . '/../../storage/images/' . $this->wsobj->brasao);
    }

    /**
     * @throws Exception
     */
    private function loadDoc($xml)
    {
//        $this->xml = $xml;
        if (!empty($xml)) {
            $this->dom = simplexml_load_string($xml);
            if (!isset($this->dom->CompNfse) and !isset($this->dom->ListaNfse)) {
                throw new Exception('Isso não é uma NFSe.');
            }
            if(isset($this->dom->CompNfse)) {
                $this->infNfse = $this->dom->CompNfse->Nfse->InfNfse;
                $this->infDecServico = $this->dom->CompNfse->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico;
            } else {
                $this->infNfse = $this->dom->ListaNfse->CompNfse->Nfse->InfNfse;
                $this->infDecServico = $this->dom->ListaNfse->CompNfse->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico;
            }
        }
    }

    public function render($dest = 'I')
    {
        //TODO Fazer algumas validações
        $this->cabecalho()
            ->dadosPrestador()
            ->identificacaoNFse()
            ->dadosTomador()
            ->descricaoServicos()
            ->dadosImpostos()
            ->informacoesComplementares()
            ->traco()
            ->protocoloEntrega();

//        $this->cancelada();

        $nameFile = 'NFSe_' . $this->infNfse->Numero;
        return $this->pdf->Output($nameFile . '.pdf', $dest);

    }

    private function cabecalho()
    {
        $this->pdf->SetFont('Calibri', 'B', 8.8);
        $this->pdf->Cell(22, 20, '', 'TLB');
        $this->pdf->Image($this->brasao, 16, 6.3, 15, 15);

        $this->pdf->setCellPaddings('', 0.5);
        $txt = 'PREFEITURA MUNICIPAL DE AGUA BOA MT' . PHP_EOL;
        $txt .= 'SECRETARIA MUNICIPAL DE FINANÇAS' . PHP_EOL;
        $txt .= 'AV. PLANALTO, 410, CENTRO' . PHP_EOL;
        $txt .= 'Telefones: (66) 3468-6400' . PHP_EOL;
        $txt .= 'CNPJ: 15.023.898/0001-90';
        $this->pdf->MultiCell(126, 20, $txt, 'TBR', 'L', false, false, '', '', true, 0, false, true, 20, 'T', '');

        $this->pdf->setCellPaddings('', 1);
        $this->pdf->SetFont('Calibri', '', 9);
        $txt = 'Número da Nota Fiscal de Serviço' . PHP_EOL;
        $txt .= 'Série Eletrônica';
        $customX = $this->pdf->GetX();
        $this->pdf->MultiCell(49, 10, $txt, 'TR', 'C', false, true, '', '', true, 0, false, true, 20, 'T', '');

        $this->pdf->SetFont('Calibri', 'B', 14);
        $this->pdf->MultiCell(49, 10, $this->infNfse->Numero, 'BR', 'C', false, true, $customX, '', true, 0, false, true, 20, 'T', '');
        return $this;
    }

    private function dadosPrestador()
    {
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('Calibri', 'B', 8.8);
        $this->pdf->Cell(21.1, 25.5, '', 'TLBR');

        $imgCustomX = $this->pdf->GetX();
        $imgCustomY = $this->pdf->GetY();
        //TODO verificar logo
        $this->pdf->Image($this->logo, $imgCustomX - 19.5, $imgCustomX - 5, 18, 18);

        $customX = $this->pdf->GetX();
        $this->pdf->SetCellPaddings(2, 0, '', 0);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(175.9, 4.2, 'Dados do Prestador', 'TRB', true, 'L', false, '', 0, true);

        $this->pdf->SetX($customX);
        $this->pdf->SetFont('Calibri', '', 10);
        $this->pdf->Cell(175.9, 4, $this->infNfse->PrestadorServico->RazaoSocial, 'R', true, 'L', false, '', 0, true);
        $this->pdf->SetX($customX);
        $this->pdf->Cell(175.9, 4, $this->infNfse->PrestadorServico->NomeFantasia, 'R', true, 'L', false, '', 0, true);

        $this->pdf->SetFont('Calibri', '', 9);
        if (isset($this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj)) {
            $cpfcnpj = mask((string)$this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj, '##.###.###/####-##');
        } else {
            $cpfcnpj = mask((string)$this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cpf, '###.###.###-##');
        }
        $txt = '<strong>CPF/CNPJ:</strong> ' . $cpfcnpj;
        $this->pdf->writeHTMLCell(60, 4, $customX, '', $txt, '', false, false, false, 'L', false);
        $txt = '<strong>Inscrição Municipal:</strong> ' . $this->infNfse->PrestadorServico->IdentificacaoPrestador->InscricaoMunicipal;
        $this->pdf->writeHTMLCell(60, 4, '', '', $txt, '', false, false, false, 'L', false);
        $txt = '<strong>Inscrição Estadual:</strong> ';
        $this->pdf->writeHTMLCell(55.9, 5.3, '', '', $txt, 'R', true, false, false, 'L', false);

        $txt = '<strong>End.:</strong> ' . $this->infNfse->PrestadorServico->Endereco->Endereco . ', ';
        $txt .= 'Nº ' . $this->infNfse->PrestadorServico->Endereco->Numero . ', ' . $this->infNfse->PrestadorServico->Endereco->Bairro;
        $this->pdf->writeHTMLCell(105, 4, $customX, '', $txt, '', false, false, false, 'L', false);
        $txt = '<strong>Complemento:</strong> ' . $this->infNfse->PrestadorServico->Endereco->Complemento;
        $this->pdf->writeHTMLCell(70.9, 4, '', '', $txt, 'R', true, false, false, 'L', false);

        $txt = '<strong>Cidade:</strong> ' . $this->resolveCidade($this->infNfse->PrestadorServico->Endereco->CodigoMunicipio) . ' - ' . $this->infNfse->PrestadorServico->Endereco->Uf;
        $this->pdf->writeHTMLCell(50, 4, $customX, '', $txt, 'B', false, false, false, 'L', false);
        $txt = '<strong>Telefone:</strong> ' . $this->infNfse->PrestadorServico->Contato->Telefone;
        $this->pdf->writeHTMLCell(50, 4, '', '', $txt, 'B', false, false, false, 'L', false);
        $txt = '<strong>Email:</strong> ' . $this->infNfse->PrestadorServico->Contato->Email;
        $this->pdf->writeHTMLCell(75.9, 4, '', '', $txt, 'BR', true, false, false, 'L', false);

        return $this;
    }

    public function identificacaoNFse()
    {
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('Calibri', 'B', 8.8);
        $customYQrCode = $this->pdf->GetY();
        $customX = $this->pdf->GetX() + 171.5;
        $this->pdf->Cell(171.5, 5, 'Identificação da Nota Fiscal Eletrônica', 'TLBR', true);
        $this->pdf->SetFont('Calibri', '', 8.8);
//        $this->pdf->Cell(65.5, 16.3, 'Identificação da Nota Fiscal Eletrônica', 'LBR', true, 'C');
        $txt = 'Natureza da Operação' . PHP_EOL;
        $txt .= mb_strtoupper($this->listExigibilidadeIss((int)$this->infDecServico->Servico->ExigibilidadeISS)) . PHP_EOL;
        $txt .= 'Número do RPS' . PHP_EOL;
        $txt .= $this->infDecServico->Rps->IdentificacaoRps->Numero ?? null;
        $this->pdf->MultiCell(65.5, 16.3, $txt, 'LBR', 'C', false, false, '', '', true, 0, false, true, 20, 'T', '');

        $txt = 'Data e Hora de Emissão da NFS-e' . PHP_EOL;
        $txt .= date('d/m/Y H:i:s', strtotime($this->infNfse->DataEmissao)) . PHP_EOL;
        $txt .= 'Data de Emissão da Nota Fiscal' . PHP_EOL;
        $txt .= isset($this->infDecServico->Rps->DataEmissao) ? date('d/m/Y', strtotime($this->infDecServico->Rps->DataEmissao)) : null . PHP_EOL;
        $this->pdf->MultiCell(53, 16.3, $txt, 'BR', 'C', false, false, '', '', true, 0, false, true, 20, 'T', '');

        $txt = 'Código de Autenticidade' . PHP_EOL;
        $txt .= ($this->infNfse->CodigoVerificacao ?? null) . PHP_EOL;
        $txt .= 'Série da Nota Fiscal' . PHP_EOL;
        $txt .= ($this->infDecServico->Rps->IdentificacaoRps->Serie ?? null);
        $this->pdf->MultiCell(53, 16.3, $txt, 'B', 'C', false, false, '', '', true, 0, false, true, 20, 'T', '');

        $style = [
            'border' => 1,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        ];
        $this->pdf->write2DBarcode('https://www.gp.srv.br/tributario_aguaboa/servlet/tnfse_validacao?' . $this->infNfse->CodigoVerificacao, 'QRCODE,M', $customX, $customYQrCode, 26.4, 21.3, $style, 'N', false);
        return $this;
    }

    public function dadosTomador()
    {
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(197, 5, 'Dados do Tomador de Serviço', 'TLBR', true);
        //Linha 1
        $this->pdf->SetFont('Calibri', 'B', $this->fdes);
        $this->pdf->Cell(42.2, $this->desc, 'CNPJ/CPF', 'LR', false);
        $this->pdf->Cell(36.1, $this->desc, 'Inscrição Estadual', 'LR', false);
        $this->pdf->Cell(38.1, $this->desc, 'Inscrição Municipal', 'LR', false);
        $this->pdf->Cell(80.6, $this->desc, 'Razão Social', 'LR', true);
        $tomador = $this->infDecServico->Tomador;
        if (isset($tomador->IdentificacaoTomador->CpfCnpj->Cnpj)) {
            $cpfcnpj = mask((string)$tomador->IdentificacaoTomador->CpfCnpj->Cnpj, '##.###.###/####-##');
        } else {
            $cpfcnpj = mask((string)$tomador->IdentificacaoTomador->CpfCnpj->Cpf, '###.###.###-##');
        }
//        dd($prestador->RazaoSocial);
        $this->pdf->SetFont('Calibri', '', $this->fcel);
        $this->pdf->Cell(42.2, $this->cell, $cpfcnpj, 'LBR', false);
        $this->pdf->Cell(36.1, $this->cell, '', 'LBR', false);
        $this->pdf->Cell(38.1, $this->cell, '', 'LBR', false);
        $this->pdf->Cell(80.6, $this->cell, $tomador->RazaoSocial, 'LBR', true);

        //Linha 2
        $this->pdf->SetFont('Calibri', 'B', $this->fdes);
        $this->pdf->Cell(59.3, $this->desc, 'Endereço', 'LR', false);
        $this->pdf->Cell(19, $this->desc, 'Número', 'LR', false);
//        $this->pdf->Cell(38.0, $this->desc, 'Complemento', 'LR', false);
        $this->pdf->Cell(69.9, $this->desc, 'Complemento', 'LR', false);
        $this->pdf->Cell(48.8, $this->desc, 'Bairro', 'LR', true);

        $this->pdf->SetFont('Calibri', '', $this->fcel);
        $this->pdf->Cell(59.3, $this->cell, $tomador->Endereco->Endereco, 'LBR', false);
        $this->pdf->Cell(19, $this->cell, $tomador->Endereco->Numero, 'LBR', false);
        $this->pdf->Cell(69.9, $this->cell, $tomador->Endereco->Complemento, 'LBR', false);
        $this->pdf->Cell(48.8, $this->cell, $tomador->Endereco->Bairro, 'LBR', true);

        //Linha 3
        $this->pdf->SetFont('Calibri', 'B', $this->fdes);
        $this->pdf->Cell(40.2, $this->desc, 'CEP', 'LR', false);
        $this->pdf->Cell(38.1, $this->desc, 'Cidade', 'LR', false);
        $this->pdf->Cell(14.7, $this->desc, 'UF', 'LR', false);
        $this->pdf->Cell(34.1, $this->desc, 'Telefone', 'LR', false);
        $this->pdf->Cell(69.9, $this->desc, 'Email', 'LR', true);

        $this->pdf->SetFont('Calibri', '', $this->fcel);
        $this->pdf->Cell(40.2, $this->cell, $tomador->Endereco->Cep, 'LBR', false);
        $this->pdf->Cell(38.1, $this->cell, $this->resolveCidade($tomador->Endereco->CodigoMunicipio), 'LBR', false);
        $this->pdf->Cell(14.7, $this->cell, $tomador->Endereco->Uf, 'LBR', false);
        $this->pdf->Cell(34.1, $this->cell, $tomador->Contato->Telefone, 'LBR', false);
        $this->pdf->Cell(69.9, $this->cell, $tomador->Contato->Email, 'LBR', true);

        return $this;
    }

    public function descricaoServicos()
    {
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(197, 5.5, 'Descrição dos Serviços', 'TLBR', true);
        $this->pdf->SetFont('Calibri', '', 7);
        $customX = $this->pdf->GetX();
        $this->pdf->MultiCell(197, 48.9, $this->infDecServico->Servico->Discriminacao, 'TLBR', 'L', false, true, '', '', true, 0, false, true, 20, 'T', '');
        $this->pdf->SetX($customX);
        $this->pdf->SetFont('Calibri', 'B', 9);
        $this->pdf->Cell(172, 5.5, 'VALOR TOTAL DA NFS-e: R$', 'LB', false, 'R');
        $this->pdf->SetFont('Calibri', '', 10);
        $this->pdf->Cell(25, 5.5, number_format((float)$this->infNfse->ValoresNfse->ValorLiquidoNfse, 2, ',', '.'), 'BR', true, 'R');

        return $this;
    }

    public function dadosImpostos()
    {
        $this->pdf->Ln(0.8);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(197, 5.5, 'Imposto Sobre Serviços de Qualquer Natureza - ISSQN', 'TLBR', true);

        $this->pdf->SetFont('Calibri', 'B', 8.5);
        $this->pdf->Cell(128.9, 5.5, 'Atividade do Município', 'LR');
        $this->pdf->Cell(21.3, 5.5, 'Alíquota', 'R', false, 'C');
        $this->pdf->Cell(25.4, 5.5, 'Item 116/2003', 'R', false, 'C');
        $this->pdf->Cell(21.4, 5.5, 'CNAE', 'R', true, 'C');

        $this->pdf->SetFont('Calibri', '', 6);
        $txt = $this->listItensServicos((int)$this->infDecServico->Servico->ItemListaServico);
        $this->pdf->MultiCell(128.9, 11.3, $txt, 'LBR', 'L', false, false);

        $this->pdf->SetFont('Calibri', '', 10);
        $this->pdf->Cell(21.3, 11.3, number_format((float)$this->infNfse->ValoresNfse->Aliquota, 2, ',', '.'), 'BR', false, 'C');
        $this->pdf->Cell(25.4, 11.3, $this->infDecServico->Servico->ItemListaServico, 'BR', false, 'C');
        $this->pdf->Cell(21.4, 11.3, mask((string)$this->infDecServico->Servico->CodigoCnae, '####-#/##'), 'BR', true, 'C');


        $valores = [
            'Valor Total dos Serviços' => number_format((float)$this->infDecServico->Servico->Valores->ValorServicos, 2, ',', '.'),
            'Base de Cálculo' => number_format((float)$this->infNfse->ValoresNfse->BaseCalculo, 2, ',', '.'),
            'Desconto Incondicionado' => number_format((float)$this->infDecServico->Servico->Valores->DescontoIncondicionado, 2, ',', '.'),
            'Desconto Condicionado' => number_format((float)$this->infDecServico->Servico->Valores->DescontoCondicionado, 2, ',', '.'),
            'Deduções (Material)' => number_format(0, 2, ',', '.'),
            'Deduções Base de Cálculo' => number_format(0, 2, ',', '.'),
            'ISSQN Devido' => number_format((float)$this->infNfse->ValoresNfse->ValorIss, 2, ',', '.'),
        ];
        $this->pdfValores($valores);
        $this->pdf->SetFont('Calibri', 'B', 8.5);
        $this->pdf->Cell(155.9, 5, 'ISSQN Retido', 'LB', false, 'L', false, '', false, true);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(41.1, 5, ((int)$this->infDecServico->Servico->IssRetido == 1 ? 'SIM' : 'NÃO'), 'BR', true, 'R', false, '', false, true);

        $this->pdf->Ln(0.8);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(197, 5.6, 'Retenções na Fonte', 'TLBR', true);

        $this->pdf->SetFont('Calibri', 'B', 8.5);
        $cellSize = 4;
        $this->pdf->Cell(29.5, $cellSize, 'PIS', 'LR', false, 'L', false, '', false, true);
        $this->pdf->Cell(27.7, $cellSize, 'COFINS', 'R', false, 'L', false, '', false, true);
        $this->pdf->Cell(27.5, $cellSize, 'INSS', 'R', false, 'L', false, '', false, true);
        $this->pdf->Cell(27.3, $cellSize, 'IRRF', 'R', false, 'L', false, '', false, true);
        $this->pdf->Cell(27.8, $cellSize, 'CSLL', 'R', false, 'L', false, '', false, true);
        $this->pdf->Cell(27.5, $cellSize, 'Outras Retenções', 'R', false, 'L', false, '', false, true);
        $this->pdf->Cell(29.7, $cellSize, 'ISSQN', 'R', true, 'L', false, '', false, true);

        $this->pdf->SetFont('Calibri', '', 10);
        $cellSize = 4.4;
        $this->pdf->Cell(29.5, $cellSize, number_format((float)$this->infDecServico->Servico->Valores->ValorPis, 2, ',', '.'), 'BLR', false, 'R', false, '', false, true);
        $this->pdf->Cell(27.7, $cellSize, number_format((float)$this->infDecServico->Servico->Valores->ValorCofins, 2, ',', '.'), 'BR', false, 'R', false, '', false, true);
        $this->pdf->Cell(27.5, $cellSize, number_format((float)$this->infDecServico->Servico->Valores->ValorInss, 2, ',', '.'), 'BR', false, 'R', false, '', false, true);
        $this->pdf->Cell(27.3, $cellSize, number_format((float)$this->infDecServico->Servico->Valores->ValorIr, 2, ',', '.'), 'BR', false, 'R', false, '', false, true);
        $this->pdf->Cell(27.8, $cellSize, number_format((float)$this->infDecServico->Servico->Valores->ValorCsll, 2, ',', '.'), 'BR', false, 'R', false, '', false, true);
        $this->pdf->Cell(27.5, $cellSize, number_format((float)$this->infDecServico->Servico->Valores->OutrasRetencoes, 2, ',', '.'), 'BR', false, 'R', false, '', false, true);
        $this->pdf->Cell(29.7, $cellSize, number_format((float)$this->infDecServico->Servico->Valores->ValorIss, 2, ',', '.'), 'BR', true, 'R', false, '', false, true);

        $this->pdf->SetFont('Calibri', 'B', 9);
        $this->pdf->Cell(167, 5.6, 'Valor líquido da Nota Fiscal', 'TLB', false);
        $this->pdf->SetFont('Calibri', '', 10);
        $this->pdf->Cell(30, 5.6, number_format((float)$this->infNfse->ValoresNfse->ValorLiquidoNfse, 2, ',', '.'), 'TBR', true, 'R');

        return $this;
    }

    public function informacoesComplementares()
    {
        $this->pdf->Ln(0.4);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(197, 5.5, 'Informações Complementares', 'TLBR', true);
//        dd($this->pdf->getMargins());
        $this->pdf->SetFont('Calibri', '', 6);
        $customX = $this->pdf->GetX();
        $this->pdf->setCellPaddings(2, 2, 2, 2);
        $this->pdf->MultiCell(197, 15.7, $this->infNfse->OutrasInformacoes, 'TLBR', 'L', false, true, '', '', true, 0, false, true, 20, 'T', '');

        return $this;
    }

    public function traco()
    {
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('Calibri', '', 6.5);
        $this->pdf->Cell(85, 3, 'Gerado Por:', '', false, 'C', false, '', false, true);
        $this->pdf->Cell(87, 3, 'Impresso Por:', '', true, 'C', false, '', false, true);
//        $this->pdf->Cell(98.5, 5.5, 'Gerado Por:', 'TLBR', true, 'L', false, '', false, true);

        $this->pdf->SetFont('Calibri', '', 25);
        $this->pdf->Cell(0, 1, str_pad('-', 83, ' -', STR_PAD_RIGHT), '', true, '', false, '', false, true);

        return $this;
    }

    public function protocoloEntrega()
    {
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->Cell(197, 6.0, 'Protocolo de entrega de Nota Fiscal Eletrônica', 'TLBR', true, '', false, '', false, true);

        $customYQrCode = $this->pdf->GetY();
        $customX = $this->pdf->GetX() + 150.4;
        $this->pdf->SetFont('Calibri', 'B', 8.5);
        $this->pdf->Cell(59.7, 4, 'Natureza da Operação', 'LR', false, 'L', false, '', false, true);
        $this->pdf->Cell(48.3, 4, 'Data e Hora de Emissão da NFS-e', 'LR', false, 'C', false, '', false, true);
        $this->pdf->Cell(42.2, 4, 'Código de Autenticidade', 'LR', true, 'C', false, '', false, true);

        $this->pdf->SetFont('Calibri', '', 10);
        $this->pdf->Cell(59.7, 5.0, mb_strtoupper($this->listExigibilidadeIss((int)$this->infDecServico->Servico->ExigibilidadeISS)), 'LR', false, 'L', false, '', false, true);
        $this->pdf->Cell(48.3, 5.0, date('d/m/Y', strtotime($this->infNfse->DataEmissao)), 'LR', false, 'C', false, '', false, true);
        $this->pdf->Cell(42.2, 5.0, ($this->infNfse->CodigoVerificacao ?? null), 'LR', true, 'C', false, '', false, true);

        $this->pdf->SetFont('Calibri', '', 6.2);
        $txt = 'Recebi(emos) de ';
        $txt .= $this->infNfse->PrestadorServico->RazaoSocial . ' ';
        if (isset($this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj)) {
            $txt .= mask((string)$this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cnpj, '##.###.###/####-##');
        } else {
            $txt .= mask((string)$this->infNfse->PrestadorServico->IdentificacaoPrestador->CpfCnpj->Cpf, '###.###.###-##');
        }
        $txt .= ', Todos o(s) serviço(s) relacionados nesta Nota Fiscal de Serviço Eletrônica';
        $this->pdf->Cell(150.2, 3, $txt, 'TLR', true, 'L', false, '', false, true);
        $this->pdf->SetTextColor(255, 0, 0);
        $this->pdf->Cell(150.2, 3.8, 'A autenticidade deste documento poderá ser realizada pelo endereço https://www.gp.srv.br/tributario_aguaboa/servlet/portal_serv_servico?9,60', 'LR', true, 'L', false, '', false, true);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Calibri', '', 10);
        $this->pdf->Cell(150.2, 3, '', 'LR', true, 'C', false, '', false, true);
        $this->pdf->Cell(60, 3.8, '___/___/______', 'L', false, 'C', false, '', false, true);
        $this->pdf->Cell(90.2, 3.8, '________________________________________', 'R', true, 'C', false, '', false, true);

        $this->pdf->SetFont('Calibri', 'B', 7);
        $this->pdf->Cell(55, 3.8, 'Data', 'LB', false, 'C', false, '', false, true);
        $this->pdf->Cell(95.2, 3.8, 'Nome e Número do CPF do Tomador', 'RB', true, 'C', false, '', false, true);

        $this->pdf->SetFont('Calibri', '', 8);
        $txt = 'Número da Nota Fiscal de Serviço' . PHP_EOL;
        $txt .= 'Série Eletrônica';
        $this->pdf->setCellPaddings(0, 0, 0, 0);
        $this->pdf->MultiCell(46.6, 7, $txt, 'R', 'C', false, true, $customX, $customYQrCode, true, 0, false, true, 20, 'T', '');

        $this->pdf->SetFont('Calibri', 'B', 10);
        $this->pdf->MultiCell(46.6, 4, $this->infNfse->Numero, 'R', 'C', false, true, $customX, '', true, 0, false, true, 20, 'T', '');

        $style = [
            'border' => false,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        ];
        $customY = $this->pdf->GetY();
        $this->pdf->write2DBarcode('https://www.gp.srv.br/tributario_aguaboa/servlet/tnfse_validacao?' . $this->infNfse->CodigoVerificacao, 'QRCODE,M', $customX, '', 46.6, 14.9, $style, 'B', false);
        $this->pdf->SetXY($customX, $customY);
        $this->pdf->Cell(46.6, 14.9, '', 'RB', true, 'C', false, '', false, true);

    }

    /**
     * @throws Exception
     */
    public function loadWsobj(string $cmun)
    {
        $path = realpath(__DIR__ . "/../../storage/urls_webservices.json");
        $urls = json_decode(file_get_contents($path), true);
        if (empty($urls[$cmun])) {
            throw new Exception("Não localizado parâmetros para esse municipio.");
        }
        return (object)$urls[$cmun];
    }

    public function setCidades(array $cidades)
    {
        $this->cidades_ibge = $cidades;
    }

    private function resolveCidade(string $codigoMunicipio)
    {
        return $this->cidades_ibge[$codigoMunicipio] ?? $codigoMunicipio;
    }

    private function pdfValores(array $valores)
    {
        $this->pdf->setCellPaddings(2, '0', 2, 0);
        $cellSize = 4.18;
        foreach ($valores as $key => $value) {
            $this->pdf->SetFont('Calibri', 'B', 8.5);
            $this->pdf->Cell(155.9, $cellSize, $key, 'L', false, 'L', false, '', false, true);
            $this->pdf->Cell(10, $cellSize, 'R$', '', false, 'L', false, '', false, true);
            $this->pdf->SetFont('Calibri', '', 10);
            $this->pdf->Cell(31.1, $cellSize, $value, 'R', true, 'R', false, '', false, true);
        }
    }

    private function cancelada()
    {
        $customY = 25;
        $this->pdf->SetY($customY);
        $this->pdf->SetTextColor(255, 0, 0);
        $this->pdf->SetFont('Calibri', 'B', 32);
        $this->pdf->Cell(197, 20, 'CANCELADA', '', false, 'C');
        $this->pdf->SetY($customY + 100);
        $this->pdf->Cell(197, 20, 'CANCELADA', '', false, 'C');
        $this->pdf->SetY($customY + 238);
        $this->pdf->Cell(197, 20, 'CANCELADA', '', false, 'C');
    }

    private function listItensServicos(int $id)
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

    private function listExigibilidadeIss(int $id)
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