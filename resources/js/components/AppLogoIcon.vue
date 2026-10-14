<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- Wallet mark: filled body with a cut-out clasp, so it works with fill-current. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill-rule="evenodd"
            d="M5 4a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-1.5h-4.5a3.5 3.5 0 1 1 0-7H22V7a3 3 0 0 0-3-3H5Zm12.5 7a1.5 1.5 0 1 0 0 3H22v-3h-4.5Z"
        />
    </svg>
</template>
