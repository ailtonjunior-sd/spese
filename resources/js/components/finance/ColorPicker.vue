<script setup lang="ts">
import { Check } from '@lucide/vue';

const SWATCHES = [
    '#2563eb',
    '#0ea5e9',
    '#14b8a6',
    '#22c55e',
    '#eab308',
    '#f97316',
    '#ef4444',
    '#ec4899',
    '#a855f7',
    '#6366f1',
    '#64748b',
];

const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            v-for="swatch in SWATCHES"
            :key="swatch"
            type="button"
            class="grid size-7 place-items-center rounded-full ring-offset-2 ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            :style="{ backgroundColor: swatch }"
            :aria-label="`Use color ${swatch}`"
            :aria-pressed="model === swatch"
            @click="model = swatch"
        >
            <Check v-if="model === swatch" class="size-4 text-white" />
        </button>
        <label
            class="relative size-7 cursor-pointer overflow-hidden rounded-full border"
            title="Custom color"
        >
            <span class="sr-only">Custom color</span>
            <input
                v-model="model"
                type="color"
                class="absolute -inset-2 size-12 cursor-pointer"
            />
        </label>
    </div>
</template>
