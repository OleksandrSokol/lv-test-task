<?php

namespace App\Modules\Order\Http\Requests;

use App\Modules\Order\Dtos\OrderFilterData;
use App\Modules\Order\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query of GET /api/orders; bounds come from openapi.yaml.
 */
class ListOrdersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status'   => ['nullable', Rule::enum(OrderStatusEnum::class)],
            'email'    => ['nullable', 'string', 'max:255'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * One message per parameter: what is allowed, whichever rule failed.
     */
    public function messages(): array
    {
        $statuses = implode(', ', array_column(OrderStatusEnum::cases(), 'value'));

        return [
            'status'   => __('orders.filter.status', ['values' => $statuses]),
            'email'    => __('orders.filter.email'),
            'page'     => __('orders.filter.page'),
            'per_page' => __('orders.filter.per_page'),
        ];
    }

    public function filter(): OrderFilterData
    {
        return new OrderFilterData(
            status: $this->enum('status', OrderStatusEnum::class),
            email: $this->validated('email'),
            page: $this->integer('page', 1),
            perPage: $this->integer('per_page', 20),
        );
    }
}
