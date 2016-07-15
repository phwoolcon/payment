<?php
namespace Phwoolcon\Payment\Alipay;

use Phwoolcon\Payment\MethodInterface;
use Phwoolcon\Payment\MethodTrait;

class MobileWebPay implements MethodInterface
{
    use MethodTrait;

    public function callback($data)
    {
    }

    public function createCallbackSign($order, $callbackData)
    {
    }

    public function payRequest($data)
    {
    }
}
