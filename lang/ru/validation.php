<?php

// Only the rules this project uses; anything else falls back to English.
return [
    'array'       => 'Поле :attribute должно быть массивом.',
    'date_format' => 'Поле :attribute должно соответствовать формату :format.',
    'enum'        => 'Недопустимое значение поля :attribute.',
    'in'          => 'Недопустимое значение поля :attribute.',
    'integer'     => 'Поле :attribute должно быть целым числом.',
    'max'         => [
        'array'   => 'Поле :attribute должно содержать не более :max элементов.',
        'numeric' => 'Поле :attribute должно быть не больше :max.',
        'string'  => 'Поле :attribute должно быть не длиннее :max символов.',
    ],
    'min' => [
        'array'   => 'Поле :attribute должно содержать не менее :min элементов.',
        'numeric' => 'Поле :attribute должно быть не меньше :min.',
        'string'  => 'Поле :attribute должно быть не короче :min символов.',
    ],
    'numeric'  => 'Поле :attribute должно быть числом.',
    'present'  => 'Поле :attribute должно присутствовать.',
    'regex'    => 'Поле :attribute имеет неверный формат.',
    'required' => 'Поле :attribute обязательно.',
    'string'   => 'Поле :attribute должно быть строкой.',
];
