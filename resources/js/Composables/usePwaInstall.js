import { ref, onMounted, computed } from 'vue';

const DISMISS_KEY = 'kova_pwa_dismiss';
const DISMISS_DAYS = 7;

function isDismissed() {
    const dismissed = localStorage.getItem(DISMISS_KEY);
    if (!dismissed) return false;
    const dismissedAt = Number(dismissed);
    const daysSince = (Date.now() - dismissedAt) / (1000 * 60 * 60 * 24);
    return daysSince < DISMISS_DAYS;
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

function isIosSafari() {
    const ua = navigator.userAgent;
    const isIos = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|Chrome/.test(ua);
    return isIos && isSafari;
}

export function usePwaInstall() {
    const canInstall = ref(false);
    const showIosPrompt = ref(false);
    let deferredPrompt = null;

    const showBanner = computed(() => {
        return (canInstall.value || showIosPrompt.value) && !isDismissed();
    });

    onMounted(() => {
        if (isStandalone()) return;

        // Chrome/Edge/Firefox — native install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (!isDismissed()) {
                canInstall.value = true;
            }
        });

        window.addEventListener('appinstalled', () => {
            canInstall.value = false;
            showIosPrompt.value = false;
            deferredPrompt = null;
        });

        // iOS Safari — show custom instructions
        if (isIosSafari() && !isDismissed()) {
            showIosPrompt.value = true;
        }
    });

    const install = async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        deferredPrompt = null;
        if (outcome === 'accepted') {
            canInstall.value = false;
        }
    };

    const dismiss = () => {
        localStorage.setItem(DISMISS_KEY, String(Date.now()));
        canInstall.value = false;
        showIosPrompt.value = false;
    };

    return { canInstall, showIosPrompt, showBanner, install, dismiss };
}
