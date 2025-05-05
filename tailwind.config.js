const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                'primary': '#FF2D20',
                'primary-dark': '#CC2419'
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                'sport': '0 4px 6px -1px rgba(14, 165, 233, 0.3), 0 2px 4px -1px rgba(14, 165, 233, 0.2)',
                'sport-lg': '0 10px 15px -3px rgba(14, 165, 233, 0.3), 0 4px 6px -2px rgba(14, 165, 233, 0.2)',
                'laravel': '0 4px 6px -1px rgba(255, 45, 32, 0.3), 0 2px 4px -1px rgba(255, 45, 32, 0.2)',
                'laravel-lg': '0 10px 15px -3px rgba(255, 45, 32, 0.3), 0 4px 6px -2px rgba(255, 45, 32, 0.2)',
            },
            animation: {
                'pulse-sport': 'pulse-sport 2s infinite',
                'slide-in': 'slide-in-bottom 0.6s cubic-bezier(0.23, 1, 0.32, 1) both',
                'bounce-in': 'bounce-in 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) both',
                'bounce-sport': 'bounce-sport 0.8s cubic-bezier(0.22, 0.61, 0.36, 1) both',
                'shine': 'shine 2s infinite linear',
                'float': 'float 6s ease-in-out infinite',
            },
            keyframes: {
                'pulse-sport': {
                    '0%': { transform: 'scale(1)' },
                    '50%': { transform: 'scale(1.05)', boxShadow: '0 0 15px rgba(14, 165, 233, 0.7)' },
                    '100%': { transform: 'scale(1)' },
                },
                'slide-in-bottom': {
                    '0%': { transform: 'translateY(30px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                'bounce-in': {
                    '0%': { transform: 'scale(0.8)', opacity: '0' },
                    '70%': { transform: 'scale(1.1)', opacity: '1' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                'bounce-sport': {
                    '0%': { transform: 'scale(0.8)', opacity: '0' },
                    '70%': { transform: 'scale(1.1)', opacity: '1' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                'shine': {
                    '0%': { backgroundPosition: '-100px' },
                    '100%': { backgroundPosition: '200px' },
                },
                'float': {
                    '0%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-20px)' },
                    '100%': { transform: 'translateY(0px)' },
                },
            },
            backgroundImage: {
                'sport-pattern': `linear-gradient(135deg, rgba(14, 165, 233, 0.1) 25%, transparent 25%), 
                                  linear-gradient(225deg, rgba(14, 165, 233, 0.1) 25%, transparent 25%), 
                                  linear-gradient(45deg, rgba(14, 165, 233, 0.1) 25%, transparent 25%), 
                                  linear-gradient(315deg, rgba(14, 165, 233, 0.1) 25%, transparent 25%)`,
                'laravel-pattern': `radial-gradient(#FF2D20 0.5px, transparent 0.5px), radial-gradient(#FF2D20 0.5px, #111827 0.5px)`,
                'dots-pattern': `radial-gradient(#FF2D20 0.5px, transparent 0.5px), radial-gradient(#FF2D20 0.5px, #111827 0.5px)`,
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
    ],
};
