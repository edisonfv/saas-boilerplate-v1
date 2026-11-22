<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Main banner of a tenant's public website: its photos crossfade behind
 * the content, under a dark overlay that keeps the text readable. The
 * first photo loads with high priority (it's the page's LCP element);
 * the rest load lazily. Rotation pauses on hover/focus and is off for
 * users who prefer reduced motion. Without photos, a brand gradient.
 */
const props = defineProps<{
    slides: { id: string; url: string; alt: string }[];
}>();

const IntervalMs = 6000;
const current = ref(0);
const paused = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;

function go(index: number) {
    current.value = (index + props.slides.length) % props.slides.length;
}

onMounted(() => {
    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (props.slides.length > 1 && !reducedMotion) {
        timer = setInterval(() => {
            if (!paused.value) {
                go(current.value + 1);
            }
        }, IntervalMs);
    }
});

onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <section
        :class="[
            'relative isolate overflow-hidden text-white',
            slides.length
                ? 'bg-ink-950'
                : 'bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800',
        ]"
        aria-roledescription="carrusel"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        @focusin="paused = true"
        @focusout="paused = false"
    >
        <template v-if="slides.length">
            <img
                v-for="(slide, index) in slides"
                :key="slide.id"
                :src="slide.url"
                :alt="slide.alt"
                :loading="index === 0 ? 'eager' : 'lazy'"
                :fetchpriority="index === 0 ? 'high' : 'low'"
                decoding="async"
                :aria-hidden="index !== current"
                :class="[
                    'absolute inset-0 -z-20 size-full object-cover transition-opacity duration-1000',
                    index === current ? 'opacity-100' : 'opacity-0',
                ]"
            />
            <div
                class="absolute inset-0 -z-10 bg-gradient-to-r from-ink-950/85 via-ink-950/60 to-ink-950/20"
            />
        </template>

        <slot />

        <div
            v-if="slides.length > 1"
            class="absolute inset-x-0 bottom-4 flex justify-center gap-2"
        >
            <button
                v-for="(slide, index) in slides"
                :key="slide.id"
                type="button"
                :aria-label="`Ver foto ${index + 1} de ${slides.length}`"
                :aria-current="index === current"
                :class="[
                    'h-2 rounded-full transition-all',
                    index === current
                        ? 'w-6 bg-white'
                        : 'w-2 bg-white/50 hover:bg-white/80',
                ]"
                @click="go(index)"
            />
        </div>
    </section>
</template>
