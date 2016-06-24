<?php
namespace Phwoolcon\Payment;

interface MethodInterface
{

    public function callback($params);

    public function getConfig($key, $default = null);

    public function payRequest($params);

    public function process($data);

    public function setConfig($key, $value = null);
}
