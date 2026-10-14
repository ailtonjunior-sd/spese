<?php

namespace App\Http\Controllers\Finance;

use App\Concerns\ResolvesMonth;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TransactionRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    use ResolvesMonth;

    /**
     * List the user's transactions for a month.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $month = $this->resolveMonth($request);

        $filters = [
            'type' => $request->enum('type', TransactionType::class)?->value,
            'account' => $request->integer('account') ?: null,
            'category' => $request->integer('category') ?: null,
            'search' => $request->string('search')->trim()->limit(60, '')->toString() ?: null,
        ];

        $transactions = $user->transactions()
            ->with(['account', 'category'])
            ->inMonth($month)
            ->when($filters['type'], fn ($q, $type) => $q->where('type', $type))
            ->when($filters['account'], fn ($q, $id) => $q->where('account_id', $id))
            ->when($filters['category'], fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['search'], fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $sumOf = fn (TransactionType $type) => (int) $transactions
            ->where('type', $type)
            ->sum('amount');

        return Inertia::render('transactions/Index', [
            'month' => $month->format('Y-m'),
            'filters' => $filters,
            'transactions' => TransactionResource::collection($transactions)->resolve(),
            'totals' => [
                'income' => $sumOf(TransactionType::Income),
                'expense' => $sumOf(TransactionType::Expense),
            ],
            'accounts' => AccountResource::collection(
                $user->accounts()->active()->orderBy('name')->get()
            )->resolve(),
            'categories' => CategoryResource::collection(
                $user->categories()->orderBy('name')->get()
            )->resolve(),
        ]);
    }

    /**
     * Store a new transaction.
     */
    public function store(TransactionRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->transactions()->create($request->toModelAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction added.')]);

        return back();
    }

    /**
     * Update a transaction.
     */
    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('update', $transaction);

        $transaction->update($request->toModelAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction updated.')]);

        return back();
    }

    /**
     * Toggle whether a transaction has been paid.
     */
    public function togglePaid(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('update', $transaction);

        $transaction->update(['paid' => ! $transaction->paid]);

        return back();
    }

    /**
     * Delete a transaction.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('delete', $transaction);

        $transaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction deleted.')]);

        return back();
    }
}
