<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_summarises_the_selected_month()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 0]);
        $food = Category::factory()->for($user)->create(['name' => 'Food']);
        $rent = Category::factory()->for($user)->create(['name' => 'Rent']);

        Transaction::factory()->for($account)->income()->create(['date' => '2026-03-01', 'amount' => 500000]);
        Transaction::factory()->for($account)->create(['date' => '2026-03-02', 'amount' => 150000, 'category_id' => $rent->id]);
        Transaction::factory()->for($account)->create(['date' => '2026-03-05', 'amount' => 3000, 'category_id' => $food->id]);
        Transaction::factory()->for($account)->pending()->create(['date' => '2026-03-28', 'amount' => 2000, 'category_id' => $food->id]);
        Transaction::factory()->for($account)->create(['date' => '2026-01-15', 'amount' => 10000]);

        $this->actingAs($user)
            ->get(route('dashboard', ['month' => '2026-03']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.income', 500000)
                ->where('summary.expense', 155000)
                ->where('summary.pending_expense', 2000)
                ->where('summary.balance', 500000 - 150000 - 3000 - 10000)
                ->where('expensesByCategory.0.name', 'Rent')
                ->where('expensesByCategory.1.name', 'Food')
                ->where('expensesByCategory.1.amount', 5000)
                ->has('trend', 6)
                ->where('trend.0.month', '2025-10')
                ->where('trend.3.expense', 10000)
                ->where('trend.5.income', 500000)
                ->has('recent', 4)
            );
    }
}
