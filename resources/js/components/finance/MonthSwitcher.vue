<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { monthLabel, shiftMonth } from '@/lib/month';

const props = defineProps<{
    month: string;
    url: string;
    query?: Record<string, string | number | null>;
}>();

const label = computed(() => monthLabel(props.month));

function go(delta: number) {
    router.get(
        props.url,
        { ...props.query, month: shiftMonth(props.month, delta) },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <div class="flex items-center gap-1">
        <Button
            variant="ghost"
            size="icon"
            aria-label="Previous month"
            @click="go(-1)"
        >
            <ChevronLeft />
        </Button>
        <span class="min-w-36 text-center text-sm font-medium">
            {{ label }}
        </span>
        <Button
            variant="ghost"
            size="icon"
            aria-label="Next month"
            @click="go(1)"
        >
            <ChevronRight />
        </Button>
    </div>
</template>
