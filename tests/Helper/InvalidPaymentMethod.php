<?php
namespace Phwoolcon\Payment\Tests\Helper;

use Phwoolcon\Payment\MethodInterface;
use Phwoolcon\Payment\MethodTrait;

class TestPaymentMethod implements MethodInterface
{
    use MethodTrait;
}
