<?php

namespace App\Modules\Marketplace\Http\Requests;

use App\Modules\Marketplace\Dtos\WebhookEventData;
use App\Modules\Marketplace\Enums\WebhookEventTypeEnum;
use App\Modules\Order\Dtos\OrderSnapshotData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A marketplace event. Rules come from the Data classes, so they live in one place.
 *
 * The envelope is always validated; the order snapshot only for a type we know —
 * an unknown type is accepted as is and ignored.
 */
class MarketplaceWebhookRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = WebhookEventData::getValidationRules($this->all());

        if ($this->knownType() === null) {
            return $rules;
        }

        $order = $this->input('data.order');
        $rules['data.order'] = ['required', 'array'];

        foreach (OrderSnapshotData::getValidationRules(is_array($order) ? $order : []) as $field => $fieldRules) {
            $rules["data.order.{$field}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->header('X-Webhook-Id') !== $this->input('id')) {
                    $validator->errors()->add('id', __('marketplace.webhook.id_mismatch'));
                }
            },
        ];
    }

    public function event(): WebhookEventData
    {
        return WebhookEventData::from($this->validated());
    }

    public function snapshot(): ?OrderSnapshotData
    {
        return $this->knownType() !== null ? OrderSnapshotData::from($this->validated('data.order')) : null;
    }

    private function knownType(): ?WebhookEventTypeEnum
    {
        return WebhookEventTypeEnum::tryFrom((string) $this->input('type'));
    }
}
