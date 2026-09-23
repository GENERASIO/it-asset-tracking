import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                    50: '#deebff',
                    100: '#b3d4ff',
                    200: '#85b8ff',
                    300: '#579dff',
                    400: '#388bff',
                    500: '#0c66e4',
                    600: '#0052cc',
                    700: '#0747a6',
                    800: '#08316c',
                    900: '#051b3d',
                },
            },
        },
    },

    plugins: [forms],
};
