<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import QuotaCard from '@/components/signatures/QuotaCard.vue';
import StatCard from '@/components/StatCard.vue';
import { useDateTime } from '@/composables/useDateTime';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { money, signatureStatusTone } from '@/lib/signatures';
import type {
    SignatureAccount,
    SignatureRequestSummary,
} from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

const props = defineProps<{
    requests: {
        data: SignatureRequestSummary[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search: string; status: string | null };
    statuses: Record<string, string>;
    counts: { drafts: number; issued: number };
    account: SignatureAccount | null;
    can: { create: boolean; storefront: boolean };
}>();

const { dateTime } = useDateTime();
const search = ref(props.filters.search);
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        tenant.signatures.requests.index().url,
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Firmas electrónicas" />

    <GeneralLayout title="Firmas electrónicas">
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Punto de venta
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Solicitudes de firma
                    </h2>
                </div>
                <div class="flex gap-2">
                    <Link
                        v-if="can.storefront"
                        :href="tenant.signatures.storefront.edit().url"
                        :class="ui.buttonSecondary"
                    >
                        <Icon name="globe" class="size-4.5" /> Sitio web
                    </Link>
                    <Link
                        v-if="can.create"
                        :href="tenant.signatures.requests.create().url"
                        :class="ui.buttonPrimary"
                    >
                        <Icon name="plus" class="size-4.5" /> Nueva venta
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <StatCard
                    label="Borradores por enviar"
                    :value="counts.drafts"
                    icon="pencil"
                    helper="Incluye las recibidas desde tu sitio web"
                />
                <StatCard
                    label="Firmas emitidas"
                    :value="counts.issued"
                    icon="check-circle"
                />
            </div>

            <Card>
                <form
                    class="mb-4 flex flex-col gap-3 sm:flex-row"
                    @submit.prevent="applyFilters"
                >
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por cédula, nombre, correo o empresa"
                        :class="[ui.input, 'flex-1']"
                    />
                    <select
                        v-model="status"
                        class="form-control w-full sm:w-48"
                        @change="applyFilters"
                    >
                        <option value="">Todos los estados</option>
                        <option
                            v-for="(label, value) in statuses"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </form>

                <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                    <li v-for="item in requests.data" :key="item.id">
                        <Link
                            :href="tenant.signatures.requests.show(item.id).url"
                            class="-mx-2 flex items-center gap-4 rounded-lg px-2 py-3.5 transition hover:bg-ink-50 dark:hover:bg-ink-800/40"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate font-semibold text-ink-950 dark:text-white"
                                >
                                    {{ item.applicant_name }}
                                    <span
                                        v-if="item.company_name"
                                        class="font-normal text-ink-500"
                                        >· {{ item.company_name }}</span
                                    >
                                </p>
                                <p class="mt-0.5 text-xs text-ink-500">
                                    <span class="font-mono">{{
                                        item.code
                                    }}</span>
                                    · {{ item.product_name }} ·
                                    {{ item.source_label }} ·
                                    {{ dateTime(item.created_at) }}
                                </p>
                            </div>
                            <span
                                v-if="item.sale_price"
                                class="hidden text-sm font-semibold text-ink-700 tabular-nums sm:block dark:text-ink-300"
                                >{{ money(item.sale_price) }}</span
                            >
                            <Badge :tone="signatureStatusTone(item.status)">{{
                                item.status_label
                            }}</Badge>
                            <Icon
                                name="chevron-right"
                                class="size-4 text-ink-400"
                            />
                        </Link>
                    </li>
                    <li
                        v-if="requests.data.length === 0"
                        class="py-12 text-center"
                    >
                        <Icon name="key" class="mx-auto size-8 text-ink-300" />
                        <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                            Aún no hay solicitudes con esos filtros.
                        </p>
                    </li>
                </ul>

                <Pagination
                    class="mt-4"
                    :links="requests.links"
                    :from="requests.from"
                    :to="requests.to"
                    :total="requests.total"
                />
            </Card>
        </div>

        <aside class="col-span-12 space-y-6 xl:col-span-4">
            <QuotaCard :account="account" />

            <Card title="¿Cómo funciona?">
                <ol
                    class="list-decimal space-y-2 pl-4 text-sm text-ink-600 dark:text-ink-400"
                >
                    <li>
                        Registra la venta con los datos y documentos del
                        cliente.
                    </li>
                    <li>
                        Al enviarla a Uanataca se descuenta una firma de tu
                        cupo.
                    </li>
                    <li>
                        Sigue el estado aquí; si la solicitud es rechazada, la
                        firma vuelve a tu cupo.
                    </li>
                </ol>
            </Card>
        </aside>
    </GeneralLayout>
</template>
