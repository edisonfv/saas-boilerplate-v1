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
