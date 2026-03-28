export function useCurrencyFormatter() {
    const formatJMD = (value) => {
        if (value === null || value === undefined) return '$0.00';
        const num = Number(value);
        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Math.abs(num));
        return num < 0 ? `-$${formatted}` : `$${formatted}`;
    };

    const formatNumber = (value) => {
        if (value === null || value === undefined) return '0.00';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value));
    };

    return { formatJMD, formatNumber };
}
