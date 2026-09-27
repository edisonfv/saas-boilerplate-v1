<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { destroy } from '@/actions/Modules/General/Http/Controllers/Auth/AuthenticatedSessionController';
import AppShell from '@/components/AppShell.vue';
import { brand } from '@/lib/brand';
import tenant from '@/routes/tenant';
import type { NavigationSection } from '@/types/navigation';

defineProps<{
    title: string;
}>();

const page = usePage();

/** Tenant workspaces show the customer's company, not the platform name. */
const caption = computed(
    () =>
        (page.props.tenant as { name: string } | null)?.name ??
        brand.workspaceName,
);

const sections: NavigationSection[] = [
    {
        label: 'Inicio',
        items: [
            { name: 'Dashboard', href: tenant.dashboard().url, icon: 'grid' },
        ],
    },
    {
        label: 'Ventas',
        items: [
            {
                name: 'Firmas electrónicas',
                href: tenant.signatures.requests.index().url,
                icon: 'key',
                permission: 'tenant.signature-requests.view',
                module: 'signatures',
            },
            {
                name: 'Enlaces prepagados',
                href: tenant.signatures.invitations.index().url,
                icon: 'currency',
                permission: 'tenant.signature-requests.payments',
                module: 'signatures',
            },
            {
                name: 'Sitio web',
                href: tenant.signatures.storefront.edit().url,
                icon: 'globe',
                permission: 'tenant.signature-storefront.update',
                module: 'signatures',
            },
        ],
    },
    {
        label: 'Administración',
        items: [
            {
                name: 'Usuarios',
                href: tenant.users.index().url,
                icon: 'users',
                permission: 'tenant.users.view',
            },
            {
                name: 'Roles',
                href: tenant.roles.index().url,
                icon: 'shield',
                permission: 'tenant.roles.view',
            },
        ],
    },
    {
        label: 'Ayuda',
        items: [
            {
                name: 'Soporte',
                href: tenant.support.tickets.index().url,
                icon: 'chat',
                permission: 'tenant.support-tickets.view',
                module: 'support',
            },
        ],
    },
];
</script>

<template>
    <AppShell
        :title="title"
        variant="tenant"
        :sections="sections"
        :home-href="tenant.dashboard().url"
        :logout-href="destroy().url"
        :caption="caption"
        user-fallback="Usuario"
    >
        <template #header-actions>
            <slot name="header-actions" />
        </template>

        <slot />
    </AppShell>
</template>
