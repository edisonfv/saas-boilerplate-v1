<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import StatCard from '@/components/StatCard.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

defineProps<{
    stats: {
        plans: number;
        modules: number;
        tenants: number;
        centralRoles: number;
    };
}>();

const quickLinks = [
    {
        title: 'Planes comerciales',
        description: 'Precios, módulos, features y límites por paquete.',
        href: central.plans.index().url,
        icon: 'tag' as const,
    },
    {
        title: 'Catálogo de módulos',
        description: 'Capacidades vendibles, addons y permisos blueprint.',
        href: central.modules.index().url,
        icon: 'cube' as const,
    },
    {
        title: 'Tenants',
        description: 'Suscripción, dominio y entitlements efectivos.',
        href: central.tenants.index().url,
        icon: 'building' as const,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <CentralLayout title="Dashboard">
        <div class="space-y-6">
            <section
                class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between"
            >
                <div class="max-w-3xl">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Vista ejecutiva de plataforma
                    </p>
                    <h2
                        class="mt-1.5 text-2xl font-semibold tracking-normal text-slate-950 md:text-3xl dark:text-white"
                    >
                        Bienvenido al sistema central
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        Controla el catálogo comercial, los tenants y el acceso
                        del staff desde una sola consola.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="central.register().url"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <Icon name="plus" class="size-4.5" />
                        Nuevo staff
                    </Link>
                    <Link
                        :href="central.tenants.index().url"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-slate-200 px-3.5 text-sm font-medium text-slate-800 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                    >
                        <Icon name="building" class="size-4.5" />
                        Revisar tenants
                    </Link>
                    <Link
                        :href="central.plans.index().url"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <Icon name="tag" class="size-4.5" />
                        Gestionar planes
                    </Link>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                    label="Tenants"
                    :value="stats.tenants"
                    icon="building"
                    helper="Cuentas provisionadas"
                />
                <StatCard
                    label="Roles centrales"
                    :value="stats.centralRoles"
                    icon="shield"
                    helper="Acceso del staff"
                />
            </div>

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Link
                    v-for="link in quickLinks"
                    :key="link.title"
                    :href="link.href"
                    class="group rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-500/40"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300"
                        >
                            <Icon :name="link.icon" />
                        </div>
                        <Icon
                            name="arrow-right"
                            class="text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-blue-700 dark:group-hover:text-blue-300"
                        />
                    </div>
                    <p class="mt-4 font-medium text-slate-950 dark:text-white">
                        {{ link.title }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ link.description }}
                    </p>
                </Link>
            </section>
        </div>
    </CentralLayout>
</template>
