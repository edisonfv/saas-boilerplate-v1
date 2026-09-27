<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import { preparePhoto } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

type Slide = { id: string; url: string; alt: string };

/**
 * Photos of the public website's main banner. Uploading is immediate
 * (optimized in the browser first); order, alt texts and removals are
 * part of the settings form (v-model) and apply on "Guardar sitio".
 */
const props = defineProps<{
    maxSlides: number;
    errors: Record<string, string>;
}>();

const slides = defineModel<Slide[]>({ required: true });

const fileInput = ref<HTMLInputElement | null>(null);
const preview = ref<string | null>(null);
const preparing = ref(false);
const localError = ref<string | null>(null);
const upload = useForm<{ image: File | null; alt: string }>({
    image: null,
    alt: '',
});

async function onPicked(event: Event) {
    const input = event.target as HTMLInputElement;
    const picked = input.files?.[0];
    input.value = '';
    localError.value = null;

    if (!picked) {
        return;
    }

    preparing.value = true;

    try {
        upload.image = await preparePhoto(picked);
        preview.value = URL.createObjectURL(upload.image);
    } catch (error) {
        localError.value = (error as Error).message;
    } finally {
        preparing.value = false;
    }
}

function submitUpload() {
    upload.post(tenant.signatures.storefront.images.store().url, {
        forceFormData: true,
        preserveScroll: true,
        // Keep unsaved edits of the rest of the settings form.
        preserveState: true,
        onSuccess: () => {
            if (preview.value) {
                URL.revokeObjectURL(preview.value);
            }

            preview.value = null;
            upload.reset();
        },
    });
}

function move(index: number, offset: number) {
    const target = index + offset;

    if (target < 0 || target >= slides.value.length) {
        return;
    }

    const reordered = [...slides.value];
    [reordered[index], reordered[target]] = [
        reordered[target],
        reordered[index],
    ];
    slides.value = reordered;
}

function remove(index: number) {
    slides.value = slides.value.filter((_, position) => position !== index);
}

function updateAlt(index: number, alt: string) {
    const slide = slides.value[index];

    if (slide) {
        slide.alt = alt;
    }
}
</script>

<template>
    <Card title="Banner principal">
        <p class="mb-4 text-sm text-ink-600 dark:text-ink-400">
            Hasta {{ props.maxSlides }} fotos que rotan detrás del titular. Usa
            fotos horizontales (ideal 1920 × 1080). La primera es la que se
            muestra al compartir tu sitio en redes y WhatsApp.
        </p>

        <ol v-if="slides.length" class="space-y-3">
            <li
                v-for="(slide, index) in slides"
                :key="slide.id"
                class="flex flex-col gap-3 rounded-lg border border-ink-200 p-3 sm:flex-row sm:items-center dark:border-ink-800"
            >
                <img
                    :src="slide.url"
                    :alt="slide.alt"
                    class="aspect-video w-full rounded-md object-cover sm:w-40"
                />
                <div class="min-w-0 flex-1">
                    <label :for="`slide-alt-${slide.id}`" :class="ui.label"
                        >Descripción de la foto
                        <span v-if="index === 0" class="text-primary-600"
                            >· principal</span
                        ></label
                    >
                    <input
                        :id="`slide-alt-${slide.id}`"
                        :value="slide.alt"
                        maxlength="150"
                        required
                        placeholder="Ej.: Asesora entregando una firma electrónica en Quito"
                        :class="ui.input"
                        @input="
                            updateAlt(
                                index,
                                ($event.target as HTMLInputElement).value,
                            )
                        "
                    />
                    <p
                        v-if="errors[`hero_slides.${index}.alt`]"
                        :class="ui.error"
                    >
                        {{ errors[`hero_slides.${index}.alt`] }}
                    </p>
                </div>
                <div class="flex items-center gap-1 self-end sm:self-center">
                    <button
                        type="button"
                        class="rounded-md px-2 py-1 text-sm font-semibold text-ink-600 hover:bg-ink-100 disabled:opacity-40 dark:text-ink-300 dark:hover:bg-ink-800"
                        :disabled="index === 0"
                        :aria-label="`Mover la foto ${index + 1} antes`"
                        @click="move(index, -1)"
                    >
                        ↑
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-2 py-1 text-sm font-semibold text-ink-600 hover:bg-ink-100 disabled:opacity-40 dark:text-ink-300 dark:hover:bg-ink-800"
                        :disabled="index === slides.length - 1"
                        :aria-label="`Mover la foto ${index + 1} después`"
                        @click="move(index, 1)"
                    >
                        ↓
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-2 py-1 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                        @click="remove(index)"
                    >
                        Quitar
                    </button>
                </div>
            </li>
        </ol>
        <p
            v-else
            class="rounded-lg bg-ink-50 p-3 text-sm text-ink-500 dark:bg-ink-800/50"
        >
            Sin fotos: el banner usa el degradado de color de la plataforma.
        </p>
        <p v-if="slides.length" :class="[ui.help, 'mt-2']">
            El orden, las descripciones y las fotos quitadas se aplican al
            guardar el sitio.
        </p>

        <!-- Upload a new photo -->
        <div
            v-if="slides.length < maxSlides"
            class="mt-4 rounded-lg bg-ink-50 p-4 dark:bg-ink-800/40"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <button
                    v-if="!preview"
                    type="button"
                    :disabled="preparing"
                    :class="[ui.buttonSecondary, 'sm:w-40']"
                    @click="fileInput?.click()"
                >
                    <Icon name="plus" class="size-4" />
                    {{ preparing ? 'Preparando…' : 'Elegir foto' }}
                </button>
                <img
                    v-else
                    :src="preview"
                    alt="Vista previa de la foto nueva"
                    class="aspect-video w-full rounded-md object-cover sm:w-40"
                />
                <div v-if="preview" class="min-w-0 flex-1 space-y-2">
                    <label for="new-slide-alt" :class="ui.label"
                        >Describe la foto (para Google y lectores de
                        pantalla)</label
                    >
                    <input
                        id="new-slide-alt"
                        v-model="upload.alt"
                        maxlength="150"
                        placeholder="Ej.: Cliente firmando un contrato desde su celular"
                        :class="ui.input"
                    />
                    <div class="flex gap-2">
                        <button
                            type="button"
                            :disabled="upload.processing || !upload.alt.trim()"
                            :class="ui.buttonPrimary"
                            @click="submitUpload"
                        >
                            {{ upload.processing ? 'Subiendo…' : 'Subir foto' }}
                        </button>
                        <button
                            type="button"
                            :class="ui.buttonSecondary"
                            @click="
                                preview = null;
                                upload.reset();
                            "
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                aria-label="Elegir foto del banner"
                @change="onPicked"
            />
            <p
                v-if="localError || upload.errors.image || upload.errors.alt"
                :class="[ui.error, 'mt-2']"
            >
                {{ localError ?? upload.errors.image ?? upload.errors.alt }}
            </p>
        </div>
    </Card>
</template>
