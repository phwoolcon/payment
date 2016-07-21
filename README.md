# payment
Payment module for Phwoolcon

## Installation
Add this library to your project by composer:

```
composer require "phwoolcon/payment":"dev-master"
```

## Usage

### Configuration
Please create a new config file `app/config/production/payment.php` to  
override the default settings with real Alipay profile:
```php
<?php
return [
    'gateways' => [
        'alipay' => [
            'partner' => 'YOUR_PARTNER_ID_APPLIED_FROM_ALIPAY',
            'seller_id' => 'YOUR_SELLER_EMAIL_APPLIED_FROM_ALIPAY',
            'private_key' => '-----BEGIN RSA PRIVATE KEY-----
YOUR_PRIVATE_KEY_PROVIDED_TO_ALIPAY
-----END RSA PRIVATE KEY-----',
            'ali_public_key' => '-----BEGIN PUBLIC KEY-----
THE_PUBLIC_KEY_APPLIED_FROM_ALIPAY
-----END PUBLIC KEY-----',
        ],
    ],
];

```

### Start Alipay Pay Request
```php
<?php
use Phalcon\Di;
use Phwoolcon\Payment\Processor;

$di = Di::getDefault();
Processor::register($di);
$payload = Processor::run(Payload::create([
    'gateway' => 'alipay',
    'method' => 'mobile_web',
    'action' => 'payRequest',
    'data' => [
        'trade_id' => $tradeId,
        'product_name' => 'Test product',
        'client_id' => 'test_client',
        'user_identifier' => 'Test User',
        'amount' => 1,
    ],
]));
echo get_class($payload);       // prints Phwoolcon\Payment\Process\Payload

$result = $payload->getResult();
echo get_class($result);        // prints Phwoolcon\Payment\Process\Result

$order = $result->getOrder();
echo get_class($order);         // prints Phwoolcon\Payment\Model\Order

echo $order->getStatus();       // prints pending

$redirectUrl = $order->getOrderData('alipay_request_url');
echo $redirectUrl;              // prints url like this:
                                // https://mapi.alipay.com/gateway.do?service=alipay.wap.create.direct.pay.by.user&partner=...
                                // You can send 302 response to make browser
                                // redirecting to this url to complete a pay request

$returnUrl = $order->getOrderData('alipay_request.return_url');
echo $returnUrl;                // prints url like this:
                                // http://yoursite.com/api/alipay/return
                                // Alipay will redirect the user back to this url
                                // once the payment is complete or closed

$notifyUrl = $order->getOrderData('alipay_request.notify_url');
echo $notifyUrl;                // prints url like this:
                                // http://yoursite.com/api/alipay/callback
                                // Alipay will post callback data to this url
                                // once the payment is complete or closed
```

### Process Alipay Callback
```php
<?php
use Phalcon\Di;
use Phwoolcon\Payment\Processor;

$di = Di::getDefault();
Processor::register($di);
$payload = Processor::run(Payload::create([
    'gateway' => 'alipay',
    'method' => 'mobile_web',
    'action' => 'callback',
    'data' => $_POST,
]));
$result = $payload->getResult();
echo $result->getResponse();    // prints success
```
