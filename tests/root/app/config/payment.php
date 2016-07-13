<?php

return [
    'gateways' => [
        'alipay' => [
            'description' => 'Alipay',
            'methods' => [
                'mobile_web_pay' => [
                    'class' => 'Phwoolcon\Payment\Alipay\MobileWebPay',
                    'description' => 'Alipay Mobile Web Pay',
                ],
            ],
        ],
        'test_gateway' => [
            'description' => 'Test',
            'order_prefix' => 'TEST',
            'methods' => [
                'test_pay' => [
                    'class' => 'Phwoolcon\Payment\Tests\Helper\TestPaymentMethod',
                    'description' => 'Test Pay',
                ],
                'invalid_method' => [
                    'class' => 'Phwoolcon\Payment\Tests\Helper\InvalidPaymentMethod',
                    'description' => 'Invalid Pay',
                ],
            ],
        ],
    ],
];
