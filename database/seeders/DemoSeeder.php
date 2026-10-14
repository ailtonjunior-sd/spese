<?php

namespace Database\Seeders;

use App\Actions\Finance\SetUpDefaultFinances;
use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class DemoSeeder extends Seeder
{
    /**
     * Create a demo user with six months of realistic finances.
     */
    public function run(): void
    {
        mt_srand(42);

        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        (new SetUpDefaultFinances)->handle($user);

        $checking = $user->accounts()->sole();
        $checking->update(['name' => 'Checking', 'initial_balance' => 240_000]);

        $savings = $user->accounts()->create([
            'name' => 'Savings',
            'type' => AccountType::Savings,
            'initial_balance' => 800_000,
            'color' => '#16a34a',
        ]);

        $card = $user->accounts()->create([
            'name' => 'Credit card',
            'type' => AccountType::CreditCard,
            'initial_balance' => 0,
            'color' => '#a855f7',
        ]);

        $categories = $user->categories()->pluck('id', 'name');
        $today = Date::today();
        $rows = collect();

        for ($offset = 5; $offset >= 0; $offset--) {
            $month = $today->subMonthsNoOverflow($offset)->startOfMonth();

            $add = function (Account $account, string $category, TransactionType $type, int $amount, int $day, string $description) use ($rows, $month, $categories, $today, $user) {
                $date = $month->setDay(min($day, $month->daysInMonth));

                $rows->push([
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                    'category_id' => $categories[$category],
                    'type' => $type->value,
                    'amount' => $amount,
                    'description' => $description,
                    'date' => $date->toDateString(),
                    'paid' => $date->lessThanOrEqualTo($today),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            };

            $income = TransactionType::Income;
            $expense = TransactionType::Expense;

            $add($checking, 'Salary', $income, 520_000, 1, 'Monthly salary');
            $add($checking, 'Freelance', $income, mt_rand(40, 160) * 1000, mt_rand(10, 25), 'Freelance project');
            $add($checking, 'Housing', $expense, 165_000, 3, 'Rent');
            $add($checking, 'Bills & Utilities', $expense, mt_rand(110, 170) * 100, 8, 'Electricity');
            $add($checking, 'Bills & Utilities', $expense, 6_499, 12, 'Internet');
            $add($card, 'Entertainment', $expense, 1_549, 15, 'Netflix');
            $add($card, 'Entertainment', $expense, 1_199, 18, 'Spotify');
            $add($savings, 'Investments', $income, mt_rand(15, 35) * 100, 28, 'Interest');

            foreach ([2, 9, 16, 23, 30] as $day) {
                $add($checking, 'Groceries', $expense, mt_rand(6_000, 16_000), $day, $this->pick(['Whole Foods', 'Trader Joe\'s', 'Costco', 'Safeway']));
            }

            foreach (range(1, mt_rand(4, 7)) as $_) {
                $add($card, 'Restaurants', $expense, mt_rand(1_500, 7_500), mt_rand(1, 28), $this->pick(['Sushi place', 'Pizza night', 'Coffee shop', 'Burger joint', 'Thai takeout']));
            }

            foreach (range(1, mt_rand(3, 6)) as $_) {
                $add($checking, 'Transport', $expense, mt_rand(1_200, 4_500), mt_rand(1, 28), $this->pick(['Uber', 'Gas station', 'Parking', 'Metro card']));
            }

            if (mt_rand(0, 1) === 1) {
                $add($checking, 'Shopping', $expense, mt_rand(3_000, 25_000), mt_rand(1, 28), $this->pick(['Amazon', 'Clothes', 'Home goods']));
            }

            if (mt_rand(0, 2) === 0) {
                $add($checking, 'Health', $expense, mt_rand(4_000, 20_000), mt_rand(1, 28), $this->pick(['Pharmacy', 'Dentist', 'Doctor visit']));
            }

            if ($offset % 3 === 0) {
                $add($checking, 'Education', $expense, 4_900, 20, 'Online course');
            }
        }

        $this->insertInChunks($rows);
    }

    /**
     * @param  list<string>  $options
     */
    private function pick(array $options): string
    {
        return $options[mt_rand(0, count($options) - 1)];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function insertInChunks(Collection $rows): void
    {
        $rows->chunk(100)->each(fn (Collection $chunk) => Transaction::insert($chunk->values()->all()));
    }
}
