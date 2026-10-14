<?php

namespace App\Http\Requests\Finance;

use App\Concerns\ConvertsMoney;
use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    use ConvertsMoney;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'description' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date'],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('user_id', $userId)
                    ->where('type', $this->input('type')),
            ],
            'paid' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Choose a category that matches the transaction type.',
        ];
    }

    /**
     * Get the validated data ready to be stored on the transaction.
     *
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        return [
            'type' => $this->enum('type', TransactionType::class),
            'amount' => $this->toCents($this->input('amount')),
            'description' => $this->string('description')->toString(),
            'date' => $this->date('date'),
            'account_id' => $this->integer('account_id'),
            'category_id' => $this->filled('category_id') ? $this->integer('category_id') : null,
            'paid' => $this->boolean('paid'),
            'notes' => $this->filled('notes') ? $this->string('notes')->toString() : null,
        ];
    }
}
