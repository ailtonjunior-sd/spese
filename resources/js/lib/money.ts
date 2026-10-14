import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Format integer cents as a localized currency string.
 */
export function formatMoney(
    cents: number,
    currency: string,
    options: Intl.NumberFormatOptions = {},
): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        ...options,
    }).format(cents / 100);
}

/**
 * Convert integer cents into the decimal string used by form inputs.
 */
export function centsToInput(cents: number): string {
    return (cents / 100).toFixed(2);
}

/**
 * Money formatter bound to the app's configured currency.
 */
export function useMoney() {
    const page = usePage();
    const currency = computed(() => page.props.currency);

    return {
        money: (cents: number) => formatMoney(cents, currency.value),
        compactMoney: (cents: number) =>
            formatMoney(cents, currency.value, {
                notation: 'compact',
                maximumFractionDigits: 1,
            }),
    };
}
