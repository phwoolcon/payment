<?php
namespace Phwoolcon\Payment\Tests\Helper;

use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\MethodInterface;
use Phwoolcon\Payment\MethodTrait;
use Phwoolcon\Payment\Process\Result;

class TestPaymentMethod implements MethodInterface
{
    use MethodTrait;

    public function callback($data)
    {
    }

    public function payRequest($data)
    {
        $order = $this->prepareOrder($data);
        return Result::create([
            'order' => $order,
        ]);
    }
}
