<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoMark from '@/components/AppLogoMark.vue';
import Icon from '@/components/Icon.vue';
import StatCard from '@/components/StatCard.vue';
import { usePermissions } from '@/composables/usePermissions';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { brand } from '@/lib/brand';
import central from '@/routes/central';
import type { IconName } from '@/types/icon';

defineProps<{
    stats: {
        plans: number;
        modules: number;
        tenants: number;
        centralRoles: number;
    };
}>();

const page = usePage();
const { can } = usePermissions();

const greeting = computed(() => {
    const name = (page.props.auth?.user as { name: string } | null)?.name;

    return name ? `Hola, ${name.split(' ')[0]}.` : 'Hola.';
});

interface QuickLink {
    title: string;
    description: string;
    href: string;
    icon: IconName;
    permission: string;
}

const allQuickLinks: QuickLink[] = [
    {
        title: 'Tenants',
        description: 'Suscripción, dominio y entitlements efectivos.',
        href: central.tenants.index().url,
        icon: 'building',
        permission: 'central.tenants.view',
    },
    {
        title: 'Planes comerciales',
        description: 'Precios, módulos, features y límites por paquete.',
        href: central.plans.index().url,
        icon: 'tag',
        permission: 'central.plans.view',
    },
    {
        title: 'Catálogo de módulos',
        description: 'Capacidades vendibles, add-ons y permisos base.',
        href: central.modules.index().url,
        icon: 'cube',
        permission: 'central.modules.view',
    },
];

const quickLinks = computed(() =>
    allQuickLinks.filter((link) => can(link.permission)),
);
</script>

<template>
    <Head title="Dashboard" />

    <CentralLayout title="Dashboard">
        <div class="space-y-6">
            <section
                class="relative overflow-hidden rounded-2xl bg-brand-night px-6 py-8 text-white shadow-xl shadow-ink-950/10 md:px-10 md:py-10"
            >
                <AppLogoMark
                    tone="light"
                    class="pointer-events-none absolute -top-10 -right-10 size-72 opacity-[0.07]"
                />
                <div
                    class="pointer-events-none absolute -bottom-24 left-1/3 size-72 rounded-full bg-primary-500/25 blur-3xl"
                />

                <div
                    class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between"
                >
                    <div class="max-w-2xl">
                        <p class="eyebrow text-accent-400">
                            {{ brand.consoleName }}
                        </p>
                        <h2
                            class="mt-3 text-2xl font-extrabold tracking-tight md:text-3xl"
                        >
                            {{ greeting }}
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-ink-300 md:text-base"
                        >
                            Controla el catálogo comercial, los tenants y el
                            acceso del staff desde una sola consola.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="can('central.tenants.create')"
                            :href="central.tenants.create().url"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-400"
                        >
                            <Icon name="plus" class="size-4.5" />
                            Nuevo tenant
                        </Link>
                        <Link
                            v-if="can('central.staff.create')"
                            :href="central.register().url"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-white/10 px-4 text-sm font-semibold text-white ring-1 ring-white/15 transition ring-inset hover:bg-white/15"
                        >
                            <Icon name="user" class="size-4.5" />
                            Nuevo staff
                        </Link>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Tenants"
                    :value="stats.tenants"
                    icon="building"
                    helper="Cuentas provisionadas"
                />
                <StatCard
                    label="Planes"
                    :value="stats.plans"
                    icon="tag"
                    helper="Paquetes comerciales"
                />
                <StatCard
                    label="Módulos"
                    :value="stats.modules"
                    icon="cube"
                    helper="Capacidades del catálogo"
                />
                <StatCard
                    label="Roles centrales"
                    :value="stats.centralRoles"
                    icon="shield"
                    helper="Acceso del staff"
                />
            </div>

            <section v-if="quickLinks.length" class="space-y-3">
                <h3 class="eyebrow text-ink-500 dark:text-ink-400">
                    Accesos rápidos
                </h3>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.title"
                        :href="link.href"
                        class="group rounded-xl border border-ink-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md dark:border-ink-800 dark:bg-ink-900 dark:hover:border-primary-500/40"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                            >
                                <Icon :name="link.icon" />
                            </div>
                            <Icon
                                name="arrow-right"
                                class="text-ink-300 transition group-hover:translate-x-0.5 group-hover:text-primary-600 dark:text-ink-600 dark:group-hover:text-primary-300"
                            />
                        </div>
                        <p class="mt-4 font-bold text-ink-950 dark:text-white">
                            {{ link.title }}
                        </p>
                        <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                            {{ link.description }}
                        </p>
                    </Link>
                </div>
            </section>
        </div>
    </CentralLayout>
</template>
