/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eef8ff',
          100: '#d8eeff',
          200: '#b9e0ff',
          300: '#89ccff',
          400: '#52adff',
          500: '#2b8aff',
          600: '#156bf5',
          700: '#0f54e1',
          800: '#1344b6',
          900: '#153c8f',
          950: '#112657',
        },
        medical: {
          emerald: '#059669',
          teal: '#0d9488',
          cyan: '#0891b2',
          red: '#e11d48',
          amber: '#d97706',
          dark: '#0f172a'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
