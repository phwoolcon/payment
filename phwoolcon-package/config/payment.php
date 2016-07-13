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
    ],
];
