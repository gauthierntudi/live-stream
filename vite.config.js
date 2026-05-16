import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, loadEnv } from 'vite';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL || 'https://live-stream.web';
    let appHost = 'live-stream.web';
    try {
        appHost = new URL(appUrl).hostname;
    } catch {
        /* garde la valeur par défaut */
    }

    /* Nom du site Herd/Valet pour le certificat TLS (souvent = nom du dossier). */
    const tlsHost =
        env.VITE_TLS_HOST || `${path.basename(process.cwd())}.web`;

    return {
        /*
         * amazon-ivs-player importe le module Node `events`. Sans alias, Vite injecte
         * un stub « browser-external » et EventEmitter n’est pas un constructeur.
         */
        resolve: {
            alias: {
                events: path.resolve(projectRoot, 'node_modules/events/events.js'),
            },
        },
        optimizeDeps: {
            include: ['events', 'amazon-ivs-player'],
        },
        plugins: [
            laravel({
                detectTls: tlsHost,
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    'resources/js/hero-carousel.js',
                    'resources/js/hero-masonry.js',
                    'resources/js/home-hero-animations.js',
                    'resources/js/page-loader.js',
                    'resources/js/stream-icons.js',
                    'resources/js/brand-typewriter.js',
                    'resources/js/live-player.js',
                    'resources/js/live-status-poll-boot.js',
                    'resources/js/admin-live-dashboard.js',
                    'resources/js/admin-donation-modal.js',
                ],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            port: env.VITE_PORT ? Number(env.VITE_PORT) : 5173,
            strictPort: true,
            hmr: {
                host: appHost,
                protocol: 'wss',
                clientPort: env.VITE_PORT ? Number(env.VITE_PORT) : 5173,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
