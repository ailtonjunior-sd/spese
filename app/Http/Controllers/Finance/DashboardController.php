<?php

namespace App\Http\Controllers\Finance;

use App\Concerns\ResolvesMonth;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesMonth;

    /**
     * Number of months shown in the income vs. expenses trend.
     */
    private const TREND_MONTHS = 6;

    /**
     * Show the monthly overview of the user's finances.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $month = $this->resolveMonth($request);

        $monthTransactions = $user->transactions()
            ->with('category')
            ->inMonth($month)
            ->get();

        $income = $monthTransactions->where('type', TransactionType::Income);
        $expenses = $monthTransactions->where('type', TransactionType::Expense);

        $accounts = $user->accounts()->active()->withBalance()->orderBy('name')->get();

        return Inertia::render('Dashboard', [
            'month' => $month->format('Y-m'),
            'summary' => [
                'balance' => $accounts->sum(fn ($account) => $account->balance()),
                'income' => (int) $income->sum('amount'),
                'expense' => (int) $expenses->sum('amount'),
                'pending_expense' => (int) $expenses->where('paid', false)->sum('amount'),
                'pending_income' => (int) $income->where('paid', false)->sum('amount'),
            ],
            'expensesByCategory' => $expenses
                ->groupBy('category_id')
                ->map(fn ($group) => [
                    'name' => $group->first()?->category->name ?? 'Uncategorized',
                    'color' => $group->first()?->category->color ?? '#94a3b8',
                    'amount' => (int) $group->sum('amount'),
                ])
                ->sortByDesc('amount')
                ->values(),
            'trend' => $this->trend($user, $month),
            'accounts' => AccountResource::collection($accounts)->resolve(),
            'categories' => CategoryResource::collection(
                $user->categories()->orderBy('name')->get()
            )->resolve(),
            'recent' => TransactionResource::collection(
                $user->transactions()
                    ->with(['account', 'category'])
                    ->inMonth($month)
                    ->where('date', '<=', Date::today()->toDateString())
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get()
            )->resolve(),
        ]);
    }

    /**
     * Income and expense totals for the months leading up to the given one.
     *
     * @return list<array{month: string, income: int, expense: int}>
     */
    private function trend(User $user, CarbonImmutable $month): array
    {
        $start = $month->subMonths(self::TREND_MONTHS - 1)->startOfMonth();
        $end = $month->endOfMonth();

        $totals = $user->transactions()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'type', 'amount'])
            ->groupBy(fn (Transaction $transaction) => $transaction->date->format('Y-m'));

        $trend = [];

        for ($cursor = $start; $cursor <= $end; $cursor = $cursor->addMonth()) {
            $group = $totals->get($cursor->format('Y-m'), collect());

            $trend[] = [
                'month' => $cursor->format('Y-m'),
                'income' => (int) $group->where('type', TransactionType::Income)->sum('amount'),
                'expense' => (int) $group->where('type', TransactionType::Expense)->sum('amount'),
            ];
        }

        return $trend;
    }
}
