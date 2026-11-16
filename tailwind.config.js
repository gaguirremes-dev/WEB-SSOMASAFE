/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.html', './Libro-reclamaciones/*.php', './js/**/*.js'],
  theme: {
    extend: {
      colors: {
        brand: {
          primary: '#003F8C',
          light: '#7FB4E5',
          dark: '#1E293B',
          gray: '#F5F7FA',
          pale: '#EFF6FF',
        },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        display: ['Manrope', 'sans-serif'],
      },
    },
  },
};
