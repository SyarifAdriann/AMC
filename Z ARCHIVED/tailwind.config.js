/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/views/**/*.php",
    "./assets/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        'amc-blue': '#3F72AF',
        'amc-dark-blue': '#112D4E',
        'amc-light': '#DBE2EF',
        'amc-bg': '#F9F7F7'
      },
      fontFamily: {
        'sans': ['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'Arial', 'sans-serif']
      },
      screens: {
        'xs': '475px',
      }
    }
  },
  plugins: [],
}
