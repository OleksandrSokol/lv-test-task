<?php

return [
    'webhook' => [
        'invalid_timestamp' => 'Заголовок X-Webhook-Timestamp отсутствует или выходит за допустимое окно.',
        'invalid_signature' => 'Неверная подпись X-Webhook-Signature.',
        'id_mismatch'       => 'id события не совпадает с заголовком X-Webhook-Id.',
    ],
];
