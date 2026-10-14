<?php

namespace Tests\Feature\Finance;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_the_users_transactions_for_the_month()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($account)->create(['date' => '2026-03-10', 'amount' => 1500]);
        Transaction::factory()->for($account)->income()->create(['date' => '2026-03-20', 'amount' => 90000]);
        Transaction::factory()->for($account)->create(['date' => '2026-04-01']);
        Transaction::factory()->create(['date' => '2026-03-15']);

        $this->actingAs($user)
            ->get(route('transactions.index', ['month' => '2026-03']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('transactions/Index')
                ->where('month', '2026-03')
                ->has('transactions', 2)
                ->where('totals.income', 90000)
                ->where('totals.expense', 1500)
            );
    }

    public function test_index_can_be_filtered()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($account)->create(['date' => '2026-03-10', 'description' => 'Coffee beans']);
        Transaction::factory()->for($account)->income()->create(['date' => '2026-03-10', 'description' => 'Invoice #12']);

        $this->actingAs($user)
            ->get(route('transactions.index', ['month' => '2026-03', 'type' => 'income']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions', 1)
                ->where('transactions.0.description', 'Invoice #12')
            );

        $this->actingAs($user)
            ->get(route('transactions.index', ['month' => '2026-03', 'search' => 'coffee']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions', 1)
                ->where('transactions.0.description', 'Coffee beans')
            );
    }

    public function test_an_invalid_month_falls_back_to_the_current_month()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('transactions.index', ['month' => '2026-13']))
            ->assertInertia(fn (Assert $page) => $page->where('month', now()->format('Y-m')));
    }

    public function test_a_transaction_can_be_created_with_the_amount_in_cents()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => '12.35',
                'description' => 'Lunch',
                'date' => '2026-03-10',
                'account_id' => $account->id,
                'category_id' => $category->id,
                'paid' => '1',
            ])
            ->assertSessionHasNoErrors();

        $transaction = $user->transactions()->sole();

        $this->assertSame(1235, $transaction->amount);
        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('2026-03-10', $transaction->date->toDateString());
        $this->assertTrue($transaction->paid);
    }

    public function test_accounts_and_categories_of_other_users_are_rejected()
    {
        $user = User::factory()->create();
        $otherAccount = Account::factory()->create();
        $otherCategory = Category::factory()->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => '10',
                'description' => 'Sneaky',
                'date' => '2026-03-10',
                'account_id' => $otherAccount->id,
                'category_id' => $otherCategory->id,
            ])
            ->assertSessionHasErrors(['account_id', 'category_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_the_category_must_match_the_transaction_type()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $incomeCategory = Category::factory()->for($user)->income()->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => '10',
                'description' => 'Groceries',
                'date' => '2026-03-10',
                'account_id' => $account->id,
                'category_id' => $incomeCategory->id,
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_a_transaction_can_be_updated()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($account)->create();

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'type' => 'income',
                'amount' => '2500',
                'description' => 'Client payment',
                'date' => '2026-03-31',
                'account_id' => $account->id,
                'paid' => '0',
            ])
            ->assertSessionHasNoErrors();

        $transaction->refresh();

        $this->assertSame(250000, $transaction->amount);
        $this->assertSame(TransactionType::Income, $transaction->type);
        $this->assertFalse($transaction->paid);
    }

    public function test_paid_status_can_be_toggled()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()
            ->for(Account::factory()->for($user))
            ->pending()
            ->create();

        $this->actingAs($user)->patch(route('transactions.toggle-paid', $transaction));

        $this->assertTrue($transaction->refresh()->paid);
    }

    public function test_a_transaction_can_be_deleted()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for(Account::factory()->for($user))->create();

        $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $this->assertModelMissing($transaction);
    }

    public function test_users_cannot_touch_other_users_transactions()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();

        $this->actingAs($user)->patch(route('transactions.toggle-paid', $transaction))->assertForbidden();
        $this->actingAs($user)->delete(route('transactions.destroy', $transaction))->assertForbidden();

        $this->assertModelExists($transaction);
    }
}
