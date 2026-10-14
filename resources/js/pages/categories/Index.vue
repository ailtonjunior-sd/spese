<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Finance/CategoryController';
import ColorPicker from '@/components/finance/ColorPicker.vue';
import ConfirmDialog from '@/components/finance/ConfirmDialog.vue';
import TypeToggle from '@/components/finance/TypeToggle.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { index } from '@/routes/categories';
import type { Category, Option, TransactionType } from '@/types';

const props = defineProps<{
    categories: Category[];
    types: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categories', href: index() }],
    },
});

const sections = computed(() => [
    {
        type: 'expense' as TransactionType,
        title: 'Expense categories',
        items: props.categories.filter((c) => c.type === 'expense'),
    },
    {
        type: 'income' as TransactionType,
        title: 'Income categories',
        items: props.categories.filter((c) => c.type === 'income'),
    },
]);

const formOpen = ref(false);
const editing = ref<Category | null>(null);

const form = useForm({
    name: '',
    type: 'expense' as TransactionType,
    color: '#64748b',
});

function open(category: Category | null, type: TransactionType = 'expense') {
    editing.value = category;
    form.defaults({
        name: category?.name ?? '',
        type: category?.type ?? type,
        color: category?.color ?? '#64748b',
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    const action = editing.value
        ? CategoryController.update(editing.value.id)
        : CategoryController.store();

    form.submit(action, {
        preserveScroll: true,
        onSuccess: () => (formOpen.value = false),
    });
}

const deleting = ref<Category | null>(null);
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

    router.delete(CategoryController.destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <Head title="Categories" />

    <div class="flex flex-1 flex-col gap-8 p-4 md:p-6">
        <Heading
            title="Categories"
            description="Group transactions to see where your money goes."
        />

        <section v-for="section in sections" :key="section.type">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-medium">{{ section.title }}</h3>
                <Button
                    variant="outline"
                    size="sm"
                    @click="open(null, section.type)"
                >
                    <Plus /> Add
                </Button>
            </div>
            <ul class="divide-y rounded-xl border">
                <li
                    v-for="category in section.items"
                    :key="category.id"
                    class="flex items-center gap-3 px-4 py-2.5"
                >
                    <span
                        class="size-3 shrink-0 rounded-full"
                        :style="{ backgroundColor: category.color }"
                    />
                    <span class="flex-1 truncate">{{ category.name }}</span>
                    <span class="text-xs text-muted-foreground">
                        {{ category.transactions_count }} transactions
                    </span>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Edit category"
                        @click="open(category)"
                    >
                        <Pencil />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Delete category"
                        @click="deleting = category"
                    >
                        <Trash2 />
                    </Button>
                </li>
                <li
                    v-if="section.items.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    No categories yet.
                </li>
            </ul>
        </section>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent>
            <form class="space-y-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit category' : 'New category' }}
                    </DialogTitle>
                    <DialogDescription>
                        Categories are separate for income and expenses.
                    </DialogDescription>
                </DialogHeader>

                <TypeToggle v-model="form.type" :options="types" />

                <div class="grid gap-2">
                    <Label for="category-name">Name</Label>
                    <Input
                        id="category-name"
                        v-model="form.name"
                        maxlength="40"
                        required
                        v-focus
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label>Color</Label>
                    <ColorPicker v-model="form.color" />
                    <InputError :message="form.errors.color" />
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ editing ? 'Save changes' : 'Create category' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="deleteOpen"
        title="Delete this category?"
        :description="`Transactions in “${deleting?.name}” will become uncategorized.`"
        @confirm="confirmDelete"
    />
</template>
