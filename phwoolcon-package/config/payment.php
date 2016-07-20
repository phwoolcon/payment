<?php

return [
    'gateways' => [
        'alipay' => [
            'description' => 'Alipay',
            'methods' => [
                'mobile_web' => [
                    'class' => 'Phwoolcon\Payment\Alipay\MobileWebPay',
                    'description' => 'Alipay Mobile Web Pay',
                ],
            ],
        ],
    ],
];
