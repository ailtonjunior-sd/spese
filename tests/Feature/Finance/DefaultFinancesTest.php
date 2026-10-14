<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\SetUpDefaultFinances;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultFinancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_get_an_account_and_default_categories()
    {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->sole();

        $this->assertSame(1, $user->accounts()->count());
        $this->assertSame(
            count(SetUpDefaultFinances::CATEGORIES['expense']) + count(SetUpDefaultFinances::CATEGORIES['income']),
            $user->categories()->count(),
        );
    }
}
