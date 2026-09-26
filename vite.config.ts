import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, loadEnv } from 'vite';

/**
 * Origins allowed to load assets from the Vite dev server.
 *
 * laravel-vite-plugin only allows the exact APP_URL, which breaks two cases
 * in this app: browsing over https while APP_URL says http (or vice versa),
 * and tenant subdomains (acme.APP_HOST). Both then fail CORS silently, and
 * because pages are server-rendered they still *look* fine, but nothing
 * interactive (menus, theme toggle, forms) works.
 */
function devServerOrigins(appUrl: string | undefined): (string | RegExp)[] {
    const origins: (string | RegExp)[] = [
        /^https?:\/\/(?:(?:[^:]+\.)?localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/,
        /^https?:\/\/.*\.test(:\d+)?$/,
    ];

    if (appUrl) {
        const host = new URL(appUrl).hostname.replace(/\./g, '\\.');

        origins.push(new RegExp(`^https?://(?:[^/]+\\.)?${host}(:\\d+)?$`));
    }

    return origins;
}

export default defineConfig(({ mode }) => ({
    server: {
        cors: {
            origin: devServerOrigins(loadEnv(mode, process.cwd(), '').APP_URL),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Manrope', {
                    weights: [400, 500, 600, 700, 800],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
}));
