<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { money } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

const props = defineProps<{
    tenants: {
        data: {
            id: string;
            company_name: string | null;
            domain: string | null;
            signatures_sold: number;
            account: {
                affiliation_mode: string;
                affiliation_mode_label: string;
                is_active: boolean;
                credit_limit: string;
                credit_used: string;
                available_units: number;
            } | null;
        }[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search: string };
    stats: {
        accounts: number;
        credit: number;
        prepaid: number;
        sold_this_month: number;
    };
}>();

const search = ref(props.filters.search);

function applySearch() {
    router.get(
        central.signatures.accounts.index().url,
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Cuentas de firmas" />

    <CentralLayout title="Cuentas de firmas">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Tenants afiliados"
                    :value="stats.accounts"
                    icon="building"
                />
                <StatCard
                    label="En crédito"
                    :value="stats.credit"
                    icon="currency"
                />
                <StatCard label="Prepago" :value="stats.prepaid" icon="tag" />
                <StatCard
                    label="Firmas vendidas este mes"
                    :value="stats.sold_this_month"
                    icon="key"
                />
            </div>

            <Card>
                <form class="mb-4" @submit.prevent="applySearch">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar tenant"
                        :class="ui.input"
                    />
                </form>

                <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                    <li v-for="tenant in tenants.data" :key="tenant.id">
                        <Link
                            :href="
                                central.signatures.accounts.show(tenant.id).url
                            "
                            class="-mx-2 flex items-center gap-4 rounded-lg px-2 py-3.5 transition hover:bg-ink-50 dark:hover:bg-ink-800/40"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate font-semibold text-ink-950 dark:text-white"
                                >
                                    {{ tenant.company_name ?? tenant.id }}
                                </p>
                                <p class="text-xs text-ink-500">
                                    {{ tenant.domain ?? tenant.id }} ·
                                    {{ tenant.signatures_sold }} vendidas
                                </p>
                            </div>
                            <template v-if="tenant.account">
                                <span
                                    class="hidden text-sm text-ink-600 tabular-nums sm:block dark:text-ink-400"
                                >
                                    <template
                                        v-if="
                                            tenant.account.affiliation_mode ===
                                            'Credit'
                                        "
                                    >
                                        {{
                                            money(tenant.account.credit_used)
                                        }}
                                        /
                                        {{ money(tenant.account.credit_limit) }}
                                    </template>
                                    <template v-else>
                                        {{ tenant.account.available_units }}
                                        firmas
                                    </template>
                                </span>
                                <Badge
                                    :tone="
                                        !tenant.account.is_active
                                            ? 'red'
                                            : tenant.account
                                                    .affiliation_mode ===
                                                'Credit'
                                              ? 'blue'
                                              : 'green'
                                    "
                                >
                                    {{ tenant.account.affiliation_mode_label }}
                                </Badge>
                            </template>
                            <Badge v-else>Sin afiliar</Badge>
                            <Icon
                                name="chevron-right"
                                class="size-4 text-ink-400"
                            />
                        </Link>
                    </li>
                </ul>

                <Pagination
                    class="mt-4"
                    :links="tenants.links"
                    :from="tenants.from"
                    :to="tenants.to"
                    :total="tenants.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
