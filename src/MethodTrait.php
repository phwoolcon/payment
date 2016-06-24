<?php
namespace Phwoolcon\Payment;

trait MethodTrait
{
    protected $config;

    public function callback($params)
    {
    }

    public function getConfig($key = null, $default = null)
    {
        return $key === null ? $this->config : fnGet($this->config, $key, $default);
    }

    public function payRequest($params)
    {
    }

    public function process($data)
    {
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
