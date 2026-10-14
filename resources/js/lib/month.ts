/**
 * Months are passed around as "YYYY-MM" strings.
 */
export function shiftMonth(month: string, delta: number): string {
    const [year, m] = month.split('-').map(Number);
    const date = new Date(Date.UTC(year, m - 1 + delta, 1));

    return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}`;
}

export function monthLabel(
    month: string,
    style: 'long' | 'short' = 'long',
): string {
    const [year, m] = month.split('-').map(Number);

    return new Intl.DateTimeFormat('en-US', {
        month: style,
        year: style === 'long' ? 'numeric' : undefined,
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, m - 1, 1)));
}

export function formatDay(date: string): string {
    const [year, m, d] = date.split('-').map(Number);

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, m - 1, d)));
}

export function todayIn(month: string): string {
    const now = new Date();
    const current = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

    return month === current
        ? `${current}-${String(now.getDate()).padStart(2, '0')}`
        : `${month}-01`;
}
