import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Bricolage Grotesque', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(24px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                kenburns: {
                    '0%': { transform: 'scale(1) translateY(0)' },
                    '100%': { transform: 'scale(1.08) translateY(-1.5%)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-400px 0' },
                    '100%': { backgroundPosition: '400px 0' },
                },
            },
            animation: {
                'fade-up': 'fade-up .7s cubic-bezier(.22,.8,.35,1) both',
                'fade-up-slow': 'fade-up 1s cubic-bezier(.22,.8,.35,1) both',
                'fade-in': 'fade-in .9s ease both',
                float: 'float 5s ease-in-out infinite',
                kenburns: 'kenburns 18s ease-out both',
            },
            colors: {
                // "navy" = brand ink (deep violet-black)
                navy: {
                    DEFAULT: '#1A1530',
                    50: '#F4F1FA',
                    100: '#E4DDF2',
                    600: '#2A2342',
                    700: '#1A1530',
                    800: '#120E24',
                    900: '#0B0817',
                },
                // "emerald" = primary action (electric violet)
                emerald: {
                    50: '#F0EDFF',
                    100: '#E0D9FF',
                    200: '#C6BAFF',
                    500: '#7C5CFF',
                    600: '#6C4BFF',
                    700: '#5534E6',
                    800: '#4224C2',
                    DEFAULT: '#6C4BFF',
                },
                // "gold" = accent (hot pink)
                gold: {
                    DEFAULT: '#FF5FA2',
                    light: '#FFB8D6',
                    dark: '#D6246E',
                },
                butter: '#FFE8A3',
            },
        },
    },

    plugins: [forms],
};
