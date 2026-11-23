// Agregué una compilación independiente para aprender daisyUI solo en el login, sin cambiar el CSS compartido.
const shared = require('./tailwind.config.cjs');
module.exports = {
  ...shared,
  // Activé dark: por clase para que el cambio de tema quede limitado al login.
  darkMode: 'class',
  content: ['./src/Views/auth/login.view.php'],
  corePlugins: { preflight: false },
  plugins: [require('daisyui')],
  daisyui: {
    // Prefijé los componentes para distinguirlos de las utilidades Tailwind y de los componentes rrhh existentes.
    prefix: 'dui-', base: false, utils: false, logs: false, darkTheme: false,
    // Definí un tema claro y otro oscuro para que los componentes daisyUI sigan el selector de tema.
    themes: [{ rrhh: {
      'primary': '#2563EB', 'primary-content': '#FFFFFF',
      'secondary': '#1E3A8A', 'secondary-content': '#FFFFFF',
      'accent': '#F59E0B', 'accent-content': '#1E293B',
      'neutral': '#1E293B', 'neutral-content': '#FFFFFF',
      'base-100': '#FFFFFF', 'base-200': '#F1F5F9', 'base-300': '#CBD5E1', 'base-content': '#1E293B',
      'info': '#DBEAFE', 'info-content': '#1E3A8A',
      'success': '#16A34A', 'success-content': '#FFFFFF',
      'warning': '#F59E0B', 'warning-content': '#1E293B',
      'error': '#DC2626', 'error-content': '#FFFFFF',
      '--rounded-box': '1rem', '--rounded-btn': '0.75rem'
    } }, { 'rrhh-dark': {
      'primary': '#60A5FA', 'primary-content': '#0F172A',
      'secondary': '#93C5FD', 'secondary-content': '#0F172A',
      'accent': '#FBBF24', 'accent-content': '#1E293B',
      'neutral': '#0F172A', 'neutral-content': '#E2E8F0',
      'base-100': '#1E293B', 'base-200': '#0F172A', 'base-300': '#334155', 'base-content': '#F1F5F9',
      'info': '#1E3A8A', 'info-content': '#DBEAFE',
      'success': '#166534', 'success-content': '#DCFCE7',
      'warning': '#92400E', 'warning-content': '#FEF3C7',
      'error': '#991B1B', 'error-content': '#FEE2E2',
      '--rounded-box': '1rem', '--rounded-btn': '0.75rem'
    } }]
  }
};
