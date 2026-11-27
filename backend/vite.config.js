import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Mã nguồn giao diện nằm ở ../frontend/resources, Vite build ra backend/public/build
export default defineConfig({
    plugins: [
        laravel({
            input: ['../frontend/resources/css/app.css', '../frontend/resources/js/app.js'],
            refresh: ['../frontend/resources/views/**', 'app/Livewire/**', 'routes/**'],
        }),
        tailwindcss(),
    ],
    server: { fs: { allow: ['..'] } },
    build: { cssCodeSplit: true, sourcemap: false },
});
