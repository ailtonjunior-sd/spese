<?php

namespace App\Http\Requests\Finance;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:40',
                Rule::unique('categories')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', $this->input('type'))
                    ->ignore($category?->id),
            ],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'color' => ['required', 'hex_color'],
        ];
    }
}
