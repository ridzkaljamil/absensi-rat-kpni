// Konfigurasi Tailwind untuk build CSS aplikasi (npm run build:css).
// Hasil build: public/assets/css/app.css — ikut di-commit, jadi server
// tidak butuh Node.js maupun internet.
module.exports = {
  content: ['./app/Views/**/*.php'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Poppins', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'Consolas', 'monospace'],
      },
    },
  },
  plugins: [],
};
