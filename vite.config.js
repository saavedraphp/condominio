import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs'
import path from 'path'
function findFilesRecursively(dir, extensions = []) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    let results = [];

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            results = results.concat(findFilesRecursively(fullPath, extensions));
        } else if (extensions.some(ext => entry.name.endsWith(ext))) {
            results.push(fullPath.replace(/\\/g, '/')); // para Windows
        }
    }

    return results;
}
const jsFiles = findFilesRecursively('resources/js', ['.js']);
const cssFiles = findFilesRecursively('resources/css', ['.css']);
export default defineConfig({
    plugins: [
        laravel({
            input: [...jsFiles, ...cssFiles],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
