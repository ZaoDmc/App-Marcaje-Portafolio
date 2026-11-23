// Agregué compilación de las clases PHP/JS y los colores que ya usaba el portal.
// Fijé 3.4.17 para conservar la sintaxis de las vistas existentes durante esta migración.
module.exports = {
  content: ['./src/**/*.php', './public/assets/js/**/*.js'],
  // Habilité la variante dark por clase para activarla solo en la pantalla de marcaje.
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: {
          sidebar: '#0b2a5b', 'sidebar-deep': '#06204a', active: '#145deb',
          blue: '#2563eb', 'blue-soft': '#eaf1ff', cyan: '#06b6d4',
          teal: '#0f766e', 'teal-dark': '#115e59', green: '#16a34a',
          yellow: '#f59e0b', red: '#e30613', ink: '#0f172a',
          muted: '#64748b', line: '#e0e7f0', bg: '#f3f6fa'
        }
      }
    }
  },
  // Incorporé daisyUI con clases namespaced solo para avisos de éxito, evitando colisiones con el UI existente.
  plugins: [require('daisyui')],
  daisyui: {
    // Desactivé reset/utilidades de daisyUI y limité sus componentes con el prefijo dui-.
    prefix: 'dui-', base: false, utils: false, logs: false,
    // Habilité solo los seis temas ofrecidos al practicante para mantener liviana la hoja compilada.
    themes: ['light', 'dark', 'synthwave', 'aqua', 'coffee', 'black']
  }
};
