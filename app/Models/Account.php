<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property AccountType $type
 * @property int $initial_balance
 * @property string $color
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property int|string|null $income_total
 * @property int|string|null $expense_total
 */
#[Fillable(['name', 'type', 'initial_balance', 'color', 'archived_at'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Only accounts that have not been archived.
     *
     * @param  Builder<Account>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Load the paid income and expense totals needed to compute the balance.
     *
     * @param  Builder<Account>  $query
     */
    public function scopeWithBalance(Builder $query): void
    {
        $query
            ->withSum(['transactions as income_total' => fn (Builder $q) => $q
                ->where('type', TransactionType::Income)
                ->where('paid', true)], 'amount')
            ->withSum(['transactions as expense_total' => fn (Builder $q) => $q
                ->where('type', TransactionType::Expense)
                ->where('paid', true)], 'amount');
    }

    /**
     * Current balance in cents. Requires the withBalance scope.
     */
    public function balance(): int
    {
        return $this->initial_balance + (int) $this->income_total - (int) $this->expense_total;
    }
}
