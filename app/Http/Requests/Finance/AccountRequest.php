<?php

namespace App\Http\Requests\Finance;

use App\Concerns\ConvertsMoney;
use App\Enums\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    use ConvertsMoney;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'initial_balance' => ['required', 'numeric', 'between:-10000000,10000000'],
            'color' => ['required', 'hex_color'],
            'archived' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the validated data ready to be stored on the account.
     *
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'type' => $this->enum('type', AccountType::class),
            'initial_balance' => $this->toCents($this->input('initial_balance')),
            'color' => $this->string('color')->toString(),
            'archived_at' => $this->boolean('archived') ? now() : null,
        ];
    }
}
