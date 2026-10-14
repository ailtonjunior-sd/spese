<?php

namespace App\Actions\Finance;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\User;

class SetUpDefaultFinances
{
    /**
     * Default categories created for every new user.
     *
     * @var array<string, array<string, string>>
     */
    public const CATEGORIES = [
        'expense' => [
            'Housing' => '#6366f1',
            'Groceries' => '#22c55e',
            'Restaurants' => '#f97316',
            'Transport' => '#0ea5e9',
            'Health' => '#ef4444',
            'Education' => '#a855f7',
            'Entertainment' => '#ec4899',
            'Shopping' => '#eab308',
            'Bills & Utilities' => '#14b8a6',
            'Other' => '#64748b',
        ],
        'income' => [
            'Salary' => '#16a34a',
            'Freelance' => '#2563eb',
            'Investments' => '#9333ea',
            'Other income' => '#64748b',
        ],
    ];

    /**
     * Give a new user a starting account and a set of categories.
     */
    public function handle(User $user): void
    {
        $user->accounts()->create([
            'name' => 'Main account',
            'type' => AccountType::Checking,
            'initial_balance' => 0,
            'color' => '#2563eb',
        ]);

        foreach (self::CATEGORIES as $type => $categories) {
            foreach ($categories as $name => $color) {
                $user->categories()->create([
                    'name' => $name,
                    'type' => TransactionType::from($type),
                    'color' => $color,
                ]);
            }
        }
    }
}
