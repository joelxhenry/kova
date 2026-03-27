export function useCurrencyFormatter() {
    const formatJMD = (value) => {
        if (value === null || value === undefined) return 'J$0.00';
        return new Intl.NumberFormat('en-JM', {
            style: 'currency',
            currency: 'JMD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value));
    };

    const formatNumber = (value) => {
        if (value === null || value === undefined) return '0.00';
        return new Intl.NumberFormat('en-JM', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value));
    };

    return { formatJMD, formatNumber };
}
