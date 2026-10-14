<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
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
            'type' => $this->type->value,
            'amount' => $this->amount,
            'description' => $this->description,
            'date' => $this->date->toDateString(),
            'paid' => $this->paid,
            'notes' => $this->notes,
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'account' => $this->whenLoaded('account', fn () => [
                'name' => $this->account->name,
                'color' => $this->account->color,
            ]),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'name' => $this->category->name,
                'color' => $this->category->color,
            ] : null),
        ];
    }
}
