<?php

use App\Modules\Notification\Enums\LetterEnum;

// Initial text of the letter templates; after seeding they are edited in the admin panel.
return [
    LetterEnum::ORDER_PAID->value => [
        'subject' => 'Заказ №{{ number }} оплачен',
        'body'    => <<<'TEXT'
            Здравствуйте, {{ customer_name }}!

            Ваш заказ №{{ number }} оплачен.

            Состав заказа:
            {{ items }}

            Доставка: {{ delivery_price }} {{ currency }}
            Скидка: {{ discount }} {{ currency }}
            Итого: {{ total }} {{ currency }}
            TEXT,
    ],

    LetterEnum::ORDER_SHIPPED->value => [
        'subject' => 'Заказ №{{ number }} передан в доставку',
        'body'    => <<<'TEXT'
            Здравствуйте, {{ customer_name }}!

            Ваш заказ №{{ number }} передан в доставку.

            Служба доставки: {{ carrier }}
            Трек-номер: {{ tracking_number }}
            Отследить посылку: {{ tracking_url }}
            TEXT,
    ],

    LetterEnum::ORDER_CANCELLED->value => [
        'subject' => 'Заказ №{{ number }} отменён',
        'body'    => <<<'TEXT'
            Здравствуйте, {{ customer_name }}!

            Ваш заказ №{{ number }} отменён.

            Причина: {{ cancel_reason }}
            {{ refund_note }}
            TEXT,
    ],
];
