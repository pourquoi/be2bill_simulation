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
	    $content = null;
	    if( $template_url = $this->container->getParameter('be2bill.template_url') ) {
            $ch = curl_init($template_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
        }

        if( !$content ) {
            $content = $this->renderView('PourquoiBe2billSimulationBundle:Simulation:default_template.html.twig');
        }

		$request_data = $request->request->all();

		$request->getSession()->set('payment_instructions', $request_data);

		$data = new Response($request_data);

		$expected_hash = Parameters::getSignature($this->container->getParameter('be2bill.password'), $request_data);

		if( $expected_hash != $data->getHash() ) {
			return new \Symfony\Component\HttpFoundation\Response(sprintf('invalid hash: received %s, expected %s', $data->getHash(), $expected_hash), 400);
		}

		$action = $this->generateUrl('payment_be2bill_simulation_form_process_payment');

		$form = $this->renderView('PourquoiBe2billSimulationBundle:Simulation:form.html.twig', array(
		    'action' => $action,
		    'identifier' => $data->getIdentifier(),
		    'orderid' => $data->getOrderId(),
		    'hash' => $data->getHash()
		));

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

        if( $notification_url = $this->container->getParameter('be2bill.notification_url') ) {
            $ch = curl_init($notification_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $res = curl_exec($ch);

            if ('OK' != $res) {
                $this->get('logger')->error(spintrf('notification call failed: %s', $res));
            };
        }

        if( !$return_url = $this->container->getParameter('be2bill.return_url') ) {
            $return_url = $this->generateUrl('payment_be2bill_simulation_payment_feedback');
        }
		return new RedirectResponse($return_url);
	}

	public function paymentFeedbackAction()
    {
        return new \Symfony\Component\HttpFoundation\Response('');
    }

	public function restProcessAction(Request $request)
	{
		$request_data = $request->request->get('params');

		$expected_hash = Parameters::getSignature($this->container->getParameter('be2bill.password'), $request_data);

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

		$ch = curl_init($this->container->getParameter('be2bill.notification_url'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$res = curl_exec($ch);

		if( 'OK' != $res ) {
            $this->get('logger')->error($res);
		};

		return new JsonResponse($r);
	}
}