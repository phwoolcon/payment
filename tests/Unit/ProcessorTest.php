<?php
namespace Phwoolcon\Payment\Tests\Unit;

use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Exception\GeneralException;
use Phwoolcon\Payment\Process\Payload;
use Phwoolcon\Payment\Process\Result;
use Phwoolcon\Payment\Processor;
use Phwoolcon\Tests\Helper\TestCase;
use Phwoolcon\Payment\Tests\Helper\TestOrderDataModel;
use Phwoolcon\Payment\Tests\Helper\TestOrderModel;

class ProcessorTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        Processor::register($this->di);
        new TestOrderModel;
        new TestOrderDataModel;
    }

    public function testRunPayRequest()
    {
        $payload = Processor::run(Payload::create([
            'gateway' => 'test_gateway',
            'method' => 'test_pay',
            'action' => 'payRequest',
            'data' => [
                'trade_id' => 'TEST-PAY-ORDER',
                'product_name' => 'Test product',
                'client_id' => 'test_client',
                'user_identifier' => 'Test User',
                'amount' => $amount = 1,
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertInstanceOf(Result::class, $result = $payload->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEmpty($order->getStringMessages());
        $this->assertEquals($order::STATUS_PENDING, $order->getStatus());
        $this->assertEquals($amount, $order->getAmount());
        $this->assertEquals($amount, $order->getCashToPay());
    }

    public function testRunUndefinedGateway()
    {
        $e = null;
        try {
            Processor::run(Payload::create([
                'gateway' => 'undefined_gateway',
                'method' => 'undefined_method',
                'action' => 'undefined_action',
                'data' => [],
            ]));
        } catch (GeneralException $e) {
        }
        $this->assertInstanceOf(GeneralException::class, $e);
        $this->assertEquals(GeneralException::UNDEFINED_PAYMENT_GATEWAY, $e->getCode());
    }

    public function testRunUndefinedPaymentMethod()
    {
        $e = null;
        try {
            Processor::run(Payload::create([
                'gateway' => 'test_gateway',
                'method' => 'undefined_method',
                'action' => 'undefined_action',
                'data' => [],
            ]));
        } catch (GeneralException $e) {
        }
        $this->assertInstanceOf(GeneralException::class, $e);
        $this->assertEquals(GeneralException::UNDEFINED_PAYMENT_METHOD, $e->getCode());
    }

    public function testRunInvalidPaymentMethod()
    {
        $e = null;
        try {
            Processor::run(Payload::create([
                'gateway' => 'test_gateway',
                'method' => 'invalid_method',
                'action' => 'undefined_action',
                'data' => [],
            ]));
        } catch (GeneralException $e) {
        }
        $this->assertInstanceOf(GeneralException::class, $e);
        $this->assertEquals(GeneralException::INVALID_PAYMENT_METHOD, $e->getCode());
    }
}
