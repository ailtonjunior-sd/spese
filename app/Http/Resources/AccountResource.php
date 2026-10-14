<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'color' => $this->color,
            'initial_balance' => $this->initial_balance,
            'balance' => $this->when(
                array_key_exists('income_total', $this->resource->getAttributes()),
                fn () => $this->resource->balance(),
            ),
            'archived' => $this->archived_at !== null,
            'transactions_count' => $this->whenCounted('transactions'),
        ];
    }
}
