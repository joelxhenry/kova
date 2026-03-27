import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';

const KovaPreset = definePreset(Aura, {
    primitive: {
        borderRadius: {
            none: '0',
            xs: '0',
            sm: '0',
            md: '0',
            lg: '0',
            xl: '0',
        },
    },
    semantic: {
        transitionDuration: '150ms',
        focusRing: {
            width: '2px',
            style: 'solid',
            color: '{primary.color}',
            offset: '2px',
            shadow: 'none',
        },
        disabledOpacity: '0.5',
        primary: {
            50: '#fff5f2',
            100: '#ffe6de',
            200: '#ffcabd',
            300: '#ffa48c',
            400: '#ff724a',
            500: '#FF3D00',
            600: '#e63600',
            700: '#c42e00',
            800: '#a12600',
            900: '#7a1d00',
            950: '#4a1100',
        },
        formField: {
            paddingX: '1rem',
            paddingY: '0.75rem',
            borderRadius: '0',
            focusRing: {
                width: '0',
                style: 'none',
                color: 'transparent',
                offset: '0',
                shadow: 'none',
            },
            transitionDuration: '150ms',
        },
        overlay: {
            select: {
                borderRadius: '0',
                shadow: 'none',
            },
            popover: {
                borderRadius: '0',
                padding: '1rem',
                shadow: 'none',
            },
            modal: {
                borderRadius: '0',
                padding: '1.5rem',
                shadow: 'none',
            },
            navigation: {
                shadow: 'none',
            },
        },
        content: {
            borderRadius: '0',
        },
        colorScheme: {
            light: {
                surface: {
                    0: '#FFFFFF',
                    50: '#FAFAFA',
                    100: '#F5F5F5',
                    200: '#E5E5E5',
                    300: '#D4D4D4',
                    400: '#A3A3A3',
                    500: '#737373',
                    600: '#525252',
                    700: '#404040',
                    800: '#262626',
                    900: '#171717',
                    950: '#0A0A0A',
                },
                primary: {
                    color: '#FF3D00',
                    contrastColor: '#FAFAFA',
                    hoverColor: '#e63600',
                    activeColor: '#c42e00',
                },
                highlight: {
                    background: '#fff5f2',
                    focusBackground: '#ffe6de',
                    color: '#c42e00',
                    focusColor: '#a12600',
                },
                formField: {
                    background: '#F0F0F0',
                    disabledBackground: '#E5E5E5',
                    filledBackground: '#F0F0F0',
                    filledHoverBackground: '#E5E5E5',
                    filledFocusBackground: '#F0F0F0',
                    borderColor: '#E5E5E5',
                    hoverBorderColor: '#D4D4D4',
                    focusBorderColor: '#FF3D00',
                    invalidBorderColor: '#FF3D00',
                    color: '#0A0A0A',
                    disabledColor: '#A3A3A3',
                    placeholderColor: '#525252',
                    floatLabelColor: '#525252',
                    floatLabelFocusColor: '#FF3D00',
                    floatLabelActiveColor: '#525252',
                    iconColor: '#525252',
                    shadow: 'none',
                },
                text: {
                    color: '#0A0A0A',
                    hoverColor: '#0A0A0A',
                    mutedColor: '#525252',
                    hoverMutedColor: '#404040',
                },
                content: {
                    background: '#FAFAFA',
                    hoverBackground: '#F0F0F0',
                    borderColor: '#E5E5E5',
                    color: '#0A0A0A',
                    hoverColor: '#0A0A0A',
                },
                overlay: {
                    select: {
                        background: '#FFFFFF',
                        borderColor: '#E5E5E5',
                        color: '#0A0A0A',
                    },
                    popover: {
                        background: '#FFFFFF',
                        borderColor: '#E5E5E5',
                        color: '#0A0A0A',
                    },
                    modal: {
                        background: '#FFFFFF',
                        borderColor: '#E5E5E5',
                        color: '#0A0A0A',
                    },
                },
            },
        },
    },
});

export default KovaPreset;
