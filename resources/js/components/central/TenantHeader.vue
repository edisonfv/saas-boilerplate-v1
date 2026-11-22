<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Badge from '@/components/Badge.vue';
import Icon from '@/components/Icon.vue';
import { usePermissions } from '@/composables/usePermissions';
import { subscriptionStatusTone } from '@/lib/status';
import central from '@/routes/central';

/** Shape of App\Services\TenantPresenter::header(). */
export interface TenantHeaderData {
    id: string;
    company_name: string | null;
    status: string;
    status_label: string;
    domain: string | null;
    subscription_status: string | null;
    subscription_status_label: string | null;
    created_at: string;
}

/**
 * Identity and tabs shared by every page of one tenant, so its account,
 * subscription and signature distribution live in a single place.
 */
const props = defineProps<{
    tenant: TenantHeaderData;
    active: 'summary' | 'signatures';
}>();

const { can } = usePermissions();

const tabs = computed(() =>
    [
        {
            key: 'summary',
            label: 'Resumen',
            icon: 'building' as const,
            href: central.tenants.show(props.tenant.id).url,
            visible: can('central.tenants.view'),
        },
        {
            key: 'signatures',
            label: 'Firmas electrónicas',
            icon: 'key' as const,
            href: central.tenants.signatures(props.tenant.id).url,
            visible: can('central.signature-accounts.view'),
        },
    ].filter((tab) => tab.visible),
);
</script>

<template>
    <div class="space-y-4">
        <Link
            v-if="can('central.tenants.view')"
            :href="central.tenants.index().url"
            class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
        >
            <Icon name="arrow-left" />
            Volver a tenants
        </Link>

        <section
            class="rounded-xl border border-ink-200 bg-white shadow-sm dark:border-ink-800 dark:bg-ink-900"
        >
            <div
                class="flex flex-col gap-4 p-5 md:flex-row md:items-start md:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2
                            class="text-xl font-semibold text-ink-950 dark:text-white"
                        >
                            {{ tenant.company_name ?? tenant.id }}
                        </h2>
                        <Badge
                            :tone="tenant.status === 'Active' ? 'green' : 'red'"
                        >
                            {{ tenant.status_label }}
                        </Badge>
                        <Badge
                            v-if="tenant.subscription_status_label"
                            :tone="
                                subscriptionStatusTone(
                                    tenant.subscription_status,
                                )
                            "
                        >
                            {{ tenant.subscription_status_label }}
                        </Badge>
                        <Badge v-else>Sin suscripción</Badge>
                    </div>
                    <p class="mt-2 text-sm text-ink-500">
                        {{ tenant.id }} ·
                        {{ tenant.domain ?? 'Sin dominio registrado' }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <slot name="actions" />
                </div>
            </div>

            <nav
                v-if="tabs.length > 1"
                class="flex gap-1 overflow-x-auto border-t border-ink-200 px-3 dark:border-ink-800"
                aria-label="Secciones del tenant"
            >
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="tab.href"
                    :aria-current="tab.key === active ? 'page' : undefined"
                    :class="[
                        'inline-flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-semibold whitespace-nowrap transition',
                        tab.key === active
                            ? 'border-primary-600 text-primary-700 dark:border-primary-400 dark:text-primary-300'
                            : 'border-transparent text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-white',
                    ]"
                >
                    <Icon :name="tab.icon" class="size-4" />
                    {{ tab.label }}
                </Link>
            </nav>
        </section>
    </div>
</template>
