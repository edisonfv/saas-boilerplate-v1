<script setup lang="ts">
import AppLogoMark from '@/components/AppLogoMark.vue';
import { brand } from '@/lib/brand';

withDefaults(
    defineProps<{
        tone?: 'default' | 'light';
        caption?: string;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        tone: 'default',
        caption: undefined,
        size: 'md',
    },
);

const markSizes = { sm: 'size-8', md: 'size-9', lg: 'size-12' };
const wordSizes = { sm: 'text-lg', md: 'text-xl', lg: 'text-3xl' };
</script>

<template>
    <span class="inline-flex min-w-0 items-center gap-2.5">
        <AppLogoMark :tone="tone" :class="markSizes[size]" />
        <span class="flex min-w-0 flex-col">
            <span
                :class="[
                    'truncate leading-none tracking-tight',
                    wordSizes[size],
                    tone === 'light'
                        ? 'text-white'
                        : 'text-brand-indigo dark:text-white',
                ]"
            >
                <span class="font-extrabold">{{ brand.wordmark[0] }}</span
                ><span class="font-normal">{{ brand.wordmark[1] }}</span>
            </span>
            <span
                v-if="caption"
                :class="[
                    'mt-1.5 truncate eyebrow !text-[0.625rem]',
                    tone === 'light'
                        ? 'text-ink-300'
                        : 'text-ink-500 dark:text-ink-400',
                ]"
            >
                {{ caption }}
            </span>
        </span>
    </span>
</template>
