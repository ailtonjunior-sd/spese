<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CircleCheck, CircleDashed, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import TransactionController from '@/actions/App/Http/Controllers/Finance/TransactionController';
import ConfirmDialog from '@/components/finance/ConfirmDialog.vue';
import MonthSwitcher from '@/components/finance/MonthSwitcher.vue';
import TransactionFormDialog from '@/components/finance/TransactionFormDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useMoney } from '@/lib/money';
import { formatDay } from '@/lib/month';
import { cn } from '@/lib/utils';
import { index } from '@/routes/transactions';
import type { Account, Category, Transaction, TransactionType } from '@/types';

type Filters = {
    type: TransactionType | null;
    account: number | null;
    category: number | null;
    search: string | null;
};

const props = defineProps<{
    month: string;
    filters: Filters;
    transactions: Transaction[];
    totals: { income: number; expense: number };
    accounts: Account[];
    categories: Category[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transactions', href: index() }],
    },
});

const { money } = useMoney();
const ALL = 'all';

const filterForm = reactive({
    type: props.filters.type ?? ALL,
    account: props.filters.account ? String(props.filters.account) : ALL,
    category: props.filters.category ? String(props.filters.category) : ALL,
    search: props.filters.search ?? '',
});

const activeQuery = computed(() => ({
    type: filterForm.type === ALL ? null : filterForm.type,
    account: filterForm.account === ALL ? null : filterForm.account,
    category: filterForm.category === ALL ? null : filterForm.category,
    search: filterForm.search || null,
}));

let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => [filterForm.type, filterForm.account, filterForm.category],
    () => applyFilters(),
);

watch(
    () => filterForm.search,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 300);
    },
);

function applyFilters() {
    router.get(
        index.url(),
        { ...activeQuery.value, month: props.month },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const hasFilters = computed(() =>
    Object.values(activeQuery.value).some((value) => value !== null),
);

function clearFilters() {
    Object.assign(filterForm, {
        type: ALL,
        account: ALL,
        category: ALL,
        search: '',
    });
}

const groupedByDay = computed(() => {
    const groups = new Map<string, Transaction[]>();

    for (const transaction of props.transactions) {
        const group = groups.get(transaction.date) ?? [];
        group.push(transaction);
        groups.set(transaction.date, group);
    }

    return [...groups.entries()];
});

const formOpen = ref(false);
const editing = ref<Transaction | null>(null);
const defaultType = ref<TransactionType>('expense');

function create(type: TransactionType) {
    editing.value = null;
    defaultType.value = type;
    formOpen.value = true;
}

function edit(transaction: Transaction) {
    editing.value = transaction;
    formOpen.value = true;
}

function togglePaid(transaction: Transaction) {
    router.patch(
        TransactionController.togglePaid.url(transaction.id),
        {},
        { preserveScroll: true },
    );
}

const deleting = ref<Transaction | null>(null);
const deleteOpen = computed({
    get: () => deleting.value !== null,
    set: (value) => {
        if (!value) {
            deleting.value = null;
        }
    },
});
const deleteProcessing = ref(false);

function confirmDelete() {
    if (!deleting.value) {
        return;
    }

    router.delete(TransactionController.destroy.url(deleting.value.id), {
        preserveScroll: true,
        onStart: () => (deleteProcessing.value = true),
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head title="Transactions" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Transactions"
                description="Everything that moved in and out of your accounts."
            />
            <div class="flex gap-2">
                <Button variant="outline" @click="create('income')">
                    <Plus /> Income
                </Button>
                <Button @click="create('expense')"><Plus /> Expense</Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <MonthSwitcher
                :month="month"
                :url="index.url()"
                :query="activeQuery"
            />
            <Input
                v-model="filterForm.search"
                type="search"
                placeholder="Search description"
                class="w-full sm:w-52"
                aria-label="Search description"
            />
            <Select v-model="filterForm.type">
                <SelectTrigger class="w-36" aria-label="Type">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All types</SelectItem>
                    <SelectItem value="income">Income</SelectItem>
                    <SelectItem value="expense">Expenses</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="filterForm.account">
                <SelectTrigger class="w-44" aria-label="Account">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All accounts</SelectItem>
                    <SelectItem
                        v-for="account in accounts"
                        :key="account.id"
                        :value="String(account.id)"
                    >
                        {{ account.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="filterForm.category">
                <SelectTrigger class="w-44" aria-label="Category">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All categories</SelectItem>
                    <SelectItem
                        v-for="category in categories"
                        :key="category.id"
                        :value="String(category.id)"
                    >
                        {{ category.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Button
                v-if="hasFilters"
                variant="ghost"
                size="sm"
                @click="clearFilters"
            >
                Clear filters
            </Button>
        </div>

        <dl class="grid grid-cols-3 gap-4 rounded-xl border p-4">
            <div>
                <dt class="text-sm text-muted-foreground">Income</dt>
                <dd class="text-lg font-semibold">
                    {{ money(totals.income) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Expenses</dt>
                <dd class="text-lg font-semibold">
                    {{ money(totals.expense) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Net</dt>
                <dd class="text-lg font-semibold">
                    {{ money(totals.income - totals.expense) }}
                </dd>
            </div>
        </dl>

        <div
            v-if="transactions.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <p class="font-medium">No transactions here yet</p>
            <p class="text-sm text-muted-foreground">
                {{
                    hasFilters
                        ? 'Nothing matches these filters this month.'
                        : 'Add your first income or expense for this month.'
                }}
            </p>
            <Button v-if="!hasFilters" @click="create('expense')">
                <Plus /> Add expense
            </Button>
        </div>

        <div v-else class="space-y-6">
            <section v-for="[day, items] in groupedByDay" :key="day">
                <h3
                    class="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{ formatDay(day) }}
                </h3>
                <ul class="divide-y rounded-xl border">
                    <li
                        v-for="transaction in items"
                        :key="transaction.id"
                        class="group flex items-center gap-3 px-4 py-3"
                    >
                        <button
                            type="button"
                            class="shrink-0 text-muted-foreground hover:text-foreground"
                            :aria-label="
                                transaction.paid
                                    ? 'Mark as not paid'
                                    : 'Mark as paid'
                            "
                            :title="transaction.paid ? 'Paid' : 'Pending'"
                            @click="togglePaid(transaction)"
                        >
                            <CircleCheck
                                v-if="transaction.paid"
                                class="size-5 text-emerald-600 dark:text-emerald-500"
                            />
                            <CircleDashed v-else class="size-5" />
                        </button>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ transaction.description }}
                            </p>
                            <p
                                class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                            >
                                <span class="inline-flex items-center gap-1.5">
                                    <span
                                        class="size-2 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                transaction.category?.color ??
                                                '#94a3b8',
                                        }"
                                    />
                                    {{
                                        transaction.category?.name ??
                                        'Uncategorized'
                                    }}
                                </span>
                                <span>{{ transaction.account?.name }}</span>
                                <span v-if="!transaction.paid">Pending</span>
                            </p>
                        </div>

                        <p
                            :class="
                                cn(
                                    'shrink-0 font-semibold tabular-nums',
                                    transaction.type === 'income' &&
                                        'text-emerald-700 dark:text-emerald-400',
                                    !transaction.paid && 'opacity-60',
                                )
                            "
                        >
                            {{ transaction.type === 'income' ? '+' : '−'
                            }}{{ money(transaction.amount) }}
                        </p>

                        <div
                            class="flex shrink-0 gap-1 opacity-100 transition-opacity md:opacity-0 md:group-focus-within:opacity-100 md:group-hover:opacity-100"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Edit"
                                @click="edit(transaction)"
                            >
                                <Pencil />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Delete"
                                @click="deleting = transaction"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </div>

    <TransactionFormDialog
        v-model:open="formOpen"
        :transaction="editing"
        :accounts="accounts"
        :categories="categories"
        :month="month"
        :default-type="defaultType"
    />

    <ConfirmDialog
        v-model:open="deleteOpen"
        title="Delete this transaction?"
        :description="`“${deleting?.description}” will be removed permanently.`"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
    />
</template>
