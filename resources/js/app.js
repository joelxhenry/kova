import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import NProgress from 'nprogress';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import KovaPreset from './primevue-preset.js';
import 'primeicons/primeicons.css';
import '../css/app.css';

// Inertia progress bar via NProgress
NProgress.configure({ showSpinner: false });
router.on('start', () => NProgress.start());
router.on('finish', (event) => {
    if (event.detail.visit.completed) NProgress.done();
    else if (event.detail.visit.interrupted) NProgress.done();
    else if (event.detail.visit.cancelled) NProgress.done();
});

createInertiaApp({
    title: (title) => title ? `${title} — Kova` : 'Kova',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: KovaPreset,
                    options: {
                        darkModeSelector: false,
                        cssLayer: false,
                    },
                },
            })
            .use(ToastService)
            .use(ConfirmationService)
            .mount(el);
    },
});
