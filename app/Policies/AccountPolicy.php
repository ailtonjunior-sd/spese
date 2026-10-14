<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /**
     * Determine whether the user can update the account.
     */
    public function update(User $user, Account $account): bool
    {
        return $account->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the account.
     */
    public function delete(User $user, Account $account): bool
    {
        return $account->user_id === $user->id;
    }
}
