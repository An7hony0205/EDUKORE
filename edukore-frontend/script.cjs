const fs = require('fs');
const path = require('path');

const walk = (dir, done) => {
  let results = [];
  fs.readdir(dir, (err, list) => {
    if (err) return done(err);
    let pending = list.length;
    if (!pending) return done(null, results);
    list.forEach(file => {
      file = path.resolve(dir, file);
      fs.stat(file, (err, stat) => {
        if (stat && stat.isDirectory()) {
          walk(file, (err, res) => {
            results = results.concat(res);
            if (!--pending) done(null, results);
          });
        } else {
          if (file.endsWith('.vue')) {
            results.push(file);
          }
          if (!--pending) done(null, results);
        }
      });
    });
  });
};

walk('c:\\Users\\Anthony\\Desktop\\EDUKORE\\edukore-frontend\\src\\views', (err, files) => {
  if (err) throw err;
  
  files.forEach(file => {
    // Skip AdminDashboard and SettingsView and LoginView because we already updated them
    if (file.includes('AdminDashboard.vue') || file.includes('SettingsView.vue') || file.includes('LoginView.vue')) return;

    let content = fs.readFileSync(file, 'utf8');
    
    // Títulos y Textos principales
    content = content.replace(/\btext-white\b/g, 'text-slate-900 dark:text-white');
    content = content.replace(/\btext-slate-400\b/g, 'text-slate-500 dark:text-slate-400');
    
    // Contenedores de Tablas y Tarjetas
    content = content.replace(/\bbg-brand-surface\b/g, 'bg-white dark:bg-slate-900');
    content = content.replace(/\bborder-brand-border\b/g, 'border-slate-200 dark:border-slate-800');
    content = content.replace(/\bbg-slate-800\b(?!\/)/g, 'bg-white dark:bg-slate-900');
    content = content.replace(/\bborder-slate-700\b/g, 'border-slate-200 dark:border-slate-700');
    
    // Cabeceras y Filas de Tablas
    content = content.replace(/\bbg-slate-800\/50\b/g, 'bg-slate-50 dark:bg-slate-800/50');
    content = content.replace(/\btext-slate-300\b/g, 'text-slate-700 dark:text-slate-300');
    content = content.replace(/\bhover:bg-slate-800\/30\b/g, 'hover:bg-slate-50 dark:hover:bg-slate-800/30');
    content = content.replace(/\bhover:bg-white\/5\b/g, 'hover:bg-slate-50 dark:hover:bg-white/5');

    fs.writeFileSync(file, content, 'utf8');
  });
  console.log('Processed ' + files.length + ' files');
});
