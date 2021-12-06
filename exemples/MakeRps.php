<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use NFePHP\Common\Certificate;

use HaDDeR\NfseCoplan\Rps;
use HaDDeR\NfseCoplan\Tools;

try {

    bcscale(2);

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

    $arps = [];

    $std = new stdClass();
//    $std->version = '2.01';
    $std->lote = '123';
    $std->Rps = new stdClass();
    $std->Rps->DataEmissao = Carbon::now()->format('Y-m-d');
    $std->Rps->Status = '1';    //1 - Normal, 2 - Cancelado

    $std->Rps->IdentificacaoRps = new stdClass();
    $std->Rps->IdentificacaoRps->Numero = 1;
    $std->Rps->IdentificacaoRps->Serie = '1'; //Deve ser string numerico?
    $std->Rps->IdentificacaoRps->Tipo = 1; //1 - RPS, 2 - Nota Fiscal Conjugada (Mista), 3 - Cupom

    $std->Competencia = Carbon::now()->format('Y-m-d');

    $std->Servico = new stdClass();
    $std->Servico->IssRetido = 2;//1 - Sim, 2 - Não
//    $std->Servico->ResponsavelRetencao = 1;// 1 Tomador, 2 Intermediario
    $std->Servico->CodigoTributacaoMunicipio = '1111'; //Consultar na prefeitura
    $std->Servico->CodigoCnae = '1111111';
    $std->Servico->ItemListaServico = '1111'; //Consultar na prefeitura
    $std->Servico->Discriminacao = 'Descriminação do Serviço';
    $std->Servico->CodigoMunicipio = '1111111'; //IBGE
    $std->Servico->ExigibilidadeISS = 1; //1 – Exigível, 2 – Não incidência, 3 – Isenção, 4 – Exportação, 5 – Imunidade, 6 – Exigibilidade Suspensa por Decisão Judicial, 7 – Exigibilidade Suspensa por Processo Administrativo

    $std->Servico->MunicipioIncidencia = $config->cmun;

    $std->Servico->Valores = new stdClass();
    $std->Servico->Valores->ValorServicos = '10500.00';
    //TODO Quando optante do Simples, informar as tags Aliquota e ValorIss, se não optante, ocultar
//    $std->Servico->Valores->Aliquota = 4.00;
//    $std->Servico->Valores->ValorIss = bcmul($std->Servico->Valores->ValorServicos, bcdiv($std->Servico->Valores->Aliquota, 100));

//    $std->Servico->Valores->ValorDeducoes = 0.00;
//    $std->Servico->Valores->ValorPis = 0.00;
//    $std->Servico->Valores->ValorCofins = 0.00;
//    $std->Servico->Valores->ValorInss = 0.00;
//    $std->Servico->Valores->ValorIr = 0.00;
//    $std->Servico->Valores->ValorCsll = 0.00;
//    $std->Servico->Valores->OutrasRetencoes = 0.00;
//    $std->Servico->Valores->DescontoIncondicionado = 0.00;
//    $std->Servico->Valores->DescontoCondicionado = 0.00;

    $std->Prestador = new stdClass();
    $std->Prestador->InscricaoMunicipal = $config->im;
    $std->Prestador->CpfCnpj = new stdClass();
    $std->Prestador->CpfCnpj->Cnpj = $config->cnpj;

    $std->Tomador = new stdClass();
    $std->Tomador->RazaoSocial = 'Nome do tomador do serviço';
    $std->Tomador->IdentificacaoTomador = new stdClass();
    $std->Tomador->IdentificacaoTomador->CpfCnpj = new stdClass();
    $std->Tomador->IdentificacaoTomador->CpfCnpj->Cpf = '11111111199';
//    $std->Tomador->IdentificacaoTomador->CpfCnpj->Cnpj = '11111111011199';
//    $std->Tomador->IdentificacaoTomador->InscricaoMunicipal = '';

    $std->Tomador->Endereco = new stdClass();
    $std->Tomador->Endereco->Endereco = 'Rua Tomador';
    $std->Tomador->Endereco->Numero = '123';
//    $std->Tomador->Endereco->Complemento = 'Complemento se existir';
    $std->Tomador->Endereco->Bairro = 'Bairro';
    $std->Tomador->Endereco->CodigoMunicipio = '5101803'; // Foi informado que para a cidade Água Boa, em ambiente de HOMOLOGAÇÃO o municipio deveria ser este
    $std->Tomador->Endereco->Uf = 'MT';
    $std->Tomador->Endereco->CodigoPais = '1058';
    $std->Tomador->Endereco->Cep = '12345123';

    $std->Tomador->Contato = new stdClass();
    $std->Tomador->Contato->Telefone = '51000000000';
    $std->Tomador->Contato->Email = 'email@site.com.br';

    $std->RegimeEspecialTributacao = 6; //1 – Microempresa municipal, 2 – Estimativa, 3 – Sociedade de profissionais, 4 – Cooperativa, 5 – Microempresário Individual (MEI), 6 – Microempresário e Empresa de Pequeno Porte (ME EPP)
    $std->OptanteSimplesNacional = 2; //1 – Sim, 2 – Não
    $std->IncentivoFiscal = 2; //1 – Sim, 2 – Não

    $rps = new Rps($std);
//    $rps->setFormatOutput(true);
//    dd($rps->render());
    $response = $tools->gerarNfseEnvio($rps, '123');
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
    dd($response,'fim');

} catch (Exception $e) {
    echo $e->getMessage();
}