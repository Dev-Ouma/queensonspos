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
                sans: ['Quicksand', ...defaultTheme.fontFamily.sans],
                display: ['Playfair Display', 'serif'],
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
                navy: {
                    DEFAULT: '#092B60',
                    50: '#E8EDF6',
                    100: '#C6D3E8',
                    600: '#0B3474',
                    700: '#092B60',
                    800: '#07224C',
                    900: '#051938',
                },
                emerald: {
                    DEFAULT: '#079447',
                    600: '#079447',
                    700: '#067A3B',
                },
                gold: {
                    DEFAULT: '#C5A66A',
                    light: '#D9C296',
                    dark: '#A8884C',
                },
            },
        },
    },

    plugins: [forms],
};
