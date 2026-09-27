<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { money } from '@/lib/signatures';
import type { SignatureProduct } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

defineProps<{
    storefront: {
        is_published: boolean;
        headline: string;
        description: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        whatsapp: string | null;
        prices: Record<string, string>;
    };
    products: SignatureProduct[];
    publicUrl: string;
}>();
</script>

<template>
    <Head title="Sitio web de firmas" />

    <GeneralLayout title="Sitio web de firmas">
        <Form
            autocomplete="off"
            v-bind="tenant.signatures.storefront.update.form()"
            #default="{ errors, processing, isDirty }"
            class="col-span-12 grid grid-cols-12 gap-6"
        >
            <div class="col-span-12 space-y-6 xl:col-span-8">
                <Card title="Contenido de la página">
                    <div class="space-y-4">
                        <div>
                            <label for="headline" :class="ui.label"
                                >Titular</label
                            >
                            <input
                                id="headline"
                                name="headline"
                                :value="storefront.headline"
                                required
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
                                name="description"
                                rows="4"
                                :value="storefront.description ?? ''"
                                :class="ui.input"
                            />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="contact_email" :class="ui.label"
                                    >Correo de contacto</label
                                >
                                <input
                                    id="contact_email"
                                    name="contact_email"
                                    type="email"
                                    :value="storefront.contact_email ?? ''"
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
                                    name="contact_phone"
                                    :value="storefront.contact_phone ?? ''"
                                    :class="ui.input"
                                />
                            </div>
                            <div>
                                <label for="whatsapp" :class="ui.label"
                                    >WhatsApp</label
                                >
                                <input
                                    id="whatsapp"
                                    name="whatsapp"
                                    placeholder="593991234567"
                                    :value="storefront.whatsapp ?? ''"
                                    :class="ui.input"
                                />
                                <p v-if="errors.whatsapp" :class="ui.error">
                                    Número con código de país, sin espacios.
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

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
                                </p>
                            </div>
                            <input
                                :name="`prices[${product.id}]`"
                                type="number"
                                step="0.01"
                                min="0"
                                :value="storefront.prices[product.id] ?? ''"
                                :placeholder="
                                    product.suggested_retail_price ?? ''
                                "
                                :aria-label="`Precio de ${product.name}`"
                                class="form-control w-32 shrink-0 text-right"
                            />
                        </li>
                    </ul>
                </Card>
            </div>

            <div class="col-span-12 space-y-6 xl:col-span-4">
                <Card title="Publicación">
                    <input type="hidden" name="is_published" value="0" />
                    <label
                        class="flex items-start gap-3 text-sm text-ink-700 dark:text-ink-300"
                    >
                        <input
                            type="checkbox"
                            name="is_published"
                            value="1"
                            :checked="storefront.is_published"
                            :class="[ui.checkbox, 'mt-0.5']"
                        />
                        <span>
                            Publicar el sitio: tus clientes podrán ver tus
                            firmas y dejar solicitudes en línea.
                        </span>
                    </label>
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
                :processing="processing"
                :is-dirty="isDirty"
                submit-label="Guardar sitio"
                :cancel-href="tenant.signatures.requests.index().url"
            />
        </Form>
    </GeneralLayout>
</template>
