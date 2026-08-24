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
  'c:\\Users\\Anthony\\Desktop\\EDUKORE\\edukore-frontend\\src\\components'
];

let allFiles = [];

const processFiles = () => {
  allFiles.forEach(file => {
    if (file.includes('LoginView.vue') || file.includes('SettingsView.vue')) return;

    let content = fs.readFileSync(file, 'utf8');

    // Breadcrumbs text: text-slate-300 to text-slate-500 dark:text-slate-400
    // Actually, any text-slate-300 that isn't handled. We already did this, but let's make sure.
    // Replace text-slate-300 (that isn't preceded by dark:)
    content = content.replace(/(?<!dark:)text-slate-300\b/g, 'text-slate-500 dark:text-slate-400');
    // We also want to fix the inputs.
    // Existing input classes are like:
    // class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 dark:focus:border-slate-400"
    // Let's replace the common background/border/text parts of these inputs to exactly what the user wants.
    // The user wants:
    // w-full bg-white border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500
    // Let's replace `bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700` with `bg-white border-slate-300 dark:bg-slate-800 dark:border-slate-700`
    content = content.replace(/\bbg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700\b/g, 'bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700');
    content = content.replace(/\bbg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800\b/g, 'bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700');
    
    // Replace focus ring
    content = content.replace(/\bfocus:border-slate-500 dark:focus:border-slate-400\b/g, 'focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500');

    // Breadcrumbs specific components? Let's check Breadcrumbs.vue
    if (file.includes('Breadcrumbs.vue')) {
      content = content.replace(/\btext-white\b/g, 'text-slate-900 dark:text-white');
    }

    fs.writeFileSync(file, content, 'utf8');
  });
  console.log('Processed second pass');
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
