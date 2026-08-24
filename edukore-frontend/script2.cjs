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
    if (file.includes('AdminDashboard.vue') || file.includes('SettingsView.vue') || file.includes('LoginView.vue')) return;

    let content = fs.readFileSync(file, 'utf8');
    
    // Additional fixes
    content = content.replace(/\bbg-brand-muted\b/g, 'bg-slate-50 dark:bg-slate-800/50');
    content = content.replace(/\bbg-\[\#1e293b\]\b/g, 'bg-white dark:bg-slate-900'); // Some tailwind arbitrary colors?
    // User said: "Cambia los fondos oscuros quemados (bg-brand-surface, bg-slate-800, bg-[#... oscuro]) por contenedores adaptables"
    content = content.replace(/\bbg-\[\#[0-9a-fA-F]{3,6}\]\b/g, 'bg-white dark:bg-slate-900');
    
    fs.writeFileSync(file, content, 'utf8');
  });
  console.log('Processed additional fixes');
});
