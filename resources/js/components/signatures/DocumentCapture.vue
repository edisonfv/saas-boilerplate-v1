<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { preparePhoto } from '@/lib/signatures';
import type { DocumentKindOption } from '@/lib/signatures';
import { ui } from '@/lib/ui';

/**
 * One document slot of a signature application, built for phones: photo
 * slots offer "Tomar foto" (opens the camera directly, front camera for
 * the selfie) and "Elegir de la galería"; PDF slots a file picker. Photos
 * are previewed and normalized (scaled down, JPEG) before being emitted.
 */
const props = defineProps<{
    document: DocumentKindOption;
    required: boolean;
    alreadyUploaded: boolean;
    file: File | null;
    error?: string;
}>();

const emit = defineEmits<{
    select: [file: File | null];
}>();

const hints: Record<string, string> = {
    IdFront:
        'Cédula completa sobre una superficie plana, con buena luz y sin reflejos.',
    IdBack: 'El reverso completo, con el código dactilar legible.',
    Selfie: 'Sostén tu cédula junto a tu rostro, sin gafas ni gorra.',
};

const processing = ref(false);
const localError = ref<string | null>(null);
const previewUrl = ref<string | null>(null);
const cameraInput = ref<HTMLInputElement | null>(null);
const galleryInput = ref<HTMLInputElement | null>(null);

const galleryAccept = computed(() =>
    [
        props.document.accepts_images ? 'image/*' : null,
        props.document.accepts_pdf ? 'application/pdf,.pdf' : null,
    ]
        .filter(Boolean)
        .join(','),
);
const isDone = computed(() => Boolean(props.file) || props.alreadyUploaded);

function setPreview(file: File | null) {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value =
        file && file.type.startsWith('image/')
            ? URL.createObjectURL(file)
            : null;
}

async function onPicked(event: Event) {
    const input = event.target as HTMLInputElement;
    const picked = input.files?.[0];
    input.value = '';

    if (!picked) {
        return;
    }

    localError.value = null;

    if (
        picked.type === 'application/pdf' ||
        !picked.type.startsWith('image/')
    ) {
        if (!props.document.accepts_pdf) {
            localError.value = 'Este documento debe ser una foto (JPG o PNG).';

            return;
        }

        setPreview(null);
        emit('select', picked);

        return;
    }

    processing.value = true;

    try {
        const photo = await preparePhoto(picked);
        setPreview(photo);
        emit('select', photo);
    } catch (error) {
        localError.value = (error as Error).message;
    } finally {
        processing.value = false;
    }
}

function clear() {
    setPreview(null);
    emit('select', null);
}

onBeforeUnmount(() => setPreview(null));
</script>

<template>
    <div
        :class="[
            'rounded-xl border p-4 transition',
            isDone
                ? 'border-emerald-300 bg-emerald-50/50 dark:border-emerald-500/40 dark:bg-emerald-500/5'
                : 'border-ink-200 dark:border-ink-700',
        ]"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="font-semibold text-ink-950 dark:text-white">
                    {{ document.label }}
                    <span
                        v-if="!required"
                        class="text-sm font-normal text-ink-400"
                        >(opcional)</span
                    >
                </p>
                <p
                    v-if="hints[document.kind]"
                    class="mt-0.5 text-xs text-ink-500 dark:text-ink-400"
                >
                    {{ hints[document.kind] }}
                </p>
            </div>
            <Icon
                v-if="isDone"
                name="check-circle"
                class="size-5 shrink-0 text-emerald-600"
            />
        </div>

        <div v-if="previewUrl" class="mt-3 flex items-center gap-3">
            <img
                :src="previewUrl"
                :alt="`Vista previa: ${document.label}`"
                class="h-20 w-28 rounded-lg object-cover ring-1 ring-ink-200 dark:ring-ink-700"
            />
            <button type="button" :class="ui.buttonDanger" @click="clear">
                Quitar
            </button>
        </div>
        <div
            v-else-if="file"
            class="mt-3 flex items-center justify-between gap-3 text-sm text-ink-700 dark:text-ink-200"
        >
            <span class="flex min-w-0 items-center gap-2">
                <Icon name="paperclip" class="size-4 shrink-0" />
                <span class="truncate">{{ file.name }}</span>
            </span>
            <button type="button" :class="ui.buttonDanger" @click="clear">
                Quitar
            </button>
        </div>
        <p v-else-if="alreadyUploaded" class="mt-2 text-xs text-ink-500">
            Ya cargado. Sube otro archivo solo para reemplazarlo.
        </p>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <button
                v-if="document.capture"
                type="button"
                :disabled="processing"
                :class="[ui.buttonPrimary, 'h-11 w-full']"
                @click="cameraInput?.click()"
            >
                <Icon name="camera" class="size-4.5" />
                {{
                    file || alreadyUploaded
                        ? 'Tomar otra foto'
                        : document.capture === 'user'
                          ? 'Tomar selfie'
                          : 'Tomar foto'
                }}
            </button>
            <button
                type="button"
                :disabled="processing"
                :class="[
                    document.capture ? ui.buttonSecondary : ui.buttonPrimary,
                    'h-11 w-full',
                    document.capture ? '' : 'sm:col-span-2',
                ]"
                @click="galleryInput?.click()"
            >
                <Icon name="paperclip" class="size-4.5" />
                {{
                    document.accepts_images
                        ? 'Elegir de la galería'
                        : 'Subir PDF'
                }}
            </button>
        </div>

        <!-- The camera input opens the camera straight away on phones. -->
        <input
            v-if="document.capture"
            ref="cameraInput"
            type="file"
            accept="image/*"
            :capture="document.capture"
            class="hidden"
            :aria-label="`Tomar foto: ${document.label}`"
            @change="onPicked"
        />
        <input
            :id="`document-${document.kind}`"
            ref="galleryInput"
            type="file"
            :accept="galleryAccept"
            class="hidden"
            :aria-label="`Elegir archivo: ${document.label}`"
            @change="onPicked"
        />

        <p v-if="processing" class="mt-2 text-xs text-ink-500">
            Preparando la imagen…
        </p>
        <p v-if="localError || error" :class="[ui.error, 'mt-2']">
            {{ localError ?? error }}
        </p>
    </div>
</template>
