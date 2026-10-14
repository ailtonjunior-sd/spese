<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_be_created()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Pets', 'type' => 'expense', 'color' => '#f97316'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Pets', $user->categories()->sole()->name);
    }

    public function test_names_must_be_unique_per_user_and_type()
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Pets']);
        Category::factory()->create(['name' => 'Gifts']);

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Pets', 'type' => 'expense', 'color' => '#f97316'])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Pets', 'type' => 'income', 'color' => '#f97316'])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Gifts', 'type' => 'expense', 'color' => '#f97316'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_category_in_use_cannot_change_type()
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Transaction::factory()
            ->for(Account::factory()->for($user))
            ->create(['category_id' => $category->id]);

        $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => $category->name,
            'type' => 'income',
            'color' => $category->color,
        ]);

        $this->assertSame('expense', $category->refresh()->type->value);
    }

    public function test_deleting_a_category_keeps_its_transactions()
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()
            ->for(Account::factory()->for($user))
            ->create(['category_id' => $category->id]);

        $this->actingAs($user)->delete(route('categories.destroy', $category));

        $this->assertModelMissing($category);
        $this->assertNull($transaction->refresh()->category_id);
    }

    public function test_users_cannot_update_other_users_categories()
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('categories.update', $category), ['name' => 'Mine', 'type' => 'expense', 'color' => '#000000'])
            ->assertForbidden();
    }
}
