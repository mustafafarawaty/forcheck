import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devServerHost = env.VITE_DEV_SERVER_HOST || '127.0.0.1';
    const devServerPort = Number(env.VITE_DEV_SERVER_PORT || 5173);
    const hmrHost = env.VITE_HMR_HOST || devServerHost;
    const hmrProtocol = env.VITE_HMR_PROTOCOL || 'ws';
    const devServerOrigin = env.VITE_ASSET_DEV_URL || `http://${hmrHost}:${devServerPort}`;

    return {
        plugins: [
            vue(),
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: devServerHost,
            port: devServerPort,
            strictPort: true,
            cors: true,
            origin: devServerOrigin,
            hmr: {
                host: hmrHost,
                protocol: hmrProtocol,
                port: devServerPort,
                clientPort: devServerPort,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
