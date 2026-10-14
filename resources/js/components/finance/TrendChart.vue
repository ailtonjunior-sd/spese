<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import { useMoney } from '@/lib/money';
import { monthLabel } from '@/lib/month';

type Point = { month: string; income: number; expense: number };

const props = defineProps<{ data: Point[]; activeMonth: string }>();

const { money, compactMoney } = useMoney();

const HEIGHT = 240;
const PAD = { top: 12, right: 8, bottom: 28, left: 56 };
const BAR_MAX = 24;
const BAR_GAP = 2;
const RADIUS = 4;

const SERIES = [
    { key: 'income', label: 'Income', color: 'var(--viz-income)' },
    { key: 'expense', label: 'Expenses', color: 'var(--viz-expense)' },
] as const;

const container = useTemplateRef<HTMLElement>('container');
const { width } = useElementSize(container);

const plotWidth = computed(() =>
    Math.max(width.value - PAD.left - PAD.right, 0),
);
const plotHeight = HEIGHT - PAD.top - PAD.bottom;

/** Round the axis max up to a clean 1/2/2.5/5 × 10^n step. */
const ticks = computed(() => {
    const max = Math.max(
        ...props.data.flatMap((p) => [p.income, p.expense]),
        0,
    );

    if (max === 0) {
        return [0, 100_00];
    }

    const rough = max / 4;
    const magnitude = 10 ** Math.floor(Math.log10(rough));
    const step =
        [1, 2, 2.5, 5, 10].map((m) => m * magnitude).find((s) => s >= rough) ??
        rough;
    const count = Math.ceil(max / step);

    return Array.from({ length: count + 1 }, (_, i) => i * step);
});

const yMax = computed(() => ticks.value[ticks.value.length - 1]);
const y = (value: number) =>
    PAD.top + plotHeight - (value / yMax.value) * plotHeight;

const band = computed(() => plotWidth.value / Math.max(props.data.length, 1));
const barWidth = computed(() =>
    Math.max(Math.min(BAR_MAX, (band.value * 0.6 - BAR_GAP) / 2), 2),
);

function barPath(x: number, value: number): string {
    const top = y(value);
    const bottom = PAD.top + plotHeight;
    const h = bottom - top;

    if (h <= 0) {
        return '';
    }

    const w = barWidth.value;
    const r = Math.min(RADIUS, h, w / 2);

    return `M${x},${bottom}V${top + r}Q${x},${top} ${x + r},${top}H${x + w - r}Q${x + w},${top} ${x + w},${top + r}V${bottom}Z`;
}

const groups = computed(() =>
    props.data.map((point, i) => {
        const center = PAD.left + band.value * i + band.value / 2;
        const start = center - barWidth.value - BAR_GAP / 2;

        return {
            point,
            center,
            bandX: PAD.left + band.value * i,
            bars: SERIES.map((series, s) => ({
                ...series,
                path: barPath(
                    start + s * (barWidth.value + BAR_GAP),
                    point[series.key],
                ),
            })),
        };
    }),
);

const hovered = ref<number | null>(null);
const tooltip = computed(() => {
    if (hovered.value === null) {
        return null;
    }

    const group = groups.value[hovered.value];
    const left = Math.min(
        Math.max(group.center, 80),
        Math.max(width.value - 80, 80),
    );

    return { group, left };
});
</script>

<template>
    <div class="space-y-3">
        <ul class="flex gap-4 text-sm text-muted-foreground">
            <li
                v-for="series in SERIES"
                :key="series.key"
                class="flex items-center gap-2"
            >
                <span
                    class="size-2.5 rounded-sm"
                    :style="{ backgroundColor: series.color }"
                />
                {{ series.label }}
            </li>
        </ul>

        <div
            ref="container"
            class="relative"
            :style="{ height: `${HEIGHT}px` }"
        >
            <svg
                v-if="width > 0"
                :width="width"
                :height="HEIGHT"
                role="img"
                aria-label="Income and expenses over the last six months"
            >
                <g>
                    <template v-for="tick in ticks" :key="tick">
                        <line
                            :x1="PAD.left"
                            :x2="width - PAD.right"
                            :y1="y(tick)"
                            :y2="y(tick)"
                            :stroke="
                                tick === 0
                                    ? 'var(--viz-baseline)'
                                    : 'var(--viz-grid)'
                            "
                            stroke-width="1"
                            shape-rendering="crispEdges"
                        />
                        <text
                            :x="PAD.left - 8"
                            :y="y(tick)"
                            text-anchor="end"
                            dominant-baseline="middle"
                            class="fill-muted-foreground text-[11px] tabular-nums"
                        >
                            {{ compactMoney(tick) }}
                        </text>
                    </template>
                </g>

                <g
                    v-for="(group, i) in groups"
                    :key="group.point.month"
                    tabindex="0"
                    class="cursor-default outline-none"
                    :aria-label="`${monthLabel(group.point.month)}: income ${money(group.point.income)}, expenses ${money(group.point.expense)}`"
                    @pointerenter="hovered = i"
                    @pointerleave="hovered = null"
                    @focus="hovered = i"
                    @blur="hovered = null"
                >
                    <rect
                        :x="group.bandX"
                        :y="PAD.top"
                        :width="band"
                        :height="plotHeight"
                        :class="
                            hovered === i ? 'fill-muted/60' : 'fill-transparent'
                        "
                    />
                    <path
                        v-for="bar in group.bars"
                        :key="bar.key"
                        :d="bar.path"
                        :fill="bar.color"
                    />
                    <text
                        :x="group.center"
                        :y="HEIGHT - 8"
                        text-anchor="middle"
                        :class="[
                            'text-[11px]',
                            group.point.month === activeMonth
                                ? 'fill-foreground font-semibold'
                                : 'fill-muted-foreground',
                        ]"
                    >
                        {{ monthLabel(group.point.month, 'short') }}
                    </text>
                </g>
            </svg>

            <div
                v-if="tooltip"
                class="pointer-events-none absolute top-0 z-10 min-w-40 -translate-x-1/2 rounded-lg border bg-popover px-3 py-2 text-sm shadow-md"
                :style="{ left: `${tooltip.left}px` }"
            >
                <p class="mb-1 text-xs text-muted-foreground">
                    {{ monthLabel(tooltip.group.point.month) }}
                </p>
                <p
                    v-for="series in SERIES"
                    :key="series.key"
                    class="flex items-center gap-2"
                >
                    <span
                        class="h-0.5 w-3 rounded-full"
                        :style="{ backgroundColor: series.color }"
                    />
                    <span class="font-semibold tabular-nums">
                        {{ money(tooltip.group.point[series.key]) }}
                    </span>
                    <span class="text-muted-foreground">{{
                        series.label
                    }}</span>
                </p>
            </div>
        </div>

        <details class="text-sm">
            <summary class="cursor-pointer text-muted-foreground">
                Show as table
            </summary>
            <table class="mt-2 w-full text-left">
                <thead class="text-muted-foreground">
                    <tr>
                        <th class="py-1 font-normal">Month</th>
                        <th class="py-1 text-right font-normal">Income</th>
                        <th class="py-1 text-right font-normal">Expenses</th>
                    </tr>
                </thead>
                <tbody class="tabular-nums">
                    <tr
                        v-for="point in data"
                        :key="point.month"
                        class="border-t"
                    >
                        <td class="py-1">{{ monthLabel(point.month) }}</td>
                        <td class="py-1 text-right">
                            {{ money(point.income) }}
                        </td>
                        <td class="py-1 text-right">
                            {{ money(point.expense) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </details>
    </div>
</template>
