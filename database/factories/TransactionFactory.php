<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => fn (array $attributes) => Account::query()->whereKey($attributes['account_id'])->value('user_id'),
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => fake()->numberBetween(500, 50_000),
            'description' => fake()->words(3, true),
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
            'paid' => true,
        ];
    }

    /**
     * Indicate that the transaction is income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
        ]);
    }

    /**
     * Indicate that the transaction has not been paid yet.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid' => false,
        ]);
    }
}
