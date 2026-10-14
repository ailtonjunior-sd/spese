<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_only_counts_paid_transactions()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['initial_balance' => 10000]);

        Transaction::factory()->for($account)->income()->create(['amount' => 5000]);
        Transaction::factory()->for($account)->create(['amount' => 2000]);
        Transaction::factory()->for($account)->pending()->create(['amount' => 99999]);

        $this->actingAs($user)
            ->get(route('accounts.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('accounts/Index')
                ->has('accounts', 1)
                ->where('accounts.0.balance', 13000)
                ->where('accounts.0.transactions_count', 3)
            );
    }

    public function test_an_account_can_be_created()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Savings',
                'type' => 'savings',
                'initial_balance' => '1500.50',
                'color' => '#22c55e',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(150050, $user->accounts()->sole()->initial_balance);
    }

    public function test_an_account_can_be_archived()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this->actingAs($user)
            ->put(route('accounts.update', $account), [
                'name' => $account->name,
                'type' => $account->type->value,
                'initial_balance' => '0',
                'color' => '#22c55e',
                'archived' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($account->refresh()->archived_at);
    }

    public function test_accounts_with_transactions_cannot_be_deleted()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        Transaction::factory()->for($account)->create();

        $this->actingAs($user)->delete(route('accounts.destroy', $account));

        $this->assertModelExists($account);
    }

    public function test_empty_accounts_can_be_deleted()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this->actingAs($user)->delete(route('accounts.destroy', $account));

        $this->assertModelMissing($account);
    }

    public function test_users_cannot_delete_other_users_accounts()
    {
        $account = Account::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('accounts.destroy', $account))
            ->assertForbidden();
    }
}
