<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { Option } from '@/types';

defineProps<{ options: Option[] }>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <div role="radiogroup" class="inline-flex rounded-lg bg-muted p-1 text-sm">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="model === option.value"
            :class="
                cn(
                    'rounded-md px-3 py-1.5 font-medium text-muted-foreground transition-colors',
                    model === option.value &&
                        'bg-background text-foreground shadow-xs',
                )
            "
            @click="model = option.value"
        >
            {{ option.label }}
        </button>
    </div>
</template>
