<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Archive, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import AccountController from '@/actions/App/Http/Controllers/Finance/AccountController';
import ColorPicker from '@/components/finance/ColorPicker.vue';
import ConfirmDialog from '@/components/finance/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import { centsToInput, useMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { index } from '@/routes/accounts';
import type { Account, Option } from '@/types';

const props = defineProps<{
    accounts: Account[];
    types: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Accounts', href: index() }],
    },
});

const { money } = useMoney();

const total = computed(() =>
    props.accounts
        .filter((account) => !account.archived)
        .reduce((sum, account) => sum + (account.balance ?? 0), 0),
);

const formOpen = ref(false);
const editing = ref<Account | null>(null);

const form = useForm({
    name: '',
    type: 'checking',
    initial_balance: '0.00',
    color: '#2563eb',
    archived: false,
});

function open(account: Account | null) {
    editing.value = account;
    form.defaults({
        name: account?.name ?? '',
        type: account?.type ?? 'checking',
        initial_balance: account
            ? centsToInput(account.initial_balance)
            : '0.00',
        color: account?.color ?? '#2563eb',
        archived: account?.archived ?? false,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const action = editing.value
        ? AccountController.update(editing.value.id)
        : AccountController.store();

    form.transform((data) => ({
        ...data,
        archived: data.archived ? 1 : 0,
    })).submit(action, {
        preserveScroll: true,
        onSuccess: () => (formOpen.value = false),
    });
}

const deleting = ref<Account | null>(null);
const deleteOpen = computed({
    get: () => deleting.value !== null,
    set: (value) => {
        if (!value) {
            deleting.value = null;
        }
    },
});

function confirmDelete() {
    if (!deleting.value) {
        return;
    }

    router.delete(AccountController.destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <Head title="Accounts" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Accounts"
                description="Bank accounts, cards and cash you track."
            />
            <Button @click="open(null)"><Plus /> New account</Button>
        </div>

        <div class="rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">Total balance</p>
            <p class="text-3xl font-semibold">{{ money(total) }}</p>
        </div>

        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="account in accounts"
                :key="account.id"
                :class="
                    cn(
                        'flex flex-col gap-4 rounded-xl border p-4',
                        account.archived && 'opacity-60',
                    )
                "
            >
                <div class="flex items-start gap-3">
                    <span
                        class="mt-1 size-3 shrink-0 rounded-full"
                        :style="{ backgroundColor: account.color }"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ account.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ account.type_label }}
                        </p>
                    </div>
                    <Badge v-if="account.archived" variant="secondary">
                        <Archive /> Archived
                    </Badge>
                </div>
                <p
                    :class="
                        cn(
                            'text-2xl font-semibold',
                            (account.balance ?? 0) < 0 && 'text-destructive',
                        )
                    "
                >
                    {{ money(account.balance ?? 0) }}
                </p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">
                        {{ account.transactions_count }} transactions
                    </span>
                    <div class="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Edit account"
                            @click="open(account)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Delete account"
                            @click="deleting = account"
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </div>
            </li>
        </ul>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent>
            <form class="space-y-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit account' : 'New account' }}
                    </DialogTitle>
                    <DialogDescription>
                        The starting balance is what the account held before you
                        began tracking it.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="account-name">Name</Label>
                    <Input
                        id="account-name"
                        v-model="form.name"
                        maxlength="60"
                        required
                        v-focus
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="account-type">Type</Label>
                        <Select v-model="form.type">
                            <SelectTrigger id="account-type" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in types"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="initial-balance">Starting balance</Label>
                        <Input
                            id="initial-balance"
                            v-model="form.initial_balance"
                            type="number"
                            step="0.01"
                            inputmode="decimal"
                            required
                        />
                        <InputError :message="form.errors.initial_balance" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Color</Label>
                    <ColorPicker v-model="form.color" />
                    <InputError :message="form.errors.color" />
                </div>

                <Label
                    v-if="editing"
                    class="flex items-center gap-2 font-normal"
                >
                    <Checkbox v-model="form.archived" />
                    Archived (hidden from forms and totals)
                </Label>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ editing ? 'Save changes' : 'Create account' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="deleteOpen"
        title="Delete this account?"
        description="Only accounts without transactions can be deleted. Archive accounts you no longer use."
        @confirm="confirmDelete"
    />
</template>
