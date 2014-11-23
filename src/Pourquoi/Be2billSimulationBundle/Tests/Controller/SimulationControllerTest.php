<?php

namespace Pourquoi\Be2billSimulationBundle\Tests\Controller;

use Pourquoi\PaymentBe2billBundle\Client\Parameters;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SimulationControllerTest extends WebTestCase
{
    public function formProvider() {
        $data = [];

        $params = [];
        $data[] = array($params, 400);

        $params = array('IDENTIFIER' => 'id_test');
        $params['HASH'] = Parameters::getSignature('pass_test', $params);
        $params = Parameters::sortParameters($params);
        $data[] = array($params, 200);

        return $data;
    }

    /**
     * @dataProvider formProvider
     */
    public function testFormProcess($data, $expected_code)
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/form/process', $data);

        $this->assertEquals($expected_code, $client->getResponse()->getStatusCode());
    }

    public function testFormProcessPayment()
    {
        $client = static::createClient();

        $data = array('IDENTIFIER' => 'id_test');
        $data['ORDERID'] = 'order1';
        $data['OPERATIONTYPE'] = 'payment';
        $data['AMOUNT'] = '1000';
        $data['HASH'] = Parameters::getSignature('pass_test', $data);
        $data = Parameters::sortParameters($data);

        $expire = new \DateTime('+1 year');

        $crawler = $client->request('POST', '/form/process', $data);

        $form = $crawler->filter('#b2b-submit')->form();
        $form['CARDCODE'] = '5555 5567 7825 0000';
        $form['MONTHDATE'] = $expire->format('m');
        $form['YEARDATE'] = $expire->format('y');
        $form['CARDCVV'] = '123';

        $crawler = $client->submit($form);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
