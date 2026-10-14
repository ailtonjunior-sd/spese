<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import MonthSwitcher from '@/components/finance/MonthSwitcher.vue';
import TransactionFormDialog from '@/components/finance/TransactionFormDialog.vue';
import TrendChart from '@/components/finance/TrendChart.vue';
import { Button } from '@/components/ui/button';
import { useMoney } from '@/lib/money';
import { formatDay } from '@/lib/month';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as transactionsIndex } from '@/routes/transactions';
import type { Account, Category, Transaction, TransactionType } from '@/types';

type Summary = {
    balance: number;
    income: number;
    expense: number;
    pending_expense: number;
    pending_income: number;
};

const props = defineProps<{
    month: string;
    summary: Summary;
    expensesByCategory: { name: string; color: string; amount: number }[];
    trend: { month: string; income: number; expense: number }[];
    accounts: Account[];
    recent: Transaction[];
    categories: Category[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const { money } = useMoney();

const net = computed(() => props.summary.income - props.summary.expense);

const categoryMax = computed(() =>
    Math.max(...props.expensesByCategory.map((c) => c.amount), 1),
);

const formOpen = ref(false);
const defaultType = ref<TransactionType>('expense');

function create(type: TransactionType) {
    defaultType.value = type;
    formOpen.value = true;
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <MonthSwitcher :month="month" :url="dashboard.url()" />
            <div class="flex gap-2">
                <Button variant="outline" @click="create('income')">
                    <Plus /> Income
                </Button>
                <Button @click="create('expense')"><Plus /> Expense</Button>
            </div>
        </div>

        <section class="grid gap-4 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div class="rounded-xl border p-5">
                <p class="text-sm text-muted-foreground">Total balance</p>
                <p
                    :class="
                        cn(
                            'mt-1 text-4xl font-semibold tracking-tight md:text-5xl',
                            summary.balance < 0 && 'text-destructive',
                        )
                    "
                >
                    {{ money(summary.balance) }}
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                    Across all active accounts, paid transactions only
                </p>
            </div>
            <div class="rounded-xl border p-5">
                <p
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <span
                        class="size-2.5 rounded-sm"
                        style="background-color: var(--viz-income)"
                    />
                    Income
                </p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ money(summary.income) }}
                </p>
                <p
                    v-if="summary.pending_income > 0"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    {{ money(summary.pending_income) }} still to receive
                </p>
            </div>
            <div class="rounded-xl border p-5">
                <p
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <span
                        class="size-2.5 rounded-sm"
                        style="background-color: var(--viz-expense)"
                    />
                    Expenses
                </p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ money(summary.expense) }}
                </p>
                <p
                    v-if="summary.pending_expense > 0"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    {{ money(summary.pending_expense) }} still to pay
                </p>
            </div>
            <div class="rounded-xl border p-5">
                <p class="text-sm text-muted-foreground">Net this month</p>
                <p
                    :class="
                        cn(
                            'mt-1 text-2xl font-semibold',
                            net < 0 && 'text-destructive',
                        )
                    "
                >
                    {{ net > 0 ? '+' : '' }}{{ money(net) }}
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{
                        net >= 0
                            ? 'You saved money'
                            : 'You spent more than you earned'
                    }}
                </p>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-5">
            <div class="rounded-xl border p-5 lg:col-span-3">
                <h2 class="mb-4 font-medium">Income vs. expenses</h2>
                <TrendChart :data="trend" :active-month="month" />
            </div>

            <div class="rounded-xl border p-5 lg:col-span-2">
                <h2 class="mb-4 font-medium">Where the money went</h2>
                <p
                    v-if="expensesByCategory.length === 0"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    No expenses this month.
                </p>
                <ul v-else class="space-y-3">
                    <li
                        v-for="category in expensesByCategory"
                        :key="category.name"
                        class="space-y-1.5"
                    >
                        <div class="flex items-center gap-2 text-sm">
                            <span
                                class="size-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: category.color }"
                            />
                            <span class="flex-1 truncate">{{
                                category.name
                            }}</span>
                            <span class="font-medium tabular-nums">
                                {{ money(category.amount) }}
                            </span>
                            <span
                                class="w-10 text-right text-xs text-muted-foreground tabular-nums"
                            >
                                {{
                                    Math.round(
                                        (category.amount / summary.expense) *
                                            100,
                                    )
                                }}%
                            </span>
                        </div>
                        <div class="h-1.5 rounded-full bg-muted">
                            <div
                                class="h-full rounded-full"
                                :style="{
                                    width: `${(category.amount / categoryMax) * 100}%`,
                                    backgroundColor: 'var(--viz-expense)',
                                }"
                            />
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-5">
            <div class="rounded-xl border p-5 lg:col-span-3">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-medium">Latest transactions</h2>
                    <Link
                        :href="transactionsIndex({ query: { month } })"
                        class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        View all <ArrowRight class="size-4" />
                    </Link>
                </div>
                <p
                    v-if="recent.length === 0"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    Nothing recorded this month yet.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="transaction in recent"
                        :key="transaction.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :style="{
                                backgroundColor:
                                    transaction.category?.color ?? '#94a3b8',
                            }"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ transaction.description }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDay(transaction.date) }} ·
                                {{
                                    transaction.category?.name ??
                                    'Uncategorized'
                                }}
                                <template v-if="!transaction.paid">
                                    · Pending</template
                                >
                            </p>
                        </div>
                        <span
                            :class="
                                cn(
                                    'text-sm font-semibold tabular-nums',
                                    transaction.type === 'income' &&
                                        'text-emerald-700 dark:text-emerald-400',
                                )
                            "
                        >
                            {{ transaction.type === 'income' ? '+' : '−'
                            }}{{ money(transaction.amount) }}
                        </span>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border p-5 lg:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-medium">Accounts</h2>
                    <Link
                        :href="accountsIndex()"
                        class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        Manage <ArrowRight class="size-4" />
                    </Link>
                </div>
                <ul class="divide-y">
                    <li
                        v-for="account in accounts"
                        :key="account.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :style="{ backgroundColor: account.color }"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ account.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ account.type_label }}
                            </p>
                        </div>
                        <span
                            :class="
                                cn(
                                    'text-sm font-semibold tabular-nums',
                                    (account.balance ?? 0) < 0 &&
                                        'text-destructive',
                                )
                            "
                        >
                            {{ money(account.balance ?? 0) }}
                        </span>
                    </li>
                </ul>
            </div>
        </section>
    </div>

    <TransactionFormDialog
        v-model:open="formOpen"
        :transaction="null"
        :accounts="accounts"
        :categories="categories"
        :month="month"
        :default-type="defaultType"
    />
</template>
