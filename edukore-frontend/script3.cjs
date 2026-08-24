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
    
    content = content.replace(/\bborder-slate-800\b/g, 'border-slate-200 dark:border-slate-800');
    // For bg-slate-800/30, bg-slate-800/40, etc.
    content = content.replace(/\bbg-slate-800\/(30|40|50)\b/g, 'bg-slate-50 dark:bg-slate-800/$1');
    content = content.replace(/\bhover:bg-slate-800\/(30|40|50)\b/g, 'hover:bg-slate-50 dark:hover:bg-slate-800/$1');
    // Just in case double dark mode replacement occurred
    content = content.replace(/dark:dark:/g, 'dark:');
    content = content.replace(/border-slate-200 dark:border-slate-200 dark:border-slate-800/g, 'border-slate-200 dark:border-slate-800');
    
    fs.writeFileSync(file, content, 'utf8');
  });
});
