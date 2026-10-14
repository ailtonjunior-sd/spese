<?php

namespace App\Http\Controllers\Finance;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\AccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * List the user's accounts with their balances.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $accounts = $user->accounts()
            ->withBalance()
            ->withCount('transactions')
            ->orderByRaw('archived_at is not null')
            ->orderBy('name')
            ->get();

        return Inertia::render('accounts/Index', [
            'accounts' => AccountResource::collection($accounts)->resolve(),
            'types' => AccountType::options(),
        ]);
    }

    /**
     * Store a new account.
     */
    public function store(AccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->accounts()->create($request->toModelAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account created.')]);

        return back();
    }

    /**
     * Update an account.
     */
    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $account->update($request->toModelAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account updated.')]);

        return back();
    }

    /**
     * Delete an account. Accounts with transactions must be archived instead.
     */
    public function destroy(Account $account): RedirectResponse
    {
        Gate::authorize('delete', $account);

        if ($account->transactions()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This account has transactions. Archive it instead.'),
            ]);

            return back();
        }

        $account->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account deleted.')]);

        return back();
    }
}
