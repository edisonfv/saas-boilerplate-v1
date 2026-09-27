<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import StorefrontShell from '@/components/signatures/StorefrontShell.vue';
import { money } from '@/lib/signatures';
import type { StorefrontInfo } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';
import type { IconName } from '@/types/icon';

const props = defineProps<{
    storefront: StorefrontInfo;
    products: {
        id: string;
        name: string;
        validity_label: string;
        container: string;
        container_label: string;
        price: string | null;
    }[];
    requirements: { type: string; label: string; documents: string[] }[];
}>();

const lowestPrice = computed(() => {
    const prices = props.products
        .map((product) => Number(product.price))
        .filter((price) => price > 0);

    return prices.length ? Math.min(...prices) : null;
});
const requirementTab = ref(props.requirements[0]?.type ?? '');
const activeRequirement = computed(() =>
    props.requirements.find(
        (requirement) => requirement.type === requirementTab.value,
    ),
);

function applyUrl(productId?: string): string {
    return tenant.signatures.storefront.create({
        query: productId ? { firma: productId } : {},
    }).url;
}

const uses: { icon: IconName; title: string; text: string }[] = [
    {
        icon: 'currency',
        title: 'Facturación electrónica',
        text: 'Emite facturas, retenciones y notas de crédito autorizadas por el SRI.',
    },
    {
        icon: 'pencil',
        title: 'Firma de documentos',
        text: 'Firma contratos y documentos PDF con la misma validez que tu firma manuscrita.',
    },
    {
        icon: 'building',
        title: 'Trámites públicos',
        text: 'Compras públicas, Quipux, IESS, Superintendencias y otros trámites en línea.',
    },
    {
        icon: 'shield',
        title: 'Seguridad jurídica',
        text: 'Certificado emitido por Uanataca, entidad de certificación acreditada en Ecuador.',
    },
];

const steps = [
    {
        title: 'Llena tu solicitud',
        text: 'Elige tu firma y completa tus datos en línea, en pocos minutos.',
    },
    {
        title: 'Sube tus documentos',
        text: 'Fotos de tu cédula y una selfie. Si es para empresa, sus documentos en PDF.',
    },
    {
        title: 'Validamos y pagas',
        text: 'Revisamos tu información y te contactamos para completar el pago.',
    },
    {
        title: 'Recibe tu firma',
        text: 'La entidad certificadora valida tu identidad y te envía tu firma por correo.',
    },
];

const faqs = [
    {
        question: '¿Qué es la firma electrónica?',
        answer: 'Es un certificado digital que te identifica en internet y tiene la misma validez legal que tu firma manuscrita, según la Ley de Comercio Electrónico del Ecuador.',
    },
    {
        question: '¿Cuánto tarda la emisión?',
        answer: 'Depende de la validación de tu identidad por la entidad certificadora. Con documentos claros y completos el proceso es rápido; te avisamos en cada paso.',
    },
    {
        question: '¿Qué diferencia hay entre archivo y nube?',
        answer: 'El archivo .p12 se descarga y lo usas desde tu computador o sistema de facturación. En la nube firmas desde cualquier dispositivo sin instalar nada.',
    },
    {
        question: '¿Qué vigencia debo elegir?',
        answer: 'Depende de tu uso: para facturar lo habitual es 1 o 2 años. Mientras más larga la vigencia, menor el costo por año.',
    },
    {
        question: '¿Qué pasa si mi solicitud es rechazada?',
        answer: 'Te indicamos qué corregir (por ejemplo, una foto poco legible) para que puedas completar tu trámite.',
    },
];
</script>

<template>
    <Head :title="`Firma electrónica · ${storefront.company_name}`" />

    <StorefrontShell :storefront="storefront">
        <!-- Hero -->
        <section
            class="bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 text-white"
        >
            <div
                class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-5 md:py-24"
            >
                <div class="md:col-span-3">
                    <p class="eyebrow text-primary-100">
                        Firma electrónica en Ecuador
                    </p>
                    <h1
                        class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl"
                    >
                        {{ storefront.headline }}
                    </h1>
                    <p
                        v-if="storefront.description"
                        class="mt-5 max-w-xl text-lg text-primary-50/90"
                    >
                        {{ storefront.description }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="applyUrl()"
                            class="inline-flex h-12 items-center gap-2 rounded-lg bg-white px-6 font-bold text-primary-700 shadow-sm transition hover:bg-primary-50"
                        >
                            Solicitar mi firma
                            <Icon name="arrow-right" class="size-4" />
                        </Link>
                        <a
                            v-if="storefront.whatsapp_url"
                            :href="storefront.whatsapp_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex h-12 items-center gap-2 rounded-lg border border-white/40 px-6 font-bold text-white transition hover:bg-white/10"
                        >
                            <Icon name="chat" class="size-4.5" /> Escríbenos por
                            WhatsApp
                        </a>
                    </div>
                    <ul
                        class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-primary-50/90"
                    >
                        <li class="flex items-center gap-2">
                            <Icon name="check-circle" class="size-4" /> 100% en
                            línea
                        </li>
                        <li class="flex items-center gap-2">
                            <Icon name="check-circle" class="size-4" /> Válida
                            en todo Ecuador
                        </li>
                        <li class="flex items-center gap-2">
                            <Icon name="check-circle" class="size-4" /> Emitida
                            por Uanataca
                        </li>
                    </ul>
                </div>
                <div class="flex items-center md:col-span-2">
                    <div
                        class="w-full rounded-2xl bg-white/10 p-6 ring-1 ring-white/20 backdrop-blur"
                    >
                        <p class="text-sm text-primary-100">Firmas desde</p>
                        <p
                            class="mt-1 text-5xl font-extrabold tracking-tight tabular-nums"
                        >
                            {{
                                lowestPrice !== null
                                    ? money(lowestPrice)
                                    : 'Consultar'
                            }}
                        </p>
                        <p class="mt-4 text-sm text-primary-50/90">
                            Personas naturales, con RUC y representantes
                            legales. Vigencias de 30 días a 5 años.
                        </p>
                        <a
                            href="#precios"
                            class="mt-5 inline-flex items-center gap-1 text-sm font-bold text-white underline-offset-4 hover:underline"
                            >Ver todos los precios
                            <Icon name="chevron-right" class="size-4"
                        /></a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Uses -->
        <section class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6">
            <h2
                class="text-center text-2xl font-extrabold text-ink-950 sm:text-3xl dark:text-white"
            >
                ¿Para qué sirve tu firma electrónica?
            </h2>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="use in uses"
                    :key="use.title"
                    class="rounded-xl border border-ink-200 bg-white p-5 dark:border-ink-800 dark:bg-ink-900"
                >
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        <Icon :name="use.icon" />
                    </div>
                    <h3 class="mt-4 font-bold text-ink-950 dark:text-white">
                        {{ use.title }}
                    </h3>
                    <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                        {{ use.text }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Prices -->
        <section
            id="precios"
            class="scroll-mt-20 border-y border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900"
        >
            <div class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6">
                <h2
                    class="text-center text-2xl font-extrabold text-ink-950 sm:text-3xl dark:text-white"
                >
                    Elige tu firma
                </h2>
                <p class="mt-2 text-center text-ink-600 dark:text-ink-400">
                    Precios finales. Escoge la vigencia que necesitas.
                </p>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="flex flex-col rounded-xl border border-ink-200 p-6 transition hover:border-primary-400 hover:shadow-md dark:border-ink-700"
                    >
                        <span
                            class="w-fit rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-600 dark:bg-ink-800 dark:text-ink-300"
                            >{{ product.container_label }}</span
                        >
                        <p
                            class="mt-3 text-lg font-bold text-ink-950 dark:text-white"
                        >
                            {{ product.name }}
                        </p>
                        <p class="text-sm text-ink-500">
                            Vigencia de {{ product.validity_label }}
                        </p>
                        <p
                            class="mt-5 text-4xl font-extrabold text-ink-950 tabular-nums dark:text-white"
                        >
                            {{
                                product.price
                                    ? money(product.price)
                                    : 'Consultar'
                            }}
                        </p>
                        <Link
                            :href="applyUrl(product.id)"
                            :class="[ui.buttonPrimary, 'mt-6']"
                        >
                            Solicitar
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section
            id="como-funciona"
            class="mx-auto w-full max-w-6xl scroll-mt-20 px-4 py-16 sm:px-6"
        >
            <h2
                class="text-center text-2xl font-extrabold text-ink-950 sm:text-3xl dark:text-white"
            >
                Obtén tu firma en 4 pasos
            </h2>
            <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="(step, index) in steps" :key="step.title">
                    <span
                        class="flex size-10 items-center justify-center rounded-full bg-primary-600 font-extrabold text-white"
                        >{{ index + 1 }}</span
                    >
                    <h3 class="mt-4 font-bold text-ink-950 dark:text-white">
                        {{ step.title }}
                    </h3>
                    <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                        {{ step.text }}
                    </p>
                </li>
            </ol>
        </section>

        <!-- Requirements -->
        <section
            id="requisitos"
            class="scroll-mt-20 border-y border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900"
        >
            <div class="mx-auto w-full max-w-4xl px-4 py-16 sm:px-6">
                <h2
                    class="text-center text-2xl font-extrabold text-ink-950 sm:text-3xl dark:text-white"
                >
                    Requisitos
                </h2>
                <div
                    class="mt-8 flex flex-wrap justify-center gap-2"
                    role="tablist"
                >
                    <button
                        v-for="requirement in requirements"
                        :key="requirement.type"
                        type="button"
                        role="tab"
                        :aria-selected="requirementTab === requirement.type"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                            requirementTab === requirement.type
                                ? 'bg-primary-600 text-white'
                                : 'bg-ink-100 text-ink-700 hover:bg-ink-200 dark:bg-ink-800 dark:text-ink-200',
                        ]"
                        @click="requirementTab = requirement.type"
                    >
                        {{ requirement.label }}
                    </button>
                </div>
                <ul
                    v-if="activeRequirement"
                    class="mt-8 grid gap-3 sm:grid-cols-2"
                >
                    <li
                        v-for="document in [
                            ...activeRequirement.documents,
                            'Correo electrónico y celular activos',
                        ]"
                        :key="document"
                        class="flex items-start gap-3 rounded-lg bg-ink-50 p-3 text-sm text-ink-700 dark:bg-ink-800/50 dark:text-ink-200"
                    >
                        <Icon
                            name="check-circle"
                            class="mt-0.5 size-4.5 shrink-0 text-emerald-600"
                        />
                        {{ document }}
                    </li>
                </ul>
                <p class="mt-6 text-center text-sm text-ink-500">
                    Fotos nítidas y a color, tomadas en el momento, sin filtros
                    ni recortes. Documentos de empresa en PDF.
                </p>
            </div>
        </section>

        <!-- FAQ -->
        <section
            id="preguntas"
            class="mx-auto w-full max-w-3xl scroll-mt-20 px-4 py-16 sm:px-6"
        >
            <h2
                class="text-center text-2xl font-extrabold text-ink-950 sm:text-3xl dark:text-white"
            >
                Preguntas frecuentes
            </h2>
            <div class="mt-8 space-y-3">
                <details
                    v-for="faq in faqs"
                    :key="faq.question"
                    class="group rounded-xl border border-ink-200 bg-white p-5 dark:border-ink-800 dark:bg-ink-900"
                >
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink-950 dark:text-white"
                    >
                        {{ faq.question }}
                        <Icon
                            name="chevron-right"
                            class="size-4 shrink-0 transition group-open:rotate-90"
                        />
                    </summary>
                    <p class="mt-3 text-sm text-ink-600 dark:text-ink-400">
                        {{ faq.answer }}
                    </p>
                </details>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="mx-auto w-full max-w-6xl px-4 pb-20 sm:px-6">
            <div
                class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-ink-950 p-8 text-white sm:flex-row sm:items-center dark:bg-ink-900"
            >
                <div>
                    <h2 class="text-2xl font-extrabold">
                        ¿Listo para obtener tu firma?
                    </h2>
                    <p class="mt-1 text-ink-300">
                        Empieza tu solicitud ahora; te acompañamos en cada paso.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a
                        v-if="storefront.whatsapp_url"
                        :href="storefront.whatsapp_url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-11 items-center rounded-lg border border-white/30 px-5 font-bold transition hover:bg-white/10"
                        >WhatsApp</a
                    >
                    <Link
                        :href="applyUrl()"
                        class="inline-flex h-11 items-center gap-2 rounded-lg bg-primary-600 px-5 font-bold transition hover:bg-primary-500"
                    >
                        Empezar solicitud
                        <Icon name="arrow-right" class="size-4" />
                    </Link>
                </div>
            </div>
        </section>
    </StorefrontShell>
</template>
