<?php
namespace Phwoolcon\Payment;

use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Process\Payload;

trait MethodTrait
{
    protected $config;
    protected $gateway;
    protected $method;

    public function __construct(array $config = null)
    {
        $config and $this->config = $config;
    }

    public function getConfig($key = null, $default = null)
    {
        return $key === null ? $this->config : fnGet($this->config, $key, $default);
    }

    protected function prepareOrder(array $data)
    {
        isset($data['order_prefix']) or $data['order_prefix'] = fnGet($this->config, 'order_prefix');
        $data['payment_gateway'] = $this->gateway;
        $data['payment_method'] = $this->method;
        $order = Order::prepare($data);
        $order->save();
        return $order;
    }

    /**
     * @param Payload $payload
     * @return mixed
     */
    public function process($payload)
    {
        $action = $payload->getData('action', 'payRequest');
        $this->gateway = $payload->getGateway();
        $this->method = $payload->getMethod();
        return $this->{$action}($payload->getData('data'));
    }

    public function setConfig($key, $value = null)
    {
        if (is_array($key)) {
            $this->config = $key;
        } else {
            array_set($this->config, $key, $value);
        }
        return $this;
    }
}
