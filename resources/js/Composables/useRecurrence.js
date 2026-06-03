/**
 * useRecurrence — human-readable labels for recurring-transaction frequency rules.
 *
 * Shared by the Recurring Transactions views and the Budget Projections view so
 * frequency wording stays consistent. Frequencies mirror the backend enum:
 * daily | weekly | biweekly | monthly | yearly.
 */
export function useRecurrence() {
    // Short adjective label for a single frequency (e.g. table badges).
    const FREQUENCY_LABELS = {
        daily: 'Daily',
        weekly: 'Weekly',
        biweekly: 'Every 2 weeks',
        monthly: 'Monthly',
        yearly: 'Yearly',
    };

    // Options ready for a PrimeVue <Select> (Recurring Create/Edit forms).
    const frequencyOptions = [
        { label: 'Daily', value: 'daily' },
        { label: 'Weekly', value: 'weekly' },
        { label: 'Every 2 weeks', value: 'biweekly' },
        { label: 'Monthly', value: 'monthly' },
        { label: 'Yearly', value: 'yearly' },
    ];

    const frequencyLabel = (frequency) => FREQUENCY_LABELS[frequency] ?? 'Unknown';

    const formatDate = (value) => {
        if (!value) return null;
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return null;
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(date);
    };

    /**
     * Full sentence describing a recurrence rule, e.g.
     *   "Every 2 weeks from Jun 1, 2026 until Dec 31, 2026"
     *   "Monthly from Jun 1, 2026"
     *
     * @param {{ frequency: string, start_date?: string, end_date?: string|null }} rule
     */
    const recurrenceLabel = (rule) => {
        if (!rule || !rule.frequency) return '';

        const parts = [frequencyLabel(rule.frequency)];
        const start = formatDate(rule.start_date);
        const end = formatDate(rule.end_date);

        if (start) parts.push(`from ${start}`);
        parts.push(end ? `until ${end}` : 'with no end date');

        return parts.join(' ');
    };

    return { frequencyOptions, frequencyLabel, recurrenceLabel };
}
