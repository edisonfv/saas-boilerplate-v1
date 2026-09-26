<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import type { FlashMessage } from '@/lib/flash';
import { resolveFlash } from '@/lib/flash';
import type { IconName } from '@/types/icon';

const page = usePage();
const toast = ref<FlashMessage | null>(null);
let hideTimer: ReturnType<typeof setTimeout> | undefined;

const icons: Record<FlashMessage['tone'], IconName> = {
    success: 'check-circle',
    info: 'information-circle',
    warning: 'exclamation-triangle',
};

const iconClasses: Record<FlashMessage['tone'], string> = {
    success: 'text-emerald-500',
    info: 'text-primary-500',
    warning: 'text-accent-500',
};

function dismiss(): void {
    clearTimeout(hideTimer);
    toast.value = null;
}

function show(flash: Record<string, unknown> | undefined): void {
    const resolved = resolveFlash(flash?.status as string | undefined);

    if (!resolved) {
        return;
    }

    toast.value = resolved;
    clearTimeout(hideTimer);
    hideTimer = setTimeout(dismiss, 5000);
}

let removeFlashListener: (() => void) | undefined;

onMounted(() => {
    // Layouts re-mount on every visit, so the flash that arrived with this
    // page may predate the listener below — read it once on mount too.
    show(page.flash as Record<string, unknown> | undefined);
    // Fires for every response carrying flash data, including repeats of
    // the same message (e.g. saving twice in a row on the same page).
    removeFlashListener = router.on('flash', (event) =>
        show(event.detail.flash as Record<string, unknown>),
    );
});

onBeforeUnmount(() => {
    clearTimeout(hideTimer);
    removeFlashListener?.();
});
</script>

<template>
    <div
        class="pointer-events-none fixed inset-x-4 bottom-4 z-[60] flex justify-center sm:inset-x-auto sm:right-6 sm:bottom-6"
        aria-live="polite"
        role="status"
    >
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="toast"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-ink-800 bg-brand-night px-4 py-3 text-sm text-white shadow-2xl shadow-ink-950/30"
            >
                <Icon
                    :name="icons[toast.tone]"
                    :class="['mt-0.5 size-5 shrink-0', iconClasses[toast.tone]]"
                />
                <p class="flex-1 leading-6 font-medium">{{ toast.message }}</p>
                <button
                    type="button"
                    class="-mr-1 flex size-7 shrink-0 items-center justify-center rounded-md text-ink-300 transition hover:bg-white/10 hover:text-white"
                    aria-label="Cerrar notificación"
                    @click="dismiss"
                >
                    <Icon name="x-mark" class="size-4" />
                </button>
            </div>
        </Transition>
    </div>
</template>
