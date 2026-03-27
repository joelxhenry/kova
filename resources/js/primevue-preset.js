import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';

const KovaPreset = definePreset(Aura, {
    primitive: {
        borderRadius: {
            none: '0',
            xs: '6px',
            sm: '8px',
            md: '12px',
            lg: '16px',
            xl: '24px',
        },
    },
    semantic: {
        transitionDuration: '200ms',
        focusRing: {
            width: '2px',
            style: 'solid',
            color: '#476664',
            offset: '2px',
            shadow: 'none',
        },
        disabledOpacity: '0.5',
        primary: {
            50: '#fef4f2',
            100: '#fee6e1',
            200: '#fecec5',
            300: '#fcab9d',
            400: '#fa7d66',
            500: '#F95831',
            600: '#e64420',
            700: '#c23618',
            800: '#a02e17',
            900: '#842c1a',
            950: '#481308',
        },
        formField: {
            paddingX: '1rem',
            paddingY: '0.75rem',
            borderRadius: '{border.radius.md}',
            focusRing: {
                width: '2px',
                style: 'solid',
                color: '#D3E2DE',
                offset: '0',
                shadow: 'none',
            },
            transitionDuration: '200ms',
        },
        overlay: {
            select: {
                borderRadius: '{border.radius.lg}',
                shadow: '0 8px 24px rgba(23, 39, 38, 0.08)',
            },
            popover: {
                borderRadius: '{border.radius.lg}',
                padding: '1rem',
                shadow: '0 8px 24px rgba(23, 39, 38, 0.08)',
            },
            modal: {
                borderRadius: '{border.radius.xl}',
                padding: '1.5rem',
                shadow: '0 16px 32px rgba(23, 39, 38, 0.12)',
            },
            navigation: {
                shadow: '0 8px 24px rgba(23, 39, 38, 0.08)',
            },
        },
        content: {
            borderRadius: '{border.radius.lg}',
        },
        colorScheme: {
            light: {
                surface: {
                    0: '#FFFFFF',
                    50: '#FAFAFA',
                    100: '#F0F5F4',
                    200: '#D3E2DE',
                    300: '#A8C5BF',
                    400: '#7BA8A0',
                    500: '#476664',
                    600: '#3A5553',
                    700: '#2E4442',
                    800: '#243F3D',
                    900: '#1C3230',
                    950: '#172726',
                },
                primary: {
                    color: '#F95831',
                    contrastColor: '#FFFFFF',
                    hoverColor: '#e64420',
                    activeColor: '#c23618',
                },
                highlight: {
                    background: '#fef4f2',
                    focusBackground: '#fee6e1',
                    color: '#c23618',
                    focusColor: '#a02e17',
                },
                formField: {
                    background: '#FFFFFF',
                    disabledBackground: '#F0F5F4',
                    filledBackground: '#F0F5F4',
                    filledHoverBackground: '#D3E2DE',
                    filledFocusBackground: '#FFFFFF',
                    borderColor: '#D3E2DE',
                    hoverBorderColor: '#476664',
                    focusBorderColor: '#476664',
                    invalidBorderColor: '#F95831',
                    color: '#172726',
                    disabledColor: '#7BA8A0',
                    placeholderColor: 'rgba(71, 102, 100, 0.6)',
                    floatLabelColor: '#476664',
                    floatLabelFocusColor: '#476664',
                    floatLabelActiveColor: '#476664',
                    iconColor: '#476664',
                    shadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.02)',
                },
                text: {
                    color: '#172726',
                    hoverColor: '#172726',
                    mutedColor: '#476664',
                    hoverMutedColor: '#3A5553',
                },
                content: {
                    background: '#FAFAFA',
                    hoverBackground: '#F0F5F4',
                    borderColor: '#D3E2DE',
                    color: '#172726',
                    hoverColor: '#172726',
                },
                overlay: {
                    select: {
                        background: '#FFFFFF',
                        borderColor: '#D3E2DE',
                        color: '#172726',
                    },
                    popover: {
                        background: '#FFFFFF',
                        borderColor: '#D3E2DE',
                        color: '#172726',
                    },
                    modal: {
                        background: '#FFFFFF',
                        borderColor: '#D3E2DE',
                        color: '#172726',
                    },
                },
            },
        },
    },
    components: {
        button: {
            colorScheme: {
                light: {
                    root: {
                        primary: {
                            background: 'color-mix(in srgb, {primary.color}, transparent 88%)',
                            hoverBackground: 'color-mix(in srgb, {primary.color}, transparent 80%)',
                            activeBackground: 'color-mix(in srgb, {primary.color}, transparent 72%)',
                            borderColor: 'transparent',
                            hoverBorderColor: 'transparent',
                            activeBorderColor: 'transparent',
                            color: '{primary.color}',
                            hoverColor: '{primary.color}',
                            activeColor: '{primary.active.color}',
                        },
                    },
                    text: {
                        primary: {
                            background: 'color-mix(in srgb, {primary.color}, transparent 90%)',
                            hoverBackground: 'color-mix(in srgb, {primary.color}, transparent 80%)',
                            activeBackground: 'color-mix(in srgb, {primary.color}, transparent 72%)',
                            color: '{primary.color}',
                        },
                    },
                },
            },
        },
    },
});

export default KovaPreset;
