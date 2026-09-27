<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import BannerPhotosCard from '@/components/signatures/BannerPhotosCard.vue';
import EditableListCard from '@/components/signatures/EditableListCard.vue';
import SeoCard from '@/components/signatures/SeoCard.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { money } from '@/lib/signatures';
import type { BankAccount, SignatureProduct } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

type Entry = { title: string; text: string };
type Faq = { question: string; answer: string };

const props = defineProps<{
    storefront: {
        headline: string;
        description: string | null;
        seo_title: string;
        seo_description: string;
        hero_slides: { id: string; url: string; alt: string }[];
        contact_email: string | null;
        contact_phone: string | null;
        whatsapp: string | null;
        whatsapp_message: string;
        prices: Record<string, string>;
        uses: Entry[];
        steps: Entry[];
        faqs: Faq[];
        bank_accounts: BankAccount[];
    };
    products: SignatureProduct[];
    publicUrl: string;
    seoDefaults: { title: string; description: string };
    maxSlides: number;
}>();

/**
 * useForm (JSON) rather than <Form>: the editable lists must reach the
 * server even when empty, which hides that section of the site.
 */
const form = useForm({
    headline: props.storefront.headline,
    description: props.storefront.description ?? '',
    seo_title: props.storefront.seo_title,
    seo_description: props.storefront.seo_description,
    hero_slides: props.storefront.hero_slides.map((slide) => ({ ...slide })),
    contact_email: props.storefront.contact_email ?? '',
    contact_phone: props.storefront.contact_phone ?? '',
    whatsapp: props.storefront.whatsapp ?? '',
    whatsapp_message: props.storefront.whatsapp_message,
    prices: Object.fromEntries(
        props.products.map((product) => [
            product.id,
            props.storefront.prices[product.id] ?? '',
        ]),
    ) as Record<string, string>,
    uses: props.storefront.uses.map((entry) => ({ ...entry })),
    steps: props.storefront.steps.map((entry) => ({ ...entry })),
    faqs: props.storefront.faqs.map((faq) => ({ ...faq })),
    bank_accounts: props.storefront.bank_accounts.map((account) => ({
        ...account,
    })) as Record<string, string>[],
});

const errors = computed(() => form.errors as Record<string, string>);

// Photos are uploaded right away (preserving this form's state): add the
// new ones to the list without touching unsaved edits.
watch(
    () => props.storefront.hero_slides,
    (slides) => {
        const known = new Set(form.hero_slides.map((slide) => slide.id));

        form.hero_slides.push(
            ...slides
                .filter((slide) => !known.has(slide.id))
                .map((slide) => ({ ...slide })),
        );
    },
);

/** Same link the public site builds (SignatureStorefront::whatsappUrl()). */
const whatsappPreview = computed(() => {
    const number = form.whatsapp.replace(/\D/g, '');

    return number
        ? `https://wa.me/${number}?text=${encodeURIComponent(form.whatsapp_message)}`
        : null;
});

function submit() {
    form.put(tenant.signatures.storefront.update().url, {
        preserveScroll: true,
    });
}

/** Validation message of one product's price (keys like "prices.{id}"). */
const priceError = (productId: string): string | undefined =>
    (form.errors as Record<string, string | undefined>)[`prices.${productId}`];
</script>

<template>
    <Head title="Sitio web de firmas" />

    <GeneralLayout title="Sitio web de firmas">
        <form
            autocomplete="off"
            class="col-span-12 grid grid-cols-12 gap-6"
            @submit.prevent="submit"
        >
            <div class="col-span-12 space-y-6 xl:col-span-8">
                <BannerPhotosCard
                    v-model="form.hero_slides"
                    :max-slides="maxSlides"
                    :errors="errors"
                />

                <Card title="Portada">
                    <div class="space-y-4">
                        <div>
                            <label for="headline" :class="ui.label"
                                >Titular</label
                            >
                            <input
                                id="headline"
                                v-model="form.headline"
                                required
                                maxlength="160"
                                :class="ui.input"
                            />
                            <p v-if="errors.headline" :class="ui.error">
                                {{ errors.headline }}
                            </p>
                        </div>
                        <div>
                            <label for="description" :class="ui.label"
                                >Descripción</label
                            >
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                maxlength="2000"
                                :class="ui.input"
                            />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="contact_email" :class="ui.label"
                                    >Correo de contacto</label
                                >
                                <input
                                    id="contact_email"
                                    v-model="form.contact_email"
                                    type="email"
                                    :class="ui.input"
                                />
                                <p
                                    v-if="errors.contact_email"
                                    :class="ui.error"
                                >
                                    {{ errors.contact_email }}
                                </p>
                            </div>
                            <div>
                                <label for="contact_phone" :class="ui.label"
                                    >Teléfono</label
                                >
                                <input
                                    id="contact_phone"
                                    v-model="form.contact_phone"
                                    :class="ui.input"
                                />
                            </div>
                        </div>
                    </div>
                </Card>

                <SeoCard
                    v-model:title="form.seo_title"
                    v-model:description="form.seo_description"
                    :url="publicUrl"
                    :defaults="seoDefaults"
                    :errors="errors"
                />

                <Card title="WhatsApp">
                    <p class="mb-4 text-sm text-ink-600 dark:text-ink-400">
                        Tus clientes verán un botón de WhatsApp en todo el sitio
                        y durante la solicitud. Al enviarla, podrán continuar la
                        conversación con su número de solicitud.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label for="whatsapp" :class="ui.label"
                                >Número de WhatsApp</label
                            >
                            <input
                                id="whatsapp"
                                v-model="form.whatsapp"
                                inputmode="tel"
                                placeholder="593991234567"
                                :class="ui.input"
                            />
                            <p :class="ui.help">
                                Con código de país y sin espacios (593 para
                                Ecuador). Déjalo vacío para ocultar el botón.
                            </p>
                            <p v-if="errors.whatsapp" :class="ui.error">
                                Número con código de país, sin espacios.
                            </p>
                        </div>
                        <div>
                            <label for="whatsapp_message" :class="ui.label"
                                >Mensaje predeterminado</label
                            >
                            <textarea
                                id="whatsapp_message"
                                v-model="form.whatsapp_message"
                                rows="3"
                                maxlength="500"
                                :class="ui.input"
                            />
                            <p v-if="errors.whatsapp_message" :class="ui.error">
                                {{ errors.whatsapp_message }}
                            </p>
                        </div>
                        <a
                            v-if="whatsappPreview"
                            :href="whatsappPreview"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 text-sm"
                            :class="ui.link"
                        >
                            <Icon name="chat" class="size-4" /> Probar enlace de
                            WhatsApp
                        </a>
                    </div>
                </Card>

                <EditableListCard
                    v-model="form.bank_accounts"
                    title="Cuentas para recibir pagos"
                    description="Tus clientes verán estas cuentas en su enlace de pago para transferir o depositar."
                    name="bank_accounts"
                    :fields="[
                        { key: 'bank', label: 'Banco', max: 80 },
                        {
                            key: 'account_type',
                            label: 'Tipo de cuenta (Ahorros / Corriente)',
                            max: 40,
                        },
                        { key: 'number', label: 'Número de cuenta', max: 30 },
                        { key: 'holder', label: 'Titular', max: 120 },
                        {
                            key: 'holder_id',
                            label: 'Cédula o RUC del titular',
                            max: 20,
                        },
                    ]"
                    add-label="Agregar cuenta"
                    :max-rows="6"
                    :errors="errors"
                />

                <Card title="Precios al público">
                    <p class="mb-4 text-sm text-ink-600 dark:text-ink-400">
                        Define a cuánto vendes cada firma. Si lo dejas vacío se
                        usa el precio sugerido.
                    </p>
                    <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                        <li
                            v-for="product in products"
                            :key="product.id"
                            class="flex items-center gap-4 py-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="font-semibold text-ink-950 dark:text-white"
                                >
                                    {{ product.name }}
                                </p>
                                <p class="text-xs text-ink-500">
                                    Sugerido
                                    {{ money(product.suggested_retail_price) }}
                                    <template v-if="product.min_retail_price">
                                        · mínimo
                                        {{ money(product.min_retail_price) }}
                                    </template>
                                </p>
                                <p
                                    v-if="priceError(product.id)"
                                    class="form-error"
                                >
                                    {{ priceError(product.id) }}
                                </p>
                            </div>
                            <input
                                v-model="form.prices[product.id]"
                                type="number"
                                step="0.01"
                                :min="product.min_retail_price ?? 0"
                                :placeholder="
                                    product.suggested_retail_price ?? ''
                                "
                                :aria-label="`Precio de ${product.name}`"
                                class="form-control w-32 shrink-0 text-right"
                            />
                        </li>
                    </ul>
                </Card>

                <EditableListCard
                    v-model="form.uses"
                    title="¿Para qué sirve tu firma?"
                    description="Tarjetas de beneficios que se muestran después de la portada."
                    name="uses"
                    :fields="[
                        { key: 'title', label: 'Título', max: 80 },
                        {
                            key: 'text',
                            label: 'Texto',
                            multiline: true,
                            max: 300,
                        },
                    ]"
                    add-label="Agregar beneficio"
                    :max-rows="8"
                    :errors="errors"
                />

                <EditableListCard
                    v-model="form.steps"
                    title="Cómo funciona"
                    description="Pasos del proceso, en orden."
                    name="steps"
                    :fields="[
                        { key: 'title', label: 'Paso', max: 80 },
                        {
                            key: 'text',
                            label: 'Descripción',
                            multiline: true,
                            max: 300,
                        },
                    ]"
                    add-label="Agregar paso"
                    :max-rows="6"
                    :errors="errors"
                />

                <EditableListCard
                    v-model="form.faqs"
                    title="Preguntas frecuentes"
                    description="Preguntas y respuestas que se muestran al final del sitio."
                    name="faqs"
                    :fields="[
                        { key: 'question', label: 'Pregunta', max: 200 },
                        {
                            key: 'answer',
                            label: 'Respuesta',
                            multiline: true,
                            max: 1000,
                        },
                    ]"
                    add-label="Agregar pregunta"
                    :max-rows="15"
                    :errors="errors"
                />
            </div>

            <div class="col-span-12 space-y-6 xl:col-span-4">
                <Card title="Tu sitio web">
                    <p class="text-sm text-ink-600 dark:text-ink-400">
                        Es la página principal de tu subdominio. Es pública y
                        tus clientes pueden solicitar su firma desde ahí.
                    </p>
                    <p
                        class="mt-3 rounded-lg bg-ink-50 px-3 py-2 font-mono text-xs break-all text-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        {{ publicUrl }}
                    </p>
                    <a
                        :href="publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-4 inline-flex items-center gap-2 text-sm"
                        :class="ui.link"
                    >
                        <Icon name="globe" class="size-4" /> Ver sitio público
                    </a>
                </Card>
            </div>

            <FormActions
                :processing="form.processing"
                :is-dirty="form.isDirty"
                submit-label="Guardar sitio"
                :cancel-href="tenant.signatures.requests.index().url"
            />
        </form>
    </GeneralLayout>
</template>
