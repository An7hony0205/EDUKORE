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

const dirs = [
  'c:\\Users\\Anthony\\Desktop\\EDUKORE\\edukore-frontend\\src\\views',
  'c:\\Users\\Anthony\\Desktop\\EDUKORE\\edukore-frontend\\src\\layouts',
  'c:\\Users\\Anthony\\Desktop\\EDUKORE\\edukore-frontend\\src\\components' // Check components just in case breadcrumbs or global search are here
];

let allFiles = [];

const processFiles = () => {
  allFiles.forEach(file => {
    if (file.includes('LoginView.vue') || file.includes('SettingsView.vue')) return; // handled previously, although we can run it safely. Let's just exclude LoginView and SettingsView to be safe if they already look perfect.

    let content = fs.readFileSync(file, 'utf8');

    // 1. Primary Buttons (bg-primary-600, bg-indigo-600, bg-blue-600)
    // Some buttons might already have text-white or text-slate-900 dark:text-white. 
    // We should replace the whole set of color classes.
    // E.g., `bg-primary-600 hover:bg-primary-500 text-slate-900 dark:text-white` -> `bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200`
    content = content.replace(/\bbg-(primary|indigo|blue)-600\s+hover:bg-\1-50\w*\s+text-[a-zA-Z0-9-:]+\s+(dark:text-[a-zA-Z0-9-:]+\s+)?/g, 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 ');
    content = content.replace(/\bbg-(primary|indigo|blue)-600\s+hover:bg-\1-50\w*\s+/g, 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 ');
    // Handle standalone
    content = content.replace(/\bbg-(primary|indigo|blue)-600\b(?!\/)/g, 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200');

    // Remove remnants of text-slate-900 dark:text-white if they were next to bg-primary-600 that was replaced
    content = content.replace(/bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200\s+text-slate-900 dark:text-white/g, 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200');

    // Remove duplicates
    content = content.replace(/text-white\s+text-white/g, 'text-white');

    // 2. Inputs
    // Replace typical input classes
    // They usually have: `w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary-500`
    // User wants: `w-full bg-white border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500`
    // Let's target `<input ... class="...">` and `<select ... class="...">`
    // Because regex on HTML is tricky, let's replace fragments often found on inputs
    content = content.replace(/bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded([^\s]*)\s+([^\s]*\s+)?([^\s]*\s+)?text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary-500/g, 'bg-white border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500 rounded$1 $2$3text-sm');
    
    // Some are `focus:border-primary-500` or `focus:ring-primary-500`
    content = content.replace(/\bfocus:border-primary-500\b/g, 'focus:border-slate-500 dark:focus:border-slate-400');
    content = content.replace(/\bfocus:ring-primary-500\b/g, 'focus:ring-slate-500 dark:focus:ring-slate-400');
    content = content.replace(/\btext-primary-500\b/g, 'text-slate-900 dark:text-white');

    fs.writeFileSync(file, content, 'utf8');
  });
  console.log('Processed buttons and inputs in ' + allFiles.length + ' files');
};

let dirsProcessed = 0;
dirs.forEach(dir => {
  walk(dir, (err, files) => {
    if (!err) allFiles = allFiles.concat(files);
    dirsProcessed++;
    if (dirsProcessed === dirs.length) {
      processFiles();
    }
  });
});
