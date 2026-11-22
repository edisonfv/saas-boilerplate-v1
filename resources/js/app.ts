import { createInertiaApp } from '@inertiajs/vue3';
import { brand } from '@/lib/brand';

const appName = brand.wordmark.join('');

createInertiaApp({
    // Tenant public websites pass complete titles ("Page | Company", see
    // App\Services\Signatures\StorefrontSeo): they must not carry the
    // platform's name. Everything else gets the "· platform" suffix.
    title: (title) =>
        title
            ? title.includes(' | ')
                ? title
                : `${title} · ${appName}`
            : appName,
    progress: {
        color: '#2F80ED',
    },
});
