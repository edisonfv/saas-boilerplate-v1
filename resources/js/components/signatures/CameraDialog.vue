<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { ui } from '@/lib/ui';

/**
 * In-page camera for computers, where browsers ignore the `capture`
 * attribute of file inputs (phones open their native camera app instead,
 * see DocumentCapture). Streams the webcam with getUserMedia, lets the
 * user capture, retake and confirm, and emits the photo as a JPEG File.
 */
const props = defineProps<{
    title: string;
    facingMode: 'user' | 'environment';
}>();

const emit = defineEmits<{
    capture: [file: File];
    close: [];
    /** The camera can't be used: fall back to choosing a file. */
    fallback: [];
}>();

const video = ref<HTMLVideoElement | null>(null);
const stream = ref<MediaStream | null>(null);
const snapshot = ref<{ url: string; blob: Blob } | null>(null);
const error = ref<string | null>(null);
const starting = ref(true);
const cameras = ref<MediaDeviceInfo[]>([]);
const cameraIndex = ref(0);

function stop() {
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
}

async function start(deviceId?: string) {
    stop();
    starting.value = true;
    error.value = null;

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: deviceId
                ? { deviceId: { exact: deviceId } }
                : {
                      facingMode: props.facingMode,
                      width: { ideal: 1920 },
                      height: { ideal: 1080 },
                  },
        });

        if (video.value) {
            video.value.srcObject = stream.value;
            await video.value.play();
        }

        cameras.value = (
            await navigator.mediaDevices.enumerateDevices()
        ).filter((device) => device.kind === 'videoinput');
    } catch (exception) {
        const name = (exception as DOMException).name;

        error.value =
            name === 'NotAllowedError'
                ? 'No diste permiso para usar la cámara. Actívalo en el candado de la barra de direcciones o elige un archivo.'
                : name === 'NotFoundError' || name === 'OverconstrainedError'
                  ? 'No encontramos una cámara en este equipo. Elige un archivo en su lugar.'
                  : 'No pudimos abrir la cámara. Cierra otras aplicaciones que la estén usando o elige un archivo.';
    } finally {
        starting.value = false;
    }
}

function switchCamera() {
    cameraIndex.value = (cameraIndex.value + 1) % cameras.value.length;
    start(cameras.value[cameraIndex.value]?.deviceId);
}

async function capture() {
    const source = video.value;

    if (!source || !source.videoWidth) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = source.videoWidth;
    canvas.height = source.videoHeight;
    canvas.getContext('2d')?.drawImage(source, 0, 0);

    const blob = await new Promise<Blob | null>((resolve) =>
        canvas.toBlob(resolve, 'image/jpeg', 0.9),
    );

    if (blob) {
        snapshot.value = { url: URL.createObjectURL(blob), blob };
    }
}

function retake() {
    if (snapshot.value) {
        URL.revokeObjectURL(snapshot.value.url);
    }

    snapshot.value = null;
}

function confirm() {
    if (!snapshot.value) {
        return;
    }

    emit(
        'capture',
        new File([snapshot.value.blob], `foto-${Date.now()}.jpg`, {
            type: 'image/jpeg',
        }),
    );
    retake();
    stop();
}

function close() {
    retake();
    stop();
    emit('close');
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        close();
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    start();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    retake();
    stop();
});
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/80 p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
    >
        <div
            class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-ink-900"
        >
            <div
                class="flex items-center justify-between border-b border-ink-200 px-5 py-3 dark:border-ink-800"
            >
                <h2 class="font-bold text-ink-950 dark:text-white">
                    {{ title }}
                </h2>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800"
                    aria-label="Cerrar"
                    @click="close"
                >
                    <Icon name="x-mark" class="size-5" />
                </button>
            </div>

            <div class="relative aspect-video bg-ink-950">
                <video
                    v-show="!snapshot && !error"
                    ref="video"
                    playsinline
                    muted
                    :class="[
                        'size-full object-contain',
                        facingMode === 'user' ? '-scale-x-100' : '',
                    ]"
                />
                <img
                    v-if="snapshot"
                    :src="snapshot.url"
                    alt="Foto capturada"
                    class="size-full object-contain"
                />
                <p
                    v-if="starting && !error"
                    class="absolute inset-0 flex items-center justify-center text-sm text-ink-300"
                >
                    Abriendo la cámara…
                </p>
                <div
                    v-if="error"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center"
                >
                    <Icon name="camera" class="size-10 text-ink-400" />
                    <p class="max-w-md text-sm text-ink-200">{{ error }}</p>
                </div>
            </div>

            <div
                class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
            >
                <button
                    v-if="cameras.length > 1 && !snapshot && !error"
                    type="button"
                    :class="ui.buttonSecondary"
                    @click="switchCamera"
                >
                    Cambiar cámara
                </button>
                <span v-else />

                <div class="flex flex-wrap gap-3">
                    <template v-if="error">
                        <button
                            type="button"
                            :class="ui.buttonPrimary"
                            @click="emit('fallback')"
                        >
                            Elegir archivo
                        </button>
                    </template>
                    <template v-else-if="snapshot">
                        <button
                            type="button"
                            :class="ui.buttonSecondary"
                            @click="retake"
                        >
                            Repetir
                        </button>
                        <button
                            type="button"
                            :class="ui.buttonPrimary"
                            @click="confirm"
                        >
                            Usar foto
                        </button>
                    </template>
                    <button
                        v-else
                        type="button"
                        :disabled="starting"
                        :class="ui.buttonPrimary"
                        @click="capture"
                    >
                        <Icon name="camera" class="size-4.5" /> Capturar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
