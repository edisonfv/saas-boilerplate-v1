<script setup lang="ts">
import { destroy } from '@/actions/Modules/Central/Http/Controllers/Auth/AuthenticatedSessionController';
import AppShell from '@/components/AppShell.vue';
import { brand } from '@/lib/brand';
import central from '@/routes/central';
import type { NavigationSection } from '@/types/navigation';

defineProps<{
    title: string;
}>();

/**
 * Sidebar holds destinations only. Actions such as "Nuevo tenant" live as
 * buttons on their index pages, so one URL never maps to two menu items.
 */
const sections: NavigationSection[] = [
    {
        label: 'Inicio',
        items: [
            { name: 'Dashboard', href: central.dashboard().url, icon: 'grid' },
        ],
    },
    {
        label: 'Plataforma',
        items: [
            {
                // Also each tenant's signature distributor account (a tab).
                name: 'Tenants',
                href: central.tenants.index().url,
                icon: 'building',
                permission: 'central.tenants.view',
            },
        ],
    },
    {
        label: 'Firmas electrónicas',
        items: [
            {
                name: 'Ventas y utilidad',
                href: central.signatures.sales.index().url,
                icon: 'chart',
                permission: 'central.signature-sales.view',
            },
            {
                name: 'Productos y paquetes',
                href: central.signatures.products.index().url,
                icon: 'key',
                permission: 'central.signature-products.view',
            },
        ],
    },
    {
        label: 'Soporte',
        items: [
            {
                name: 'Tickets',
                href: central.support.tickets.index().url,
                icon: 'chat',
                permission: 'central.support-tickets.view',
            },
            {
                name: 'Agenda',
                href: central.support.schedule.index().url,
                icon: 'calendar',
                permission: 'central.support-schedule.view',
            },
            {
                name: 'Reportes',
                href: central.support.reports.index().url,
                icon: 'chart',
                permission: 'central.support-reports.view',
            },
        ],
    },
    {
        label: 'Catálogo',
        items: [
            {
                name: 'Planes',
                href: central.plans.index().url,
                icon: 'tag',
                permission: 'central.plans.view',
            },
            {
                name: 'Módulos',
                href: central.modules.index().url,
                icon: 'cube',
                permission: 'central.modules.view',
            },
            {
                name: 'Features',
                href: central.features.index().url,
                icon: 'layers',
                permission: 'central.features.view',
            },
            {
                name: 'Límites',
                href: central.limitTypes.index().url,
                icon: 'chart',
                permission: 'central.limit-types.view',
            },
        ],
    },
    {
        label: 'Control de acceso',
        items: [
            {
                name: 'Staff',
                href: central.staff.index().url,
                icon: 'users',
                permission: 'central.staff.view',
            },
            {
                name: 'Roles',
                href: central.roles.index().url,
                icon: 'shield',
                permission: 'central.roles.view',
            },
        ],
    },
];
</script>

<template>
    <AppShell
        :title="title"
        variant="central"
        :sections="sections"
        :home-href="central.dashboard().url"
        :logout-href="destroy().url"
        :profile-href="central.profile.edit().url"
        :caption="brand.consoleName"
        user-fallback="Staff central"
    >
        <template #header-actions>
            <slot name="header-actions" />
        </template>

        <slot />
    </AppShell>
</template>
