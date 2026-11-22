<script setup lang="ts">
import { computed } from 'vue';

/**
 * Small illustration of what each document slot expects (front and back
 * of the ID card, selfie holding it, PDF documents), so applicants on a
 * phone know what to capture before opening the camera. Inline SVG that
 * follows the theme colors.
 */
const props = defineProps<{
    kind: string;
    label: string;
}>();

const scene = computed(() => {
    switch (props.kind) {
        case 'IdFront':
        case 'LegalRepresentativeId':
            return 'id-front';
        case 'IdBack':
            return 'id-back';
        case 'Selfie':
            return 'selfie';
        case 'LegalRepresentativeAuthorization':
            return 'signed-document';
        default:
            return 'pdf-document';
    }
});
</script>

<template>
    <svg
        viewBox="0 0 120 90"
        class="text-primary-600 dark:text-primary-400"
        role="img"
        :aria-label="`Ejemplo: ${label}`"
    >
        <rect
            x="0"
            y="0"
            width="120"
            height="90"
            rx="14"
            class="fill-primary-50 dark:fill-primary-500/10"
        />

        <!-- Front of the ID card: photo, data lines, flag stripe. -->
        <g v-if="scene === 'id-front'">
            <rect
                x="14"
                y="18"
                width="92"
                height="56"
                rx="7"
                class="fill-white stroke-ink-300 dark:fill-ink-800 dark:stroke-ink-600"
                stroke-width="1.5"
            />
            <rect x="14" y="18" width="92" height="7" rx="3" fill="#FFD100" />
            <rect x="14" y="23" width="92" height="3" fill="#0072CE" />
            <rect x="14" y="26" width="92" height="2" fill="#EF3340" />
            <rect
                x="21"
                y="34"
                width="24"
                height="30"
                rx="3"
                class="fill-primary-100 dark:fill-primary-500/20"
            />
            <circle cx="33" cy="44" r="6" fill="currentColor" />
            <path d="M24 62c1.5-7 5-10 9-10s7.5 3 9 10z" fill="currentColor" />
            <rect
                x="51"
                y="36"
                width="44"
                height="4"
                rx="2"
                class="fill-ink-300 dark:fill-ink-600"
            />
            <rect
                x="51"
                y="45"
                width="34"
                height="4"
                rx="2"
                class="fill-ink-200 dark:fill-ink-700"
            />
            <rect
                x="51"
                y="54"
                width="40"
                height="4"
                rx="2"
                class="fill-ink-200 dark:fill-ink-700"
            />
        </g>

        <!-- Back of the ID card: fingerprint code highlighted, barcode. -->
        <g v-else-if="scene === 'id-back'">
            <rect
                x="14"
                y="18"
                width="92"
                height="56"
                rx="7"
                class="fill-white stroke-ink-300 dark:fill-ink-800 dark:stroke-ink-600"
                stroke-width="1.5"
            />
            <g
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linecap="round"
            >
                <path d="M28 44c0-5 4-9 9-9s9 4 9 9" />
                <path d="M31 47c0-4 2.5-7 6-7s6 3 6 7v4" />
                <path d="M37 45v9" />
                <path d="M26 52c1-2 1-5 1-8" />
            </g>
            <rect
                x="54"
                y="30"
                width="44"
                height="12"
                rx="3"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-dasharray="3 2"
            />
            <rect
                x="58"
                y="34"
                width="36"
                height="4"
                rx="2"
                fill="currentColor"
            />
            <g class="fill-ink-400 dark:fill-ink-500">
                <rect x="22" y="60" width="2" height="8" />
                <rect x="26" y="60" width="1" height="8" />
                <rect x="29" y="60" width="3" height="8" />
                <rect x="34" y="60" width="1" height="8" />
                <rect x="37" y="60" width="2" height="8" />
                <rect x="41" y="60" width="1" height="8" />
                <rect x="44" y="60" width="3" height="8" />
            </g>
            <rect
                x="54"
                y="50"
                width="40"
                height="4"
                rx="2"
                class="fill-ink-200 dark:fill-ink-700"
            />
            <rect
                x="54"
                y="59"
                width="30"
                height="4"
                rx="2"
                class="fill-ink-200 dark:fill-ink-700"
            />
        </g>

        <!-- Selfie holding the ID card next to the face. -->
        <g v-else-if="scene === 'selfie'">
            <circle
                cx="46"
                cy="36"
                r="13"
                class="fill-amber-200 dark:fill-amber-300"
            />
            <path
                d="M33 34c0-9 6-14 13-14s13 5 13 13c-4-4-9-5-13-5s-9 1-13 6z"
                class="fill-ink-700 dark:fill-ink-500"
            />
            <path
                d="M22 90c2-18 12-28 24-28s22 10 24 28z"
                fill="currentColor"
            />
            <rect
                x="68"
                y="34"
                width="36"
                height="24"
                rx="4"
                class="fill-white stroke-ink-300 dark:fill-ink-800 dark:stroke-ink-600"
                stroke-width="1.5"
                transform="rotate(-6 86 46)"
            />
            <rect
                x="72"
                y="38"
                width="10"
                height="13"
                rx="2"
                class="fill-primary-100 dark:fill-primary-500/30"
                transform="rotate(-6 86 46)"
            />
            <rect
                x="85"
                y="40"
                width="15"
                height="3"
                rx="1.5"
                class="fill-ink-300 dark:fill-ink-600"
                transform="rotate(-6 86 46)"
            />
            <rect
                x="85"
                y="46"
                width="11"
                height="3"
                rx="1.5"
                class="fill-ink-200 dark:fill-ink-700"
                transform="rotate(-6 86 46)"
            />
            <path
                d="M62 66c3-4 6-9 9-10"
                fill="none"
                stroke="currentColor"
                stroke-width="5"
                stroke-linecap="round"
            />
            <circle
                cx="72"
                cy="57"
                r="4"
                class="fill-amber-200 dark:fill-amber-300"
            />
        </g>

        <!-- Document with a signature line. -->
        <g v-else-if="scene === 'signed-document'">
            <rect
                x="34"
                y="12"
                width="52"
                height="66"
                rx="5"
                class="fill-white stroke-ink-300 dark:fill-ink-800 dark:stroke-ink-600"
                stroke-width="1.5"
            />
            <rect
                x="42"
                y="22"
                width="36"
                height="3"
                rx="1.5"
                class="fill-ink-300 dark:fill-ink-600"
            />
            <rect
                x="42"
                y="30"
                width="30"
                height="3"
                rx="1.5"
                class="fill-ink-200 dark:fill-ink-700"
            />
            <rect
                x="42"
                y="38"
                width="33"
                height="3"
                rx="1.5"
                class="fill-ink-200 dark:fill-ink-700"
            />
            <path
                d="M44 62c4-8 7 4 10-2s5-6 7 0 6 1 9-3"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
            />
            <rect
                x="42"
                y="66"
                width="36"
                height="1.5"
                class="fill-ink-300 dark:fill-ink-600"
            />
        </g>

        <!-- PDF document. -->
        <g v-else>
            <path
                d="M38 12h32l16 16v50a5 5 0 0 1-5 5H38a5 5 0 0 1-5-5V17a5 5 0 0 1 5-5z"
                class="fill-white stroke-ink-300 dark:fill-ink-800 dark:stroke-ink-600"
                stroke-width="1.5"
            />
            <path
                d="M70 12v11a5 5 0 0 0 5 5h11"
                fill="none"
                class="stroke-ink-300 dark:stroke-ink-600"
                stroke-width="1.5"
            />
            <rect
                x="41"
                y="36"
                width="36"
                height="3"
                rx="1.5"
                class="fill-ink-200 dark:fill-ink-700"
            />
            <rect
                x="41"
                y="44"
                width="30"
                height="3"
                rx="1.5"
                class="fill-ink-200 dark:fill-ink-700"
            />
            <rect x="28" y="56" width="30" height="16" rx="4" fill="#DC2626" />
            <text
                x="43"
                y="67.5"
                text-anchor="middle"
                font-size="9"
                font-weight="800"
                fill="#fff"
                font-family="sans-serif"
            >
                PDF
            </text>
        </g>
    </svg>
</template>
