<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Main checking', 'Savings', 'Wallet', 'Travel fund']),
            'type' => fake()->randomElement(AccountType::cases()),
            'initial_balance' => fake()->numberBetween(0, 500_000),
            'color' => fake()->hexColor(),
        ];
    }

    /**
     * Indicate that the account is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
