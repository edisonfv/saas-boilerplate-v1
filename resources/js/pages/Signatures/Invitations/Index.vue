<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import { useDateTime } from '@/composables/useDateTime';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { money } from '@/lib/signatures';
import type { SignatureProduct } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

const props = defineProps<{
    invitations: {
        data: {
            id: string;
            customer_name: string;
            customer_email: string | null;
            product_name: string;
            amount: string;
            expires_at: string;
            consumed_at: string | null;
            is_usable: boolean;
            request_id: string | null;
            request_code: string | null;
            created_by_name: string | null;
            link: string | null;
            whatsapp_url: string | null;
        }[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    products: SignatureProduct[];
    methods: Record<string, string>;
    validDays: number;
}>();

const { date } = useDateTime();
const productId = ref(props.products[0]?.id ?? '');
const method = ref('BankTransfer');
const copiedId = ref<string | null>(null);
const selectedProduct = computed(() =>
    props.products.find((product) => product.id === productId.value),
);

async function copy(id: string, link: string) {
    await navigator.clipboard?.writeText(link);
    copiedId.value = id;
    setTimeout(() => (copiedId.value = null), 2000);
}
</script>

<template>
    <Head title="Enlaces prepagados" />

    <GeneralLayout title="Enlaces prepagados">
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div>
                <Link
                    :href="tenant.signatures.requests.index().url"
                    class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
                >
                    <Icon name="arrow-left" /> Volver a solicitudes
                </Link>
                <h2
                    class="mt-3 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Enlaces prepagados
                </h2>
                <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                    Cobra primero y envía a tu cliente un enlace de un solo uso:
                    su solicitud llegará ya pagada, lista para enviar a
                    Uanataca.
                </p>
            </div>

            <Card>
                <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                    <li
                        v-for="invitation in invitations.data"
                        :key="invitation.id"
                        class="py-4"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="font-semibold text-ink-950 dark:text-white"
                                >
                                    {{ invitation.customer_name }}
                                </p>
                                <p class="text-xs text-ink-500">
                                    {{ invitation.product_name }} ·
                                    {{ money(invitation.amount) }}
                                    <template v-if="invitation.created_by_name">
                                        · {{ invitation.created_by_name }}
                                    </template>
                                </p>
                            </div>
                            <Badge v-if="invitation.consumed_at" tone="green"
                                >Usado {{ date(invitation.consumed_at) }}</Badge
                            >
                            <Badge v-else-if="invitation.is_usable" tone="blue"
                                >Vigente hasta
                                {{ date(invitation.expires_at) }}</Badge
                            >
                            <Badge v-else tone="red">Vencido</Badge>
                        </div>

                        <div
                            v-if="invitation.link"
                            class="mt-3 flex flex-wrap items-center gap-2"
                        >
                            <button
                                type="button"
                                :class="[ui.buttonSecondary, 'h-9']"
                                @click="copy(invitation.id, invitation.link)"
                            >
                                {{
                                    copiedId === invitation.id
                                        ? 'Copiado'
                                        : 'Copiar enlace'
                                }}
                            </button>
                            <a
                                v-if="invitation.whatsapp_url"
                                :href="invitation.whatsapp_url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#25D366] px-3 text-sm font-semibold text-white hover:brightness-95"
                            >
                                <Icon name="chat" class="size-4" /> WhatsApp
                            </a>
                            <Form
                                v-if="invitation.customer_email"
                                v-bind="
                                    tenant.signatures.invitations.resend.form(
                                        invitation.id,
                                    )
                                "
                                #default="{ processing }"
                            >
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    :class="[ui.buttonSecondary, 'h-9']"
                                >
                                    Enviar por correo
                                </button>
                            </Form>
                        </div>
                        <Link
                            v-if="invitation.request_id"
                            :href="
                                tenant.signatures.requests.show(
                                    invitation.request_id,
                                ).url
                            "
                            class="mt-2 inline-block text-sm"
                            :class="ui.link"
                        >
                            Ver solicitud {{ invitation.request_code }}
                        </Link>
                    </li>
                    <li
                        v-if="invitations.data.length === 0"
                        class="py-12 text-center text-sm text-ink-500"
                    >
                        Aún no has creado enlaces prepagados.
                    </li>
                </ul>
                <Pagination
                    class="mt-4"
                    :links="invitations.links"
                    :from="invitations.from"
                    :to="invitations.to"
                    :total="invitations.total"
                />
            </Card>
        </div>

        <aside class="col-span-12 xl:col-span-4">
            <Card title="Nuevo enlace prepagado">
                <Form
                    v-bind="tenant.signatures.invitations.store.form()"
                    reset-on-success
                    #default="{ errors, processing }"
                    class="space-y-3"
                >
                    <div>
                        <label for="inv-product" :class="ui.label">Firma</label>
                        <select
                            id="inv-product"
                            v-model="productId"
                            name="signature_product_id"
                            :class="ui.input"
                        >
                            <option
                                v-for="product in products"
                                :key="product.id"
                                :value="product.id"
                            >
                                {{ product.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="inv-name" :class="ui.label"
                            >Nombre del cliente</label
                        >
                        <input
                            id="inv-name"
                            name="customer_name"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errors.customer_name" :class="ui.error">
                            {{ errors.customer_name }}
                        </p>
                    </div>
                    <div>
                        <label for="inv-email" :class="ui.label"
                            >Correo (para enviarle el enlace)</label
                        >
                        <input
                            id="inv-email"
                            name="customer_email"
                            type="email"
                            :class="ui.input"
                        />
                        <p v-if="errors.customer_email" :class="ui.error">
                            {{ errors.customer_email }}
                        </p>
                    </div>
                    <div>
                        <label for="inv-phone" :class="ui.label"
                            >Celular (para WhatsApp)</label
                        >
                        <input
                            id="inv-phone"
                            name="customer_phone"
                            inputmode="tel"
                            placeholder="0991234567"
                            :class="ui.input"
                        />
                        <p v-if="errors.customer_phone" :class="ui.error">
                            Celular de 10 dígitos que empiece con 09.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="inv-amount" :class="ui.label"
                                >Monto cobrado</label
                            >
                            <input
                                id="inv-amount"
                                :key="productId"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                :value="selectedProduct?.retail_price ?? ''"
                                :class="ui.input"
                            />
                        </div>
                        <div>
                            <label for="inv-method" :class="ui.label"
                                >Medio</label
                            >
                            <select
                                id="inv-method"
                                v-model="method"
                                name="method"
                                :class="ui.input"
                            >
                                <option
                                    v-for="(label, value) in methods"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div v-if="method !== 'Cash'">
                        <label for="inv-reference" :class="ui.label"
                            >Número de comprobante</label
                        >
                        <input
                            id="inv-reference"
                            name="reference"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errors.reference" :class="ui.error">
                            {{ errors.reference }}
                        </p>
                    </div>
                    <p :class="ui.help">
                        El enlace es de un solo uso y vence en
                        {{ validDays }} días.
                    </p>
                    <button
                        type="submit"
                        :disabled="processing"
                        :class="[ui.buttonPrimary, 'w-full']"
                    >
                        Crear enlace
                    </button>
                </Form>
            </Card>
        </aside>
    </GeneralLayout>
</template>
