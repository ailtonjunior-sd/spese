<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import TransactionController from '@/actions/App/Http/Controllers/Finance/TransactionController';
import TypeToggle from '@/components/finance/TypeToggle.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { centsToInput } from '@/lib/money';
import { todayIn } from '@/lib/month';
import type { Account, Category, Transaction, TransactionType } from '@/types';

const props = defineProps<{
    transaction: Transaction | null;
    accounts: Account[];
    categories: Category[];
    month: string;
    defaultType?: TransactionType;
}>();

const open = defineModel<boolean>('open', { required: true });

const NO_CATEGORY = 'none';

const typeOptions = [
    { value: 'expense', label: 'Expense' },
    { value: 'income', label: 'Income' },
];

const form = useForm({
    type: 'expense' as TransactionType,
    amount: '',
    description: '',
    date: '',
    account_id: '',
    category_id: NO_CATEGORY,
    paid: true,
    notes: '',
});

const categoryOptions = computed(() =>
    props.categories.filter((category) => category.type === form.type),
);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    const t = props.transaction;

    form.defaults({
        type: t?.type ?? props.defaultType ?? 'expense',
        amount: t ? centsToInput(t.amount) : '',
        description: t?.description ?? '',
        date: t?.date ?? todayIn(props.month),
        account_id: String(t?.account_id ?? props.accounts[0]?.id ?? ''),
        category_id: t?.category_id ? String(t.category_id) : NO_CATEGORY,
        paid: t?.paid ?? true,
        notes: t?.notes ?? '',
    });
    form.reset();
    form.clearErrors();
});

watch(
    () => form.type,
    () => {
        const stillValid = categoryOptions.value.some(
            (category) => String(category.id) === form.category_id,
        );

        if (!stillValid) {
            form.category_id = NO_CATEGORY;
        }
    },
);

function submit() {
    const action = props.transaction
        ? TransactionController.update(props.transaction.id)
        : TransactionController.store();

    form.transform((data) => ({
        ...data,
        category_id: data.category_id === NO_CATEGORY ? null : data.category_id,
        paid: data.paid ? 1 : 0,
    })).submit(action, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="space-y-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            transaction ? 'Edit transaction' : 'New transaction'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Record money coming in or going out of an account.
                    </DialogDescription>
                </DialogHeader>

                <TypeToggle v-model="form.type" :options="typeOptions" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="amount">Amount</Label>
                        <Input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            required
                            v-focus
                        />
                        <InputError :message="form.errors.amount" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="date">Date</Label>
                        <Input
                            id="date"
                            v-model="form.date"
                            type="date"
                            required
                        />
                        <InputError :message="form.errors.date" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        maxlength="120"
                        placeholder="e.g. Groceries at Trader Joe's"
                        required
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="account">Account</Label>
                        <Select v-model="form.account_id">
                            <SelectTrigger id="account" class="w-full">
                                <SelectValue placeholder="Choose an account" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="String(account.id)"
                                >
                                    <span
                                        class="size-2 rounded-full"
                                        :style="{
                                            backgroundColor: account.color,
                                        }"
                                    />
                                    {{ account.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.account_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">Category</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger id="category" class="w-full">
                                <SelectValue placeholder="Uncategorized" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NO_CATEGORY">
                                    Uncategorized
                                </SelectItem>
                                <SelectItem
                                    v-for="category in categoryOptions"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    <span
                                        class="size-2 rounded-full"
                                        :style="{
                                            backgroundColor: category.color,
                                        }"
                                    />
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notes</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        maxlength="1000"
                        placeholder="Optional"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <Label class="flex items-center gap-2 font-normal">
                    <Checkbox v-model="form.paid" />
                    {{ form.type === 'income' ? 'Received' : 'Paid' }}
                </Label>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ transaction ? 'Save changes' : 'Add transaction' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
