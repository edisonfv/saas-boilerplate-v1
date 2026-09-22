<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { destroy } from '@/actions/Modules/General/Http/Controllers/Auth/AuthenticatedSessionController';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import tenant from '@/routes/tenant';

defineProps<{
    title: string;
    tenantId?: string;
}>();

const page = usePage();
const authUser = computed(
    () => page.props.auth?.user as { name: string } | null,
);
const permissions = computed(
    () => (page.props.auth?.permissions as string[] | undefined) ?? [],
);
const canManageUsers = computed(() =>
    permissions.value.includes('tenant.users.view'),
);
const canManageRoles = computed(() =>
    permissions.value.includes('tenant.roles.view'),
);
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950">
        <header
            class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white"
                    >
                        S
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{
                        title
                    }}</span>
                </div>

                <nav
                    v-if="canManageUsers || canManageRoles"
                    class="hidden items-center gap-1 sm:flex"
                >
                    <Link
                        v-if="canManageUsers"
                        :href="tenant.users.index().url"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        Usuarios
                    </Link>
                    <Link
                        v-if="canManageRoles"
                        :href="tenant.roles.index().url"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        Roles
                    </Link>
                </nav>

                <div class="flex items-center gap-3">
                    <span
                        v-if="tenantId"
                        class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                        tenant: {{ tenantId }}
                    </span>

                    <AppearanceToggle />

                    <div v-if="authUser" class="flex items-center gap-3">
                        <span
                            class="text-sm text-gray-600 dark:text-gray-400"
                            >{{ authUser.name }}</span
                        >
                        <Link
                            :href="destroy().url"
                            method="post"
                            as="button"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            Salir
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl p-6">
            <slot />
        </main>
    </div>
</template>
