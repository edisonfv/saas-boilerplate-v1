<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    renewSubscription,
    toggleStatus,
} from '@/actions/Modules/Central/Http/Controllers/TenantController';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import StatCard from '@/components/StatCard.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { subscriptionStatusTone } from '@/lib/status';
import central from '@/routes/central';

interface Tenant {
    id: string;
    company_name: string | null;
    legal_name: string | null;
    tax_identifier: string | null;
    country_code: string | null;
    timezone: string;
    primary_contact_name: string | null;
    primary_contact_email: string | null;
    status: string;
    status_label: string;
    domain: string | null;
    created_at: string;
}

interface Subscription {
    plan_name: string;
    billing_period_label: string;
    price: string | null;
    currency: string | null;
    status: string;
    status_label: string;
    trial_ends_at: string | null;
    current_period_start: string | null;
    current_period_end: string | null;
    grants_access: boolean;
}

interface Entitlements {
    modules: string[];
    features: string[];
    limits: Record<string, number>;
}

const props = defineProps<{
    can: {
        impersonate: boolean;
        manage: boolean;
    };
    tenant: Tenant;
    subscription: Subscription | null;
    entitlements: Entitlements | null;
}>();

const isActive = computed(() => props.tenant.status === 'Active');
</script>

<template>
    <Head :title="`Tenant: ${tenant.company_name ?? tenant.id}`" />

    <CentralLayout :title="`Tenant: ${tenant.id}`">
        <div class="space-y-6">
            <Link
                :href="central.tenants.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a tenants
            </Link>

            <section
                class="rounded-lg border border-ink-200 bg-white p-5 shadow-sm dark:border-ink-800 dark:bg-ink-900"
            >
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="text-xl font-semibold text-ink-950 dark:text-white"
                            >
                                {{ tenant.company_name ?? tenant.id }}
                            </h2>
                            <Badge :tone="isActive ? 'green' : 'red'">
                                {{ tenant.status_label }}
                            </Badge>
                            <Badge
                                v-if="subscription"
                                :tone="
                                    subscriptionStatusTone(subscription.status)
                                "
                            >
                                {{ subscription.status_label }}
                            </Badge>
                            <Badge v-else>Sin suscripción</Badge>
                        </div>
                        <p class="mt-2 text-sm text-ink-500">
                            {{ tenant.domain ?? 'Sin dominio registrado' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Form
                            autocomplete="off"
                            v-if="can.manage"
                            v-bind="toggleStatus.form(tenant.id)"
                            #default="{ processing }"
                        >
                            <button
                                type="submit"
                                :disabled="processing"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-ink-300 px-3.5 text-sm font-medium text-ink-700 transition hover:bg-ink-50 disabled:opacity-50 dark:border-ink-700 dark:text-ink-200 dark:hover:bg-ink-800"
                            >
                                <Icon name="power" class="size-4.5" />
                                {{ isActive ? 'Suspender' : 'Reactivar' }}
                            </button>
                        </Form>

                        <div
                            class="rounded-lg border border-ink-200 px-4 py-3 text-sm dark:border-ink-800"
                        >
                            <p class="text-ink-500 dark:text-ink-400">Creado</p>
                            <p
                                class="mt-1 font-semibold text-ink-950 dark:text-white"
                            >
                                {{ tenant.created_at }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Módulos activos"
                    :value="entitlements?.modules.length ?? 0"
                    icon="cube"
                    helper="Segun entitlements"
                />
                <StatCard
                    label="Features"
                    :value="entitlements?.features.length ?? 0"
                    icon="layers"
                    helper="Habilitadas por plan"
                />
                <StatCard
                    label="Límites"
                    :value="
                        entitlements
                            ? Object.keys(entitlements.limits).length
                            : 0
                    "
                    icon="chart"
                    helper="Controles de uso"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card title="Empresa">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">ID</dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{ tenant.id }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">
                                Razón social
                            </dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{ tenant.legal_name ?? 'Sin registrar' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">
                                Identificación fiscal
                            </dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{ tenant.tax_identifier ?? 'Sin registrar' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">País</dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{ tenant.country_code ?? 'Sin registrar' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">
                                Zona horaria
                            </dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{ tenant.timezone }}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Contacto">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">
                                Owner
                            </dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{
                                    tenant.primary_contact_name ??
                                    'Sin registrar'
                                }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">
                                Email
                            </dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{
                                    tenant.primary_contact_email ??
                                    'Sin registrar'
                                }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500 dark:text-ink-400">
                                Dominio
                            </dt>
                            <dd
                                class="text-right font-medium text-ink-950 dark:text-white"
                            >
                                {{ tenant.domain ?? 'Sin dominio' }}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Suscripción">
                    <template v-if="subscription">
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Plan
                                </dt>
                                <dd
                                    class="text-right font-medium text-ink-950 dark:text-white"
                                >
                                    {{ subscription.plan_name }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Periodo
                                </dt>
                                <dd
                                    class="text-right font-medium text-ink-950 dark:text-white"
                                >
                                    {{ subscription.billing_period_label }}
                                </dd>
                            </div>
                            <div
                                v-if="subscription.price !== null"
                                class="flex items-start justify-between gap-4"
                            >
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Precio contratado
                                </dt>
                                <dd
                                    class="text-right font-medium text-ink-950 dark:text-white"
                                >
                                    {{ subscription.price }}
                                    {{ subscription.currency }}
                                </dd>
                            </div>
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Estado
                                </dt>
                                <dd>
                                    <Badge
                                        :tone="
                                            subscriptionStatusTone(
                                                subscription.status,
                                            )
                                        "
                                    >
                                        {{ subscription.status_label }}
                                    </Badge>
                                </dd>
                            </div>
                            <div
                                v-if="subscription.trial_ends_at"
                                class="flex items-start justify-between gap-4"
                            >
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Trial hasta
                                </dt>
                                <dd
                                    class="text-right font-medium text-ink-950 dark:text-white"
                                >
                                    {{ subscription.trial_ends_at }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Periodo actual
                                </dt>
                                <dd
                                    class="text-right font-medium text-ink-950 dark:text-white"
                                >
                                    {{ subscription.current_period_start }} -
                                    {{ subscription.current_period_end }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            v-if="!subscription.grants_access"
                            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                        >
                            La suscripción está vencida: el espacio del tenant
                            está inhabilitado hasta que se renueve.
                        </div>

                        <Form
                            v-if="can.manage"
                            v-bind="renewSubscription.form(tenant.id)"
                            #default="{ processing }"
                            class="mt-4"
                        >
                            <button
                                type="submit"
                                :disabled="processing"
                                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50"
                            >
                                Renovar periodo
                            </button>
                        </Form>
                    </template>
                    <p v-else class="text-sm text-ink-400">
                        Este tenant no tiene ninguna suscripción todavia.
                    </p>
                </Card>
            </div>

            <Card title="Entitlements activos">
                <template v-if="entitlements">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div>
                            <p
                                class="mb-2 text-xs font-semibold text-ink-500 uppercase dark:text-ink-400"
                            >
                                Módulos
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="module in entitlements.modules"
                                    :key="module"
                                    tone="blue"
                                >
                                    {{ module }}
                                </Badge>
                                <span
                                    v-if="entitlements.modules.length === 0"
                                    class="text-sm text-ink-400"
                                >
                                    Ninguno
                                </span>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 text-xs font-semibold text-ink-500 uppercase dark:text-ink-400"
                            >
                                Features
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="feature in entitlements.features"
                                    :key="feature"
                                >
                                    {{ feature }}
                                </Badge>
                                <span
                                    v-if="entitlements.features.length === 0"
                                    class="text-sm text-ink-400"
                                >
                                    Ninguna
                                </span>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 text-xs font-semibold text-ink-500 uppercase dark:text-ink-400"
                            >
                                Límites
                            </p>
                            <ul class="space-y-2">
                                <li
                                    v-for="(value, key) in entitlements.limits"
                                    :key="key"
                                    class="flex justify-between gap-4 text-sm"
                                >
                                    <span
                                        class="text-ink-700 dark:text-ink-300"
                                    >
                                        {{ key }}
                                    </span>
                                    <span
                                        class="font-medium text-ink-950 dark:text-white"
                                    >
                                        {{ value }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>
                <p v-else class="text-sm text-ink-400">
                    Sin entitlements porque no hay suscripción activa.
                </p>
            </Card>
        </div>
    </CentralLayout>
</template>
