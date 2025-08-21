/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#e41e5b',
          50: '#fdf2f5',
          100: '#fce7ed',
          200: '#f9d0dc',
          300: '#f4a8bf',
          400: '#ed7599',
          500: '#e41e5b',
          600: '#d31650',
          700: '#b20f42',
          800: '#940f3c',
          900: '#7d1037',
        },
        accent: {
          DEFAULT: '#9a0864',
          50: '#fdf2f8',
          100: '#fce7f3',
          200: '#fbcfe8',
          300: '#f8a2d3',
          400: '#f472b6',
          500: '#9a0864',
          600: '#8b0759',
          700: '#7c064e',
          800: '#6d0543',
          900: '#5e0438',
        },
        dark: {
          DEFAULT: '#2c2c2c',
          50: '#f7f7f7',
          100: '#e3e3e3',
          200: '#c8c8c8',
          300: '#a4a4a4',
          400: '#818181',
          500: '#666666',
          600: '#515151',
          700: '#434343',
          800: '#383838',
          900: '#2c2c2c',
        },
        neutral: {
          DEFAULT: '#746354',
          50: '#f9f8f6',
          100: '#f0ede8',
          200: '#e0d9d0',
          300: '#cbbfb0',
          400: '#b3a08c',
          500: '#9d8670',
          600: '#8a7562',
          700: '#746354',
          800: '#5f5248',
          900: '#4e443c',
        },
        highlight: {
          DEFAULT: '#a67c00',
          50: '#fffbeb',
          100: '#fef3c7',
          200: '#fde68a',
          300: '#fcd34d',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
          800: '#92400e',
          900: '#a67c00',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'bounce-in': 'bounceIn 0.6s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        bounceIn: {
          '0%': { transform: 'scale(0.3)', opacity: '0' },
          '50%': { transform: 'scale(1.05)' },
          '70%': { transform: 'scale(0.9)' },
          '100%': { transform: 'scale(1)', opacity: '1' },
        },
      },
      screens: {
        'xs': '475px',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
