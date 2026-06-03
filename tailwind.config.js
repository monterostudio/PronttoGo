/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./publica/**/*.php",
    "./administrador/**/*.php",
    "./includes/**/*.php",
    "./core/**/*.php",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        primary: "var(--color-primary)",
        "primary-hover": "var(--color-primary-hover)",
        soft: "var(--color-soft)",
        "border-soft": "var(--color-border-soft)",
      },
    },
  },
  plugins: [],
}
