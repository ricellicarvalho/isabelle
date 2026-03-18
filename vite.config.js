import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        // O plugin do Tailwind deve vir PREFERENCIALMENTE antes do Laravel
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        // AJUSTES PARA DOCKER:
        host: '0.0.0.0', // Permite conexões externas ao container
        port: 5173,      // Porta padrão do Vite
        strictPort: true,
        hmr: {
            host: 'localhost', // Como o seu navegador acessa o Vite
        },
        watch: {
            usePolling: true, // Necessário em alguns ambientes Docker para detectar mudanças de arquivo
            ignored: ['**/storage/framework/views/**'],
        },
    },
});