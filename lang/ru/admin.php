<?php

return [
    'actions' => [
        'view_logs'     => 'Журнал',
        'view_webhooks' => 'Вебхуки',
    ],

    'filters' => [
        'order'           => 'Заказ',
        'order_indicator' => 'Заказ: :order',
    ],

    'navigation' => [
        'orders'  => 'Заказы',
        'letters' => 'Письма',
        'system'  => 'Система',
    ],

    'orders' => [
        'model'  => 'заказ',
        'plural' => 'Заказы',

        'sections' => [
            'order'    => 'Заказ',
            'customer' => 'Покупатель',
            'shipment' => 'Доставка',
            'items'    => 'Позиции',
            'history'  => 'История статусов',
            'letters'  => 'Письма покупателю',
        ],

        'fields' => [
            'external_id'    => 'ID в MarketHub',
            'number'         => 'Номер',
            'status'         => 'Статус',
            'customer_name'  => 'Покупатель',
            'customer_email' => 'Email',
            'customer_phone' => 'Телефон',
            'total'          => 'Итого',
            'delivery_price' => 'Доставка',
            'discount'       => 'Скидка',
            'currency'       => 'Валюта',
            'created_at'     => 'Оформлен',
            'updated_at'     => 'Обновлён в MarketHub',
            'received_at'    => 'Получен нами',
            'paid_at'        => 'Оплачен',
            'cancelled_at'   => 'Отменён',
            'cancel_reason'  => 'Причина отмены',
            'carrier'        => 'Служба доставки',
            'tracking'       => 'Трек-номер',
            'sku'            => 'Артикул',
            'name'           => 'Товар',
            'quantity'       => 'Кол-во',
            'price'          => 'Цена',
            'subtotal'       => 'Сумма',
            'changed_at'     => 'Дата',
        ],

        'no_shipment' => 'Ещё не передан в доставку',
        'no_letters'  => 'Писем не было',
    ],

    'letter_templates' => [
        'model'  => 'шаблон письма',
        'plural' => 'Шаблоны писем',

        'fields' => [
            'key'         => 'Письмо',
            'sender'      => 'Email отправителя',
            'sender_name' => 'Имя отправителя',
            'subject'     => 'Тема',
            'body'        => 'Текст',
            'updated_at'  => 'Изменён',
        ],

        'sender_helper' => 'Пусто — адрес из настроек почты (MAIL_FROM_ADDRESS).',
        'body_helper'   => 'Подстановки вида {{ number }} заменяются данными заказа при отправке — список ниже.',

        'placeholders' => [
            'title'       => 'Подстановки',
            'description' => 'Работают в теме и тексте любого письма; данные берутся из текущего состояния заказа.',
            'key'         => 'Подстановка',
            'value'       => 'Что подставляется',

            'list' => [
                'number'          => 'Номер заказа в MarketHub, например MH-1048213',
                'customer_name'   => 'Имя покупателя',
                'items'           => 'Состав заказа, по строке на позицию: «- Название × 3 — 3870.00 RUB»',
                'delivery_price'  => 'Стоимость доставки, например 349.00',
                'discount'        => 'Скидка, например 200.00',
                'total'           => 'Итоговая сумма заказа, например 4909.50',
                'currency'        => 'Код валюты: RUB, USD или EUR',
                'carrier'         => 'Служба доставки; пусто, пока заказ не передан в доставку',
                'tracking_number' => 'Трек-номер отправления; пусто до передачи в доставку',
                'tracking_url'    => 'Ссылка для отслеживания; пусто до передачи в доставку',
                'cancel_reason'   => 'Причина отмены; «не указана», если маркетплейс её не прислал',
                'refund_note'     => 'Фраза о возврате денег, если заказ был оплачен; иначе пусто',
            ],
        ],
    ],

    'log_letters' => [
        'model'  => 'письмо',
        'plural' => 'Журнал писем',

        'fields' => [
            'template_key'  => 'Письмо',
            'order'         => 'Заказ',
            'receiver'      => 'Получатель',
            'sender'        => 'Отправитель',
            'subject'       => 'Тема',
            'body'          => 'Текст',
            'status'        => 'Статус',
            'error_message' => 'Причина',
            'created_at'    => 'Создано',
            'updated_at'    => 'Обновлено',
        ],
    ],

    'logs' => [
        'model'  => 'запись журнала',
        'plural' => 'Журнал действий',

        'fields' => [
            'created_at'   => 'Дата',
            'event'        => 'Событие',
            'subject_type' => 'Объект',
            'subject_id'   => 'ID объекта',
            'source'       => 'Источник',
            'data'         => 'Данные',
            'from'         => 'С',
            'until'        => 'По',
        ],

        'subjects' => [
            'order'         => 'Заказ',
            'log_letter'    => 'Письмо',
            'webhook_event' => 'Вебхук',
        ],

        'subject_indicator' => 'ID объекта: :id',
        'period_indicator'  => 'Период: :from — :until',
    ],

    'webhook_events' => [
        'model'  => 'вебхук',
        'plural' => 'Вебхуки',

        'fields' => [
            'event_id'          => 'ID события',
            'type'              => 'Тип',
            'order'             => 'Заказ',
            'order_external_id' => 'ID заказа в MarketHub',
            'status'            => 'Результат',
            'occurred_at'       => 'Событие в MarketHub',
            'created_at'        => 'Получен нами',
            'raw_body'          => 'Тело запроса',
        ],

        'raw_body_hint' => 'Отформатировано для чтения; «Копировать» даёт тело байт в байт, как пришло и как подписано.',
        'unknown_type'  => 'Неизвестный тип: :type',

        'duplicates_hint' => 'Повторная доставка с тем же ID события не сохраняется отдельной строкой — её видно в журнале.',
    ],

    'users' => [
        'model'  => 'пользователь',
        'plural' => 'Пользователи',

        'fields' => [
            'name'       => 'Имя',
            'email'      => 'Email',
            'password'   => 'Новый пароль',
            'created_at' => 'Создан',
        ],

        'password_helper' => 'Пусто — пароль не меняется.',
    ],
];
