// Salin font lokal & buat subset ikon Tabler (hanya ikon yang dipakai di app/Views).
// Jalankan: npm run build:assets  (otomatis ikut di "npm run build")
import { readFileSync, writeFileSync, mkdirSync, copyFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import subsetFont from 'subset-font';

const ROOT = new URL('..', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');
const FONTS = join(ROOT, 'public/assets/fonts');
const CSS = join(ROOT, 'public/assets/css');
mkdirSync(FONTS, { recursive: true });
mkdirSync(CSS, { recursive: true });

// 1) Font teks
const nm = (p) => join(ROOT, 'node_modules', p);
for (const w of [400, 500, 600, 700, 800]) {
  copyFileSync(nm(`@fontsource/poppins/files/poppins-latin-${w}-normal.woff2`), join(FONTS, `poppins-${w}.woff2`));
}
for (const w of [500, 700]) {
  copyFileSync(nm(`@fontsource/jetbrains-mono/files/jetbrains-mono-latin-${w}-normal.woff2`), join(FONTS, `jetbrains-mono-${w}.woff2`));
}

// 2) Ikon yang dipakai di view
const walk = (dir) => readdirSync(dir).flatMap((f) => {
  const p = join(dir, f);
  return statSync(p).isDirectory() ? walk(p) : p.endsWith('.php') ? [p] : [];
});
const used = new Set();
for (const file of walk(join(ROOT, 'app/Views'))) {
  for (const m of readFileSync(file, 'utf8').matchAll(/\bti-([a-z0-9]+(?:-[a-z0-9]+)*)/g)) used.add(m[1]);
}

const tablerCss = readFileSync(nm('@tabler/icons-webfont/dist/tabler-icons.css'), 'utf8');
const codepoints = {};
for (const m of tablerCss.matchAll(/\.ti-([a-z0-9-]+):before\s*\{\s*content:\s*"\\([0-9a-f]+)"/g)) codepoints[m[1]] = m[2];

const found = [...used].filter((n) => codepoints[n]).sort();
const unknown = [...used].filter((n) => !codepoints[n]);
if (unknown.length) console.warn('Ikon tidak dikenal (cek namanya di tabler.io/icons):', unknown.join(', '));

// Coba buat subset (file kecil). Tabler 3.19 punya tabel GSUB yang ditolak harfbuzz,
// jadi kalau gagal dipakai file font lengkap (tetap lokal, hanya lebih besar ±850 KB).
// Versi kecil di repo dibuat dengan: pyftsubset --layout-features= --drop-tables+=GSUB,GPOS,GDEF
const text = found.map((n) => String.fromCodePoint(parseInt(codepoints[n], 16))).join('');
try {
  const subset = await subsetFont(readFileSync(nm('@tabler/icons-webfont/dist/fonts/tabler-icons.ttf')), text, { targetFormat: 'woff2' });
  writeFileSync(join(FONTS, 'tabler-icons.woff2'), subset);
} catch (e) {
  console.warn('Subset ikon gagal, memakai font Tabler lengkap.');
  copyFileSync(nm('@tabler/icons-webfont/dist/fonts/tabler-icons.woff2'), join(FONTS, 'tabler-icons.woff2'));
}

let css = '/* Tabler Icons 3.19.0 (MIT) — hanya kelas ikon yang dipakai aplikasi. Dibuat oleh tools/build-assets.mjs */\n';
css += "@font-face{font-family:'tabler-icons';font-style:normal;font-weight:400;font-display:block;src:url('../fonts/tabler-icons.woff2') format('woff2');}\n";
css += ".ti{font-family:'tabler-icons'!important;speak:none;font-style:normal;font-weight:normal;font-variant:normal;text-transform:none;line-height:1;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;}\n";
css += found.map((n) => `.ti-${n}:before{content:"\\${codepoints[n]}";}`).join('\n') + '\n';
writeFileSync(join(CSS, 'icons.css'), css);

console.log(`Aset selesai: ${found.length} ikon, font Poppins & JetBrains Mono.`);
