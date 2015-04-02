<?php
// +----------------------------------------------------------------------+
// | BoletoPhp - Versão Beta                                              |
// +----------------------------------------------------------------------+
// | Este arquivo está disponível sob a Licença GPL disponível pela Web   |
// | em http://pt.wikipedia.org/wiki/GNU_General_Public_License           |
// | Você deve ter recebido uma cópia da GNU Public License junto com     |
// | esse pacote; se não, escreva para:                                   |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+

// +----------------------------------------------------------------------+
// | Originado do Projeto BBBoletoFree que tiveram colaborações de Daniel |
// | William Schultz e Leandro Maniezo que por sua vez foi derivado do    |
// | PHPBoleto de João Prado Maia e Pablo Martins F. Costa                |
// |                                                                      |
// | Se vc quer colaborar, nos ajude a desenvolver p/ os demais bancos :-)|
// | Acesse o site do Projeto BoletoPhp: www.boletophp.com.br             |
// +----------------------------------------------------------------------+

// +----------------------------------------------------------------------+
// | Equipe Coordenação Projeto BoletoPhp: <boletophp@boletophp.com.br>   |
// | Desenvolvimento Boleto NossaCaixa: Keitty Suélen                     |
// +----------------------------------------------------------------------+

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
$codigobanco = "151"; //Código do banco de acordo com o banco central
$codigo_banco_com_dv = geraCodigoBanco( $codigobanco );
$nummoeda = "9";
$fator_vencimento = fator_vencimento( $dadosboleto["data_vencimento"] );

//valor tem 10 digitos, sem virgula
$valor = formata_numero( $dadosboleto["valor_boleto"], 10, 0, "valor" );
//agencia é 4 digitos
$agencia = formata_numero( $dadosboleto["agencia"], 4, 0 );
//conta cedente (sem dv) é 6 digitos
$conta_cedente = formata_numero( $dadosboleto["conta_cedente"], 6, 0 );
//dv da conta cedente
$conta_cedente_dv = formata_numero( $dadosboleto["conta_cedente_dv"], 1, 0 );
//carteira
$carteira = $dadosboleto["carteira"];
//modalidade da conta
$modalidade = formata_numero( $dadosboleto["modalidade_conta"], 2, 0 );

// DONE: Bugfix 2007-03-25 Francisco Ernesto Teixeira <fco_ernesto@yahoo.com.br>
// Notice: Undefined variable: modalidade_c1 in funcoes_nossacaixa.php on line 48

$modalidade_c1 = isset( $modalidade_c1 ) ? $modalidade_c1 : 0;

//Converte a modalidade de acordo com a tabela do banco
$modalidade == "01" ? $modalidade_c1 = 1 : $modalidade_c1;
$modalidade == "04" ? $modalidade_c1 = 4 : $modalidade_c1;
$modalidade == "09" ? $modalidade_c1 = 9 : $modalidade_c1;
$modalidade == "13" ? $modalidade_c1 = 3 : $modalidade_c1;
$modalidade == "16" ? $modalidade_c1 = 6 : $modalidade_c1;
$modalidade == "17" ? $modalidade_c1 = 7 : $modalidade_c1;
$modalidade == "18" ? $modalidade_c1 = 8 : $modalidade_c1;


//nosso número (sem dv) é 9 digitos
$dadosboleto["inicio_nosso_numero"] = ( 5 == $carteira ) ? 99 : 0;
$nnum = $dadosboleto["inicio_nosso_numero"] . formata_numero( $dadosboleto["nosso_numero"], 7, 0 );

//Agencia sem o digito + modalidade convertida e conta sem o dígito
$ag_contacedente = $agencia .$modalidade_c1. $conta_cedente;

//
$prefixo = ( 5 == $carteira ) ? 9 : 0;

//Calcula o digito verificador do nosso número
$dv_nosso_numero = digitoVerificador_nossonumero( $nnum, $conta_cedente, $conta_cedente_dv, $agencia, $modalidade );
$nossonumero_dv  = "$nnum$dv_nosso_numero";

//pega o nosso numero a partir da 2º posição
$nnum = substr( $nnum, 1 );

//numero para o calculo dos dígitos verificadores da posição 43 e 44
$calcdv1 = $prefixo.$nnum.$ag_contacedente.$codigobanco;

//Gera os dígitos verificadores da posição 43 e 44
$dv1 = geraDv43( $calcdv1 );
$dv2 = geraDv44( "$calcdv1$dv1" );

//Se vier 2 caracteres significa que o dv2 deu 1 então o dv1 e o dv2 foi recalculado e retornado nesta string(Coisa do manual do banco =D !)
if ( strlen( $dv2 ) == 2 ) {
    $dv1= substr( $dv2, 0, 1 );
    $dv2= substr( $dv2, 1, 1 );
}

// DONE: Bugfix 2007-03-25 Francisco Ernesto Teixeira <fco_ernesto@yahoo.com.br>
// Notice: Undefined variable: dv in funcoes_nossacaixa.php on line 93
$dv = isset( $dv ) ? $dv : 0;

// 43 numeros para o calculo do digito verificador do codigo de barras
$dv = digitoVerificador_barra( "$codigobanco$nummoeda$fator_vencimento$valor$prefixo$nnum$ag_contacedente$codigobanco$dv1$dv2" );

// Numero para o codigo de barras com 44 digitos
$linha = "$codigobanco$nummoeda$dv$fator_vencimento$valor$prefixo$nnum$ag_contacedente$codigobanco$dv1$dv2";

$nossonumero = substr( $nossonumero_dv, 0, 9 ).'-'.substr( $nossonumero_dv, 9, 1 );
$agencia_codigo = $agencia." / ".$modalidade." ".$conta_cedente ." ". $conta_cedente_dv;

$dadosboleto["codigo_barras"]       = $linha;
$dadosboleto["linha_digitavel"]     = monta_linha_digitavel( $linha );
$dadosboleto["agencia_codigo"]      = $agencia_codigo;
$dadosboleto["nosso_numero"]        = $nossonumero;
$dadosboleto["codigo_banco_com_dv"] = $codigo_banco_com_dv;
?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN'>
<html>
	<head>
		<title>
			<?php echo $dadosboleto["identificacao"]; ?>
		</title>
		<meta http-equiv="Content-Type" content="text/html" charset="ISO-8859-1">
		<meta name="Generator" content="Projeto BoletoPHP - www.boletophp.com.br - Licença GPL">
		<style type="text/css">
		<!--.cp {  font: bold 10px Arial; color: black}
		<!--.ti {  font: 9px Arial, Helvetica, sans-serif}
		<!--.ld { font: bold 15px Arial; color: #000000}
		<!--.ct { font: 9px "Arial Narrow"; color: #000033}
		<!--.cn { font: 9px Arial; color: black }
		<!--.bc { font: bold 20px Arial; color: #000000 }
		<!--.ld2 { font: bold 12px Arial; color: #000000 }
		-->
		</style>
	</head>
	<body text="#000000" bgcolor="#FFFFFF" topmargin="0" rightmargin="0">
		<table width="666" cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td valign="top" class="cp">
					<div align="center">
						<?php _e( 'Instru&ccedil;&otilde;es de Impress&atilde;o', 'woocommerce-boleto' ); ?>
					</div>
				</td>
			</tr>
			<tr>
				<td valign="top" class="cp">
					<div align="left">
						<ul>
							<li><?php _e( 'Imprima em impressora jato de tinta (ink jet) ou laser em qualidade normal ou alta (N&atilde;o use modo econ&ocirc;mico).', 'woocommerce-boleto' ); ?><br>
							</li>
							<li><?php _e( 'Utilize folha A4 (210 x 297 mm) ou Carta (216 x 279 mm) e margens m&iacute;nimas &agrave; esquerda e &agrave; direita do formul&aacute;rio.', 'woocommerce-boleto' ); ?><br>
							</li>
							<li><?php _e( 'Corte na linha indicada. N&atilde;o rasure, risque, fure ou dobre a regi&atilde;o onde se encontra o c&oacute;digo de barras.', 'woocommerce-boleto' ); ?><br>
							</li>
							<li><?php _e( 'Caso n&atilde;o apare&ccedil;a o c&oacute;digo de barras no final, clique em F5 para atualizar esta tela.', 'woocommerce-boleto' ); ?>
							</li>
							<li><?php _e( 'Caso tenha problemas ao imprimir, copie a seq&uuml;encia num&eacute;rica abaixo e pague no caixa eletr&ocirc;nico ou no internet banking:', 'woocommerce-boleto' ); ?><br>
								<br>
								<span class="ld2">&nbsp;&nbsp;&nbsp;&nbsp;<?php _e( 'Linha Digit&aacute;vel:', 'woocommerce-boleto' ); ?> &nbsp;<?php echo $dadosboleto["linha_digitavel"]?><br>
								&nbsp;&nbsp;&nbsp;&nbsp;<?php _e( 'Valor:', 'woocommerce-boleto' ); ?>&nbsp;&nbsp;R$ <?php echo $dadosboleto["valor_boleto"]?><br></span>
							</li>
						</ul>
					</div>
				</td>
			</tr>
		</table><br>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tbody>
				<tr>
					<td class="ct" width="666">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/6.png" width="665" border="0">
					</td>
				</tr>
				<tr>
					<td class="ct" width="666">
						<div align="right">
							<b class="cp"><?php _e( 'Recibo do Sacado', 'woocommerce-boleto' ); ?></b>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<table width="666" cellspacing="5" cellpadding="0" border="0">
			<tr>
				<td width="41"></td>
			</tr>
		</table>
		<table width="666" cellspacing="5" cellpadding="0" border="0" align="default">
			<tr>
				<td width="41">
					<img src="<?php echo $logo; ?>" alt="<?php echo $shop_name; ?>">
				</td>
				<td class="ti" width="455">
					<?php echo $dadosboleto["identificacao"]; ?><?php echo isset($dadosboleto["cpf_cnpj"]) ? "<br>".$dadosboleto["cpf_cnpj"] : '' ?><br>
					<?php echo $dadosboleto["endereco"]; ?><br>
					<?php echo $dadosboleto["cidade_uf"]; ?><br>
				</td>
				<td align="right" width="150" class="ti">
					&nbsp;
				</td>
			</tr>
		</table><br>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tr>
				<td class="cp" width="150">
					<span class="campo"><img src="<?php echo wcboleto_parcelado_assets_url(); ?>images/logonossacaixa.jpg" width="150" height="40" border="0"></span>
				</td>
				<td width="3" valign="bottom">
					<img height="22" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/3.png" width="2" border="0">
				</td>
				<td class="cpt" width="58" valign="bottom">
					<div align="center">
						<font class="bc"><?php echo $dadosboleto["codigo_banco_com_dv"]?></font>
					</div>
				</td>
				<td width="3" valign="bottom">
					<img height="22" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/3.png" width="2" border="0">
				</td>
				<td class="ld" align="right" width="453" valign="bottom">
					<span class="ld"><span class="campotitulo"><?php echo $dadosboleto["linha_digitavel"]?></span></span>
				</td>
			</tr>
			<tbody>
				<tr>
					<td colspan="5">
						<img height="2" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="666" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="298" height="13">
						<?php _e( 'Cedente', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="126" height="13">
						<?php _e( 'Ag&ecirc;ncia/C&oacute;digo do Cedente', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="34" height="13">
						<?php _e( 'Esp&eacute;cie', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="53" height="13">
						<?php _e( 'Quantidade', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="120" height="13">
						<?php _e( 'Nosso n&uacute;mero', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="298" height="12">
						<span class="campo"><?php echo $dadosboleto["cedente"]; ?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="126" height="12">
						<span class="campo"><?php echo $dadosboleto["agencia_codigo"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="34" height="12">
						<span class="campo"><?php echo $dadosboleto["especie"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="53" height="12">
						<span class="campo"><?php echo $dadosboleto["quantidade"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="120" height="12">
						<span class="campo"><?php echo $dadosboleto["nosso_numero"]?></span>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="298" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="298" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="126" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="126" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="34" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="34" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="53" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="53" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="120" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="120" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" colspan="3" height="13">
						<?php _e( 'N&uacute;mero do documento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="132" height="13">
						<?php _e( 'CPF/CNPJ', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="134" height="13">
						<?php _e( 'Vencimento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="180" height="13">
						<?php _e( 'Valor documento', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" colspan="3" height="12">
						<span class="campo"><?php echo $dadosboleto["numero_documento"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="132" height="12">
						<span class="campo"><?php echo $dadosboleto["cpf_cnpj"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="134" height="12">
						<span class="campo"><?php echo $dadosboleto["data_vencimento"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="180" height="12">
						<span class="campo"><?php echo $dadosboleto["valor_boleto"]?></span>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="113" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="113" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="72" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="72" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="132" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="132" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="134" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="134" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="180" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="113" height="13">
						<?php _e( '(-) Desconto / Abatimentos', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="112" height="13">
						<?php _e( '(-) Outras dedu&ccedil;&otilde;es', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="113" height="13">
						<?php _e( '(+) Mora / Multa', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="113" height="13">
						<?php _e( '(+) Outros acr&eacute;scimos', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="180" height="13">
						<?php _e( '(=) Valor cobrado', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="113" height="12"></td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="112" height="12"></td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="113" height="12"></td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="113" height="12"></td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="180" height="12"></td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="113" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="113" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="112" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="112" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="113" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="113" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="113" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="113" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="180" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="659" height="13">
						<?php _e( 'Sacado', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="659" height="12">
						<span class="campo"><?php echo $dadosboleto["sacado"]?></span>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="659" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="659" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" width="7" height="12"></td>
					<td class="ct" width="564">
						<?php _e( 'Demonstrativo', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" width="7" height="12"></td>
					<td class="ct" width="88">
						<?php _e( 'Autentica&ccedil;&atilde;o mec&acirc;nica', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td width="7"></td>
					<td class="cp" width="564">
						<span class="campo"><?php echo $dadosboleto["demonstrativo1"]?><br>
						<?php echo $dadosboleto["demonstrativo2"]?><br>
						<?php echo $dadosboleto["demonstrativo3"]?><br></span>
					</td>
					<td width="7"></td>
					<td width="88"></td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tbody>
				<tr>
					<td width="7"></td>
					<td width="500" class="cp">
						<br>
						<br>
						<br>
					</td>
					<td width="159"></td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tr>
				<td class="ct" width="666"></td>
			</tr>
			<tbody>
				<tr>
					<td class="ct" width="666">
						<div align="right">
							<?php _e( 'Corte na linha pontilhada', 'woocommerce-boleto' ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<td class="ct" width="666">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/6.png" width="665" border="0">
					</td>
				</tr>
			</tbody>
		</table><br>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tr>
				<td class="cp" width="150">
					<span class="campo"><img src="<?php echo wcboleto_parcelado_assets_url(); ?>images/logonossacaixa.jpg" width="150" height="40" border="0"></span>
				</td>
				<td width="3" valign="bottom">
					<img height="22" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/3.png" width="2" border="0">
				</td>
				<td class="cpt" width="58" valign="bottom">
					<div align="center">
						<font class="bc"><?php echo $dadosboleto["codigo_banco_com_dv"]?></font>
					</div>
				</td>
				<td width="3" valign="bottom">
					<img height="22" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/3.png" width="2" border="0">
				</td>
				<td class="ld" align="right" width="453" valign="bottom">
					<span class="ld"><span class="campotitulo"><?php echo $dadosboleto["linha_digitavel"]?></span></span>
				</td>
			</tr>
			<tbody>
				<tr>
					<td colspan="5">
						<img height="2" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="666" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="472" height="13">
						<?php _e( 'Local de pagamento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="180" height="13">
						<?php _e( 'Vencimento', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="472" height="12">
						<?php _e( 'Pag&aacute;vel em qualquer Banco at&eacute; o vencimento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="180" height="12">
						<span class="campo"><?php echo $dadosboleto["data_vencimento"]?></span>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="472" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="472" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="180" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="472" height="13">
						<?php _e( 'Cedente', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="180" height="13">
						<?php _e( 'Ag&ecirc;ncia/C&oacute;digo cedente', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="472" height="12">
						<span class="campo"><?php echo $dadosboleto["cedente"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="180" height="12">
						<span class="campo"><?php echo $dadosboleto["agencia_codigo"]?></span>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="472" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="472" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="180" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="113" height="13">
						<?php _e( 'Data do documento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="153" height="13">
						<?php _e( 'N<u>o</u> documento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="62" height="13">
						<?php _e( 'Esp&eacute;cie doc.', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="34" height="13">
						<?php _e( 'Aceite', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="82" height="13">
						<?php _e( 'Data processamento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="180" height="13">
						<?php _e( 'Nosso n&uacute;mero', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="113" height="12">
						<div align="left">
							<span class="campo"><?php echo $dadosboleto["data_documento"]?></span>
						</div>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="153" height="12">
						<span class="campo"><?php echo $dadosboleto["numero_documento"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="62" height="12">
						<div align="left">
							<span class="campo"><?php echo $dadosboleto["especie_doc"]?></span>
						</div>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="34" height="12">
						<div align="left">
							<span class="campo"><?php echo $dadosboleto["aceite"]?></span>
						</div>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="82" height="12">
						<div align="left">
							<span class="campo"><?php echo $dadosboleto["data_processamento"]?></span>
						</div>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="180" height="12">
						<span class="campo"><?php echo $dadosboleto["nosso_numero"]?></span>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="113" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="113" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="153" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="153" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="62" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="62" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="34" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="34" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="82" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="82" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="180" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" colspan="3" height="13">
						<?php _e( 'Uso do banco', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" height="13" width="7">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="83" height="13">
						<?php _e( 'Carteira', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" height="13" width="7">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="53" height="13">
						<?php _e( 'Esp&eacute;cie', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" height="13" width="7">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="123" height="13">
						<?php _e( 'Quantidade', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" height="13" width="7">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="72" height="13">
						<?php _e( 'Valor Documento', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="180" height="13">
						<?php _e( '(=) Valor documento', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td valign="top" class="cp" height="12" colspan="3">
						<div align="left"></div>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="83">
						<div align="left">
							<span class="campo"><?php echo $dadosboleto["carteira"]?></span>
						</div>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="53">
						<div align="left">
							<span class="campo"><?php echo $dadosboleto["especie"]?></span>
						</div>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="123">
						<span class="campo"><?php echo $dadosboleto["quantidade"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="72">
						<span class="campo"><?php echo $dadosboleto["valor_unitario"]?></span>
					</td>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" align="right" width="180" height="12">
						<span class="campo"><?php echo $dadosboleto["valor_boleto"]?></span>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="75" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="31" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="31" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="83" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="83" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="53" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="53" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="123" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="123" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="72" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="72" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="180" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tbody>
				<tr>
					<td align="right" width="10">
						<table cellspacing="0" cellpadding="0" border="0" align="left">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="1" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
					<td valign="top" width="468" rowspan="5">
						<font class="ct"><?php _e( 'Instru&ccedil;&otilde;es (Texto de responsabilidade do cedente)', 'woocommerce-boleto' ); ?></font><br>
						<br>
						<span class="cp"><font class="campo"><?php echo $dadosboleto["instrucoes1"]; ?><br>
						<?php echo $dadosboleto["instrucoes2"]; ?><br>
						<?php echo $dadosboleto["instrucoes3"]; ?><br>
						<?php echo $dadosboleto["instrucoes4"]; ?></font><br>
						<br></span>
					</td>
					<td align="right" width="188">
						<table cellspacing="0" cellpadding="0" border="0">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="ct" valign="top" width="180" height="13">
										<?php _e( '(-) Desconto / Abatimentos', 'woocommerce-boleto' ); ?>
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="cp" valign="top" align="right" width="180" height="12"></td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
									</td>
									<td valign="top" width="180" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td align="right" width="10">
						<table cellspacing="0" cellpadding="0" border="0" align="left">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="1" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
					<td align="right" width="188">
						<table cellspacing="0" cellpadding="0" border="0">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="ct" valign="top" width="180" height="13">
										<?php _e( '(-) Outras dedu&ccedil;&otilde;es', 'woocommerce-boleto' ); ?>
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="cp" valign="top" align="right" width="180" height="12"></td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
									</td>
									<td valign="top" width="180" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td align="right" width="10">
						<table cellspacing="0" cellpadding="0" border="0" align="left">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="1" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
					<td align="right" width="188">
						<table cellspacing="0" cellpadding="0" border="0">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="ct" valign="top" width="180" height="13">
										<?php _e( '(+) Mora / Multa', 'woocommerce-boleto' ); ?>
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="cp" valign="top" align="right" width="180" height="12"></td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
									</td>
									<td valign="top" width="180" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td align="right" width="10">
						<table cellspacing="0" cellpadding="0" border="0" align="left">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="1" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
					<td align="right" width="188">
						<table cellspacing="0" cellpadding="0" border="0">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="ct" valign="top" width="180" height="13">
										<?php _e( '(+) Outros acr&eacute;scimos', 'woocommerce-boleto' ); ?>
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="cp" valign="top" align="right" width="180" height="12"></td>
								</tr>
								<tr>
									<td valign="top" width="7" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
									</td>
									<td valign="top" width="180" height="1">
										<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td align="right" width="10">
						<table cellspacing="0" cellpadding="0" border="0" align="left">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
								</tr>
							</tbody>
						</table>
					</td>
					<td align="right" width="188">
						<table cellspacing="0" cellpadding="0" border="0">
							<tbody>
								<tr>
									<td class="ct" valign="top" width="7" height="13">
										<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="ct" valign="top" width="180" height="13">
										<?php _e( '(=) Valor cobrado', 'woocommerce-boleto' ); ?>
									</td>
								</tr>
								<tr>
									<td class="cp" valign="top" width="7" height="12">
										<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
									</td>
									<td class="cp" valign="top" align="right" width="180" height="12"></td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tbody>
				<tr>
					<td valign="top" width="666" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="666" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="659" height="13">
						<?php _e( 'Sacado', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="659" height="12">
						<span class="campo"><?php echo $dadosboleto["sacado"]?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="cp" valign="top" width="7" height="12">
						<img height="12" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="659" height="12">
						<span class="campo"><?php echo $dadosboleto["endereco1"]?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0">
			<tbody>
				<tr>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="cp" valign="top" width="472" height="13">
						<span class="campo"><?php echo $dadosboleto["endereco2"]?></span>
					</td>
					<td class="ct" valign="top" width="7" height="13">
						<img height="13" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/1.png" width="1" border="0">
					</td>
					<td class="ct" valign="top" width="180" height="13">
						<?php _e( 'C&oacute;d. baixa', 'woocommerce-boleto' ); ?>
					</td>
				</tr>
				<tr>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="472" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="472" border="0">
					</td>
					<td valign="top" width="7" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="7" border="0">
					</td>
					<td valign="top" width="180" height="1">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/2.png" width="180" border="0">
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" border="0" width="666">
			<tbody>
				<tr>
					<td class="ct" width="7" height="12"></td>
					<td class="ct" width="409">
						<?php _e( 'Sacador/Avalista', 'woocommerce-boleto' ); ?>
					</td>
					<td class="ct" width="250">
						<div align="right">
							<?php _e( 'Autentica&ccedil;&atilde;o mec&acirc;nica', 'woocommerce-boleto' ); ?> - <b class="cp"><?php _e( 'Ficha de Compensa&ccedil;&atilde;o', 'woocommerce-boleto' ); ?></b>
						</div>
					</td>
				</tr>
				<tr>
					<td class="ct" colspan="3"></td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tbody>
				<tr>
					<td valign="bottom" align="left" height="50">
						<?php fbarcode($dadosboleto["codigo_barras"]); ?>
					</td>
				</tr>
			</tbody>
		</table>
		<table cellspacing="0" cellpadding="0" width="666" border="0">
			<tr>
				<td class="ct" width="666"></td>
			</tr>
			<tbody>
				<tr>
					<td class="ct" width="666">
						<div align="right">
							<?php _e( 'Corte na linha pontilhada', 'woocommerce-boleto' ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<td class="ct" width="666">
						<img height="1" src="<?php echo wcboleto_parcelado_assets_url(); ?>images/6.png" width="665" border="0">
					</td>
				</tr>
			</tbody>
		</table>
	</body>
</html>
