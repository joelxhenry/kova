import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useFiscalYear(initialYear) {
    const currentYear = new Date().getFullYear();
    const year = ref(initialYear ?? currentYear);

    const years = Array.from({ length: 5 }, (_, i) => {
        const y = currentYear - 2 + i;
        return { label: String(y), value: y };
    });

    const changeYear = (newYear) => {
        year.value = newYear;
        router.get(window.location.pathname, { year: newYear }, { preserveState: true });
    };

    return { year, years, changeYear };
}
