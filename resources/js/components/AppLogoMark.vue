<script setup lang="ts">
/**
 * The "open hexagon" isotype: a hexagon left open at the top-right, where
 * its right edge becomes the stem of an "i" dotted with the amber spark.
 * `tone="light"` is for dark surfaces (sidebar, brand panels).
 */
withDefaults(
    defineProps<{
        tone?: 'default' | 'light';
    }>(),
    {
        tone: 'default',
    },
);
</script>

<template>
    <svg
        viewBox="0 0 48 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
        class="shrink-0"
    >
        <path
            d="M41.3 21.5V34L24 44 6.7 34V14L24 4l7.4 4.3"
            :class="
                tone === 'light'
                    ? 'stroke-white'
                    : 'stroke-brand-indigo dark:stroke-white'
            "
            stroke-width="5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M17.6 30.5V17.5l12.8 13V17.5"
            :class="
                tone === 'light'
                    ? 'stroke-primary-400'
                    : 'stroke-brand-blue dark:stroke-primary-400'
            "
            stroke-width="4.6"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <circle cx="40.6" cy="12.6" r="3.4" class="fill-brand-amber" />
    </svg>
</template>
