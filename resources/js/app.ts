import { createInertiaApp } from '@inertiajs/vue3';
import { brand } from '@/lib/brand';

const appName = brand.wordmark.join('');

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    progress: {
        color: '#2F80ED',
    },
});
