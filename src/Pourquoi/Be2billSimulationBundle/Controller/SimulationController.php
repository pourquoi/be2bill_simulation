<?php

namespace Pourquoi\Be2billSimulationBundle\Controller;

use Pourquoi\PaymentBe2billBundle\Client\Parameters;
use Pourquoi\PaymentBe2billBundle\Client\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class SimulationController extends Controller
{
	public function formProcessAction(Request $request)
	{
		$ch = curl_init($this->container->getParameter('template_url'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($ch);

		$request_data = $request->request->all();

		$request->getSession()->set('payment_instructions', $request_data);

		$data = new Response($request_data);

		$expected_hash = Parameters::getSignature($this->container->getParameter('be2bill_password'), $request_data);

		if( $expected_hash != $data->getHash() ) {
			return new \Symfony\Component\HttpFoundation\Response(sprintf('invalid hash: received %s, expected %s', $expected_hash, $data->getHash()), 400);
		}

		$action = $this->generateUrl('payment_be2bill_simulation_form_process_payment');

		$form = <<<FORM
	<form method="POST" id="B2B-FORM" action="{$action}">
		<input type="hidden" name="IDENTIFIER" value="{$data->getIdentifier()}">
		<input type="hidden" name="ORDERID" value="{$data->getOrderId()}">
		<input type="hidden" name="HASH" value="{$data->getHash()}">

		<table id="b2b-table">
		<tbody>
		<tr id="b2b-ccnum">
			<th>Numéro de carte</th>
			<td nowrap="nowrap">
				<input type="text" name="CARDCODE" value="" size="20" maxlength="30" id="b2b-ccnum-input" autocomplete="off">
			</td>
		</tr>
		<tr class="invalid">
    		<td></td>
    		<td></td>
		</tr>
		<tr id="b2b-expiration-date">
		<th>Date d'expiration</th>
		<td>
			<select name="MONTHDATE" id="b2b-month-input"><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option></select>        <select name="YEARDATE" id="b2b-year-input"><option value="14">2014</option><option value="15">2015</option><option value="16">2016</option><option value="17">2017</option><option value="18">2018</option><option value="19">2019</option><option value="20">2020</option><option value="21">2021</option><option value="22">2022</option><option value="23">2023</option><option value="24">2024</option><option value="25">2025</option><option value="26">2026</option><option value="27">2027</option><option value="28">2028</option></select>    </td>
		</tr>
		<tr class="invalid">
    		<td></td>
    		<td>
			</td>
		</tr>
		<tr id="b2b-cvv">
			<th>Cryptogramme visuel</th>
			<td>
			<input type="text" name="CARDCVV" value="" size="3" maxlength="5" id="b2b-cvv-input" autocomplete="off">
			</td>
		</tr>
		<tr class="invalid">
			<td></td>
			<td>
			</td>
		</tr>
		<tr id="b2b-cvv-comment">
			<td colspan="2">
				<p>Les trois derniers chiffres au dos de votre carte</p>
			</td>
		</tr>
		<tr id="b2b-fullname">
			<th>Nom</th>
			<td>
				<input type="text" name="CARDFULLNAME" value="" size="40" maxlength="40" id="b2b-fullname-input" autocomplete="off">
			</td>
		</tr>
        <tr class="invalid">
            <td></td>
            <td></td>
        </tr>
			<tr id="b2b-email">
			<th>E-mail</th>
			<td>
				<input type="text" name="CLIENTEMAIL" value="" size="20" maxlength="40" id="b2b-email-input" autocomplete="off">
		</td>
		</tr>
		<tr class="invalid">
			<td></td>
			<td></td>
		</tr>
		<tr id="b2b-buttons">
			<td colspan="2">
			<input type="SUBMIT" id="b2b-submit" name="B2B-VALIDATE" value="Valider">
        	<div id="b2b-loading" style="visibility: hidden;">Traitement...</div>
			</td>
		</tr>
		</tbody>
		</table>
	</form>
	<script type="text/javascript">
		if (document.getElementById("B2B-FORM"))
		{
			document.getElementById("B2B-FORM").onsubmit = function () {
				document.getElementById('b2b-submit').style.display = 'none';
				document.getElementById('b2b-loading').style.visibility  = 'visible';
				// Override this function to prevent multiple submit
				this.onsubmit = function () { return false; };
				return true;
		};
		}

		if (document.getElementById('b2b-ccnum-input'))
		{
			document.getElementById("b2b-ccnum-input").onblur = function () {
				cardcode = this.value;
				this.value = cardcode.replace(/[^\d]/g, '');
			};
			document.getElementById("b2b-ccnum-input").onkeyup = function () {
				cardcode = this.value;
				this.value = cardcode.replace(/[^\d]/g, '');
				var longueur = this.value.length;
				if (longueur > 19) {
					this.value = this.value.substr(0,19);
				}
			};

		}
	</script>
FORM;

		$content = str_replace('%PLACEHOLDER%', $form, $content);

		return new \Symfony\Component\HttpFoundation\Response($content);
	}

	public function formProcessPaymentAction(Request $request)
	{
		$form_data = $request->getSession()->get('payment_instructions');

		$execcodes = array(
			'5555557376384001' => '4001',
			'5555554530114002' => '4002',
			'5555550226824003' => '4003',
			'5555558726544005' => '4005',
			'5555550082334006' => '4006',
			'5555550082334007' => '4007'
		);

		$aliases = array(
			'5555557376384008' => 'AB0001',
			'5555554530114009' => 'AB0002',
			'5555550226824010' => 'AB0003',
			'5555558726544011' => 'AB0004',
			'5555550082334012' => 'AB0005',
			'5555550082334013' => 'AB0006'
		);

		$r = $request->request->all();

		if( $form_data['ORDERID'] != $r['ORDERID'] ) {
			throw new \Exception('invalid session, try refreshing the page');
		}

		$data = array();
		$data['EXECCODE'] = isset($execcodes[$r['CARDCODE']]) ? $execcodes[$r['CARDCODE']] : '0000';
		$data['ORDERID'] = $r['ORDERID'];
		$data['OPERATIONTYPE'] = $form_data['OPERATIONTYPE'];
		$data['CARDCODE'] = 'XXXXXXXXXXXX1234';
		$data['CARDVALIDITYDATE'] = '05-18';
		$data['CARDCVV'] = '123';
		$data['AMOUNT'] = $form_data['AMOUNT'];
		$data['TRANSACTIONID'] = uniqid('tr_');

		if( isset($form_data['CREATEALIAS']) && strtolower($form_data['CREATEALIAS']) == 'yes' ) {
			$data['ALIAS'] = isset($aliases[$r['CARDCODE']]) ? $aliases[$r['CARDCODE']] : 'AB0000';
		}

		$ch = curl_init($this->container->getParameter('notification_url'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$res = curl_exec($ch);

		if( 'OK' != $res ) {
			return new \Symfony\Component\HttpFoundation\Response($res);
		};

		return new RedirectResponse($this->container->getParameter('return_url'));
	}

	public function restProcessAction(Request $request)
	{
		$request_data = $request->request->get('params');

		$expected_hash = Parameters::getSignature($this->container->getParameter('be2bill_password'), $request_data);

		if( $expected_hash != $request_data['HASH'] ) {
			return new \Symfony\Component\HttpFoundation\Response(sprintf('invalid hash: received %s, expected %s.', $request_data['HASH'], $expected_hash), 400);
		}

		$execcodes = array(
			'AB0001' => '4001',
			'AB0002' => '4002',
			'AB0003' => '4003',
			'AB0004' => '4005',
			'AB0005' => '4006',
			'AB0006' => '4007'
		);

		$r = array();
		$r['EXECCODE'] = (isset($request_data['ALIAS']) && isset($execcodes[$request_data['ALIAS']])) ? $execcodes[$request_data['ALIAS']] : '0000';
		$r['OPERATIONTYPE'] = $request_data['OPERATIONTYPE'];
		$r['MESSAGE'] = $r['EXECCODE'] == '0000' ? 'The transaction has been accepted' : ('error ' . $r['EXECCODE']);
		$r['TRANSACTIONID'] = uniqid('tr_');

		$data = array();
		$data['EXECCODE'] = $r['EXECCODE'];
		$data['ORDERID'] = $request_data['ORDERID'];
		$data['OPERATIONTYPE'] = $request_data['OPERATIONTYPE'];
		if( isset($request_data['AMOUNT']) )
			$data['AMOUNT'] = $request_data['AMOUNT'];
		$data['TRANSACTIONID'] = $r['TRANSACTIONID'];

		$ch = curl_init($this->container->getParameter('notification_url'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$res = curl_exec($ch);

		if( 'OK' != $res ) {
			if( $logger = $this->get('logger') )
				$logger->error($res);
		};

		return new JsonResponse($r);
	}
}