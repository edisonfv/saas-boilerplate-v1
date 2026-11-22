<script setup lang="ts">
import { computed } from 'vue';
import Card from '@/components/Card.vue';
import { ui } from '@/lib/ui';

/**
 * Search snippet of the public website: title and meta description, with
 * a live preview of how it looks on Google. Empty fields fall back to a
 * title/description generated from the site's content.
 */
const props = defineProps<{
    url: string;
    defaults: { title: string; description: string };
    errors: Record<string, string>;
}>();

const title = defineModel<string>('title', { required: true });
const description = defineModel<string>('description', { required: true });

const previewTitle = computed(() => title.value.trim() || props.defaults.title);
const previewDescription = computed(
    () => description.value.trim() || props.defaults.description,
);
const displayUrl = computed(() => props.url.replace(/^https?:\/\//, ''));
</script>

<template>
    <Card title="Posicionamiento en Google (SEO)">
        <div class="space-y-4">
            <div>
                <div class="flex items-baseline justify-between">
                    <label for="seo_title" :class="ui.label"
                        >Título en Google</label
                    >
                    <span
                        :class="[
                            'text-xs tabular-nums',
                            title.length > 60
                                ? 'text-accent-600'
                                : 'text-ink-400',
                        ]"
                        >{{ title.length }}/70</span
                    >
                </div>
                <input
                    id="seo_title"
                    v-model="title"
                    maxlength="70"
                    :placeholder="defaults.title"
                    :class="ui.input"
                />
                <p :class="ui.help">
                    Incluye lo que tus clientes buscan y tu ciudad, p. ej.
                    «Firma electrónica en Quito | Tu empresa». Ideal: hasta 60
                    caracteres.
                </p>
                <p v-if="errors.seo_title" :class="ui.error">
                    {{ errors.seo_title }}
                </p>
            </div>
            <div>
                <div class="flex items-baseline justify-between">
                    <label for="seo_description" :class="ui.label"
                        >Descripción en Google</label
                    >
                    <span
                        :class="[
                            'text-xs tabular-nums',
                            description.length > 155
                                ? 'text-accent-600'
                                : 'text-ink-400',
                        ]"
                        >{{ description.length }}/160</span
                    >
                </div>
                <textarea
                    id="seo_description"
                    v-model="description"
                    rows="3"
                    maxlength="160"
                    :placeholder="defaults.description"
                    :class="ui.input"
                />
                <p v-if="errors.seo_description" :class="ui.error">
                    {{ errors.seo_description }}
                </p>
            </div>

            <div
                class="rounded-lg border border-ink-200 bg-white p-4 dark:border-ink-700 dark:bg-ink-950"
                aria-label="Vista previa en Google"
            >
                <p class="text-xs text-ink-500">Vista previa en Google</p>
                <p
                    class="mt-2 truncate text-xs text-[#202124] dark:text-ink-300"
                >
                    {{ displayUrl }}
                </p>
                <p
                    class="truncate text-lg leading-snug text-[#1a0dab] dark:text-[#8ab4f8]"
                >
                    {{ previewTitle }}
                </p>
                <p
                    class="line-clamp-2 text-sm text-[#4d5156] dark:text-ink-400"
                >
                    {{ previewDescription }}
                </p>
            </div>
        </div>
    </Card>
</template>
