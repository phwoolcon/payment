<?php
namespace Phwoolcon\Payment;

use Phalcon\Di;
use Phwoolcon\Config;

class Processor
{
    protected $config;
    /**
     * @var Di
     */
    protected static $di;
    /**
     * @var static
     */
    protected static $instance;

    public static function register(Di $di)
    {
        static::$di = $di;
        $di->remove('payment');
        static::$instance = null;
        $di->setShared('payment', function () {
            return new static(Config::get('payment'));
        });
    }

    public function run($payload)
    {
        $data = $payload['data'];
        $paymentMethod = fnGet($data, 'payment_method');
        $config = fnGet($this->config, 'methods.' . $paymentMethod);
        $class = fnGet($config, 'class');
        if ($class && class_exists($class)) {
            $processor = new $class;
            if ($processor instanceof MethodInterface) {
                $processor->setConfig($config);
                $payload['result'] = $processor->process($data);
            }
        }
        return $payload;
    }
}
