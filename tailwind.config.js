import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#fff1ee',
                    100: '#ffe1db',
                    200: '#ffc3b8',
                    300: '#ff9d8a',
                    400: '#ff6b4d',
                    500: '#f04e2e',
                    600: '#d63a1d',
                    700: '#b32d15',
                    800: '#8f2612',
                    900: '#762212',
                },
            },
        },
    },

    plugins: [forms],
};
