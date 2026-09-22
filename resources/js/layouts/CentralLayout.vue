<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { destroy } from '@/actions/Modules/Central/Http/Controllers/Auth/AuthenticatedSessionController';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import Icon from '@/components/Icon.vue';
import central from '@/routes/central';
import type { IconName } from '@/types/icon';

defineProps<{
    title: string;
}>();

interface NavigationItem {
    name: string;
    href: string;
    icon: IconName;
    permission?: string;
}

interface NavigationSection {
    label: string;
    items: NavigationItem[];
}

const page = usePage();
const authUser = computed(
    () => page.props.auth?.user as { name: string; email?: string } | null,
);
const permissions = computed(
    () => (page.props.auth?.permissions as string[] | undefined) ?? [],
);
const can = (permission?: string) =>
    !permission || permissions.value.includes(permission);
const isSidebarCollapsed = ref(false);
const isMobileNavigationOpen = ref(false);
const isUserMenuOpen = ref(false);
const sidebarStorageKey = 'central-sidebar-collapsed';

const allNavigationSections: NavigationSection[] = [
    {
        label: 'Inicio',
        items: [
            { name: 'Dashboard', href: central.dashboard().url, icon: 'grid' },
        ],
    },
    {
        label: 'Catalogo',
        items: [
            {
                name: 'Planes',
                href: central.plans.index().url,
                icon: 'tag',
                permission: 'central.plans.view',
            },
            {
                name: 'Modulos',
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
                name: 'Limites',
                href: central.limitTypes.index().url,
                icon: 'chart',
                permission: 'central.limit-types.view',
            },
        ],
    },
    {
        label: 'Plataforma',
        items: [
            {
                name: 'Tenants',
                href: central.tenants.index().url,
                icon: 'building',
                permission: 'central.tenants.view',
            },
            {
                name: 'Nuevo tenant',
                href: central.tenants.create().url,
                icon: 'globe',
                permission: 'central.tenants.create',
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

const navigationSections = computed(() =>
    allNavigationSections
        .map((section) => ({
            ...section,
            items: section.items.filter((item) => can(item.permission)),
        }))
        .filter((section) => section.items.length > 0),
);

const currentPath = computed(() => page.url.split('?')[0]);
const userInitials = computed(() => {
    if (!authUser.value?.name) {
        return 'SC';
    }

    return authUser.value.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
});

const collapseLabel = computed(() =>
    isSidebarCollapsed.value ? 'Expandir sidebar' : 'Ocultar sidebar',
);

const sidebarIcon = computed<IconName>(() =>
    isSidebarCollapsed.value ? 'arrow-right' : 'arrow-left',
);

onMounted(() => {
    isSidebarCollapsed.value =
        localStorage.getItem(sidebarStorageKey) === 'true';
});

watch(isSidebarCollapsed, (value) => {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(sidebarStorageKey, String(value));
});

function isActive(href: string): boolean {
    return href === central.dashboard().url
        ? currentPath.value === href
        : currentPath.value.startsWith(href);
}

function closeMobileNavigation(): void {
    isMobileNavigationOpen.value = false;
}

function toggleSidebar(): void {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    isUserMenuOpen.value = false;
}

function toggleUserMenu(): void {
    isUserMenuOpen.value = !isUserMenuOpen.value;
}
</script>

<template>
    <div
        class="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-slate-200 bg-white shadow-sm shadow-slate-950/5 transition-[width] duration-200 lg:flex dark:border-slate-800 dark:bg-slate-900',
                isSidebarCollapsed ? 'w-[4.5rem]' : 'w-72',
            ]"
        >
            <div
                :class="[
                    'flex h-full flex-col',
                    isSidebarCollapsed ? 'px-3 py-4' : 'px-4 py-5',
                ]"
            >
                <div
                    :class="[
                        'flex items-center',
                        isSidebarCollapsed
                            ? 'justify-center'
                            : 'justify-between gap-3',
                    ]"
                >
                    <div
                        :class="[
                            'flex min-w-0 items-center',
                            isSidebarCollapsed ? 'justify-center' : 'gap-3',
                        ]"
                    >
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-xs font-semibold text-white shadow-sm dark:bg-white dark:text-slate-950"
                        >
                            SC
                        </div>
                        <div v-if="!isSidebarCollapsed" class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-slate-950 dark:text-white"
                            >
                                SaaS Central
                            </p>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-slate-400"
                            >
                                Operacion multi-tenant
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="!isSidebarCollapsed"
                        type="button"
                        class="flex size-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-950 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800 dark:hover:text-white"
                        :aria-label="collapseLabel"
                        :title="collapseLabel"
                        :aria-pressed="isSidebarCollapsed"
                        @click.stop.prevent="toggleSidebar"
                    >
                        <Icon :name="sidebarIcon" class="size-4.5" />
                    </button>
                </div>

                <button
                    v-if="isSidebarCollapsed"
                    type="button"
                    class="mt-4 flex size-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-950 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800 dark:hover:text-white"
                    :aria-label="collapseLabel"
                    :title="collapseLabel"
                    :aria-pressed="isSidebarCollapsed"
                    @click.stop.prevent="toggleSidebar"
                >
                    <Icon :name="sidebarIcon" class="size-4.5" />
                </button>

                <nav
                    :class="[
                        'mt-8 flex-1 overflow-x-hidden overflow-y-auto',
                        isSidebarCollapsed ? 'space-y-4' : 'space-y-7 pr-1',
                    ]"
                    aria-label="Navegacion central"
                >
                    <section
                        v-for="section in navigationSections"
                        :key="section.label"
                        :class="isSidebarCollapsed ? 'space-y-2' : 'space-y-3'"
                    >
                        <div
                            v-if="isSidebarCollapsed"
                            class="mx-auto h-px w-8 bg-slate-200 first:hidden dark:bg-slate-800"
                        />
                        <p
                            v-else
                            class="px-3 text-[11px] font-semibold text-slate-400 uppercase dark:text-slate-500"
                        >
                            {{ section.label }}
                        </p>

                        <div class="space-y-1">
                            <Link
                                v-for="item in section.items"
                                :key="item.name"
                                :href="item.href"
                                :title="
                                    isSidebarCollapsed ? item.name : undefined
                                "
                                :aria-label="item.name"
                                :class="[
                                    'group relative flex h-11 items-center rounded-lg text-sm font-medium transition-colors',
                                    isSidebarCollapsed
                                        ? 'justify-center px-0'
                                        : 'gap-3 px-3',
                                    isActive(item.href)
                                        ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-500/15 dark:text-blue-200 dark:ring-blue-400/20'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
                                ]"
                            >
                                <span
                                    v-if="isActive(item.href)"
                                    :class="[
                                        'absolute rounded-full bg-blue-600 dark:bg-blue-300',
                                        isSidebarCollapsed
                                            ? 'left-1 h-5 w-1'
                                            : 'left-0 h-6 w-1',
                                    ]"
                                />
                                <Icon
                                    :name="item.icon"
                                    class="size-4.5 shrink-0"
                                />
                                <span
                                    v-if="!isSidebarCollapsed"
                                    class="truncate"
                                >
                                    {{ item.name }}
                                </span>
                            </Link>
                        </div>
                    </section>
                </nav>

                <div v-if="authUser" class="relative pt-5">
                    <div
                        v-if="isUserMenuOpen"
                        :class="[
                            'absolute bottom-full mb-2 rounded-lg border border-slate-200 bg-white p-2 shadow-xl shadow-slate-950/10 dark:border-slate-800 dark:bg-slate-900',
                            isSidebarCollapsed
                                ? 'left-full ml-3 w-72'
                                : 'right-0 left-0',
                        ]"
                    >
                        <div
                            class="flex items-center gap-3 border-b border-slate-100 px-2 py-2.5 dark:border-slate-800"
                        >
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-200"
                            >
                                {{ userInitials }}
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-medium text-slate-950 dark:text-white"
                                >
                                    {{ authUser.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-slate-500 dark:text-slate-400"
                                >
                                    {{ authUser.email ?? 'Staff central' }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-1 pt-2">
                            <Link
                                :href="central.profile.edit().url"
                                class="flex h-9 items-center gap-3 rounded-lg px-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <Icon name="settings" class="size-4.5" />
                                Ajustes
                            </Link>
                            <Link
                                :href="destroy().url"
                                method="post"
                                as="button"
                                class="flex h-9 w-full items-center gap-3 rounded-lg px-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <Icon name="log-out" class="size-4.5" />
                                Cerrar sesion
                            </Link>
                        </div>
                    </div>

                    <button
                        type="button"
                        :class="[
                            'flex w-full items-center rounded-lg border border-slate-200 bg-slate-50 text-left transition hover:border-slate-300 hover:bg-white dark:border-slate-800 dark:bg-slate-800/60 dark:hover:border-slate-700 dark:hover:bg-slate-800',
                            isSidebarCollapsed
                                ? 'justify-center p-2'
                                : 'gap-3 p-3',
                        ]"
                        :aria-expanded="isUserMenuOpen"
                        :title="isSidebarCollapsed ? authUser.name : undefined"
                        @click="toggleUserMenu"
                    >
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-xs font-semibold text-white"
                        >
                            {{ userInitials }}
                        </div>
                        <div v-if="!isSidebarCollapsed" class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-medium text-slate-950 dark:text-white"
                            >
                                {{ authUser.name }}
                            </p>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{ authUser.email ?? 'Staff central' }}
                            </p>
                        </div>
                        <Icon
                            v-if="!isSidebarCollapsed"
                            name="chevrons-up-down"
                            class="size-4 text-slate-500"
                        />
                    </button>
                </div>
            </div>
        </aside>

        <div
            v-if="isMobileNavigationOpen"
            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
            @click="closeMobileNavigation"
        />

        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-80 max-w-[calc(100vw-2rem)] flex-col border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:hidden dark:border-slate-800 dark:bg-slate-900',
                isMobileNavigationOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
            aria-label="Navegacion movil central"
        >
            <div class="flex h-full flex-col px-4 py-5">
                <div class="flex items-center justify-between gap-3 pb-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-xs font-semibold text-white dark:bg-white dark:text-slate-950"
                        >
                            SC
                        </div>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-slate-950 dark:text-white"
                            >
                                SaaS Central
                            </p>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-slate-400"
                            >
                                Operacion multi-tenant
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-950 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800 dark:hover:text-white"
                        aria-label="Cerrar navegacion"
                        @click="closeMobileNavigation"
                    >
                        <Icon name="x-mark" class="size-4.5" />
                    </button>
                </div>

                <nav class="flex-1 space-y-7 overflow-y-auto pr-1">
                    <section
                        v-for="section in navigationSections"
                        :key="section.label"
                        class="space-y-3"
                    >
                        <p
                            class="px-3 text-[11px] font-semibold text-slate-400 uppercase dark:text-slate-500"
                        >
                            {{ section.label }}
                        </p>
                        <div class="space-y-1">
                            <Link
                                v-for="item in section.items"
                                :key="item.name"
                                :href="item.href"
                                :class="[
                                    'relative flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors',
                                    isActive(item.href)
                                        ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-500/15 dark:text-blue-200 dark:ring-blue-400/20'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
                                ]"
                                @click="closeMobileNavigation"
                            >
                                <span
                                    v-if="isActive(item.href)"
                                    class="absolute left-0 h-6 w-1 rounded-full bg-blue-600 dark:bg-blue-300"
                                />
                                <Icon
                                    :name="item.icon"
                                    class="size-4.5 shrink-0"
                                />
                                <span class="truncate">{{ item.name }}</span>
                            </Link>
                        </div>
                    </section>
                </nav>
            </div>
        </aside>

        <div
            :class="[
                'flex min-h-screen w-full flex-col transition-[padding] duration-200',
                isSidebarCollapsed ? 'lg:pl-[4.5rem]' : 'lg:pl-72',
            ]"
        >
            <header
                class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur md:px-6 dark:border-slate-800 dark:bg-slate-900/95"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-950 lg:hidden dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800 dark:hover:text-white"
                        aria-label="Abrir navegacion"
                        @click="isMobileNavigationOpen = true"
                    >
                        <Icon name="menu" class="size-4.5" />
                    </button>

                    <div class="min-w-0">
                        <h1
                            class="truncate text-base font-semibold text-slate-950 md:text-lg dark:text-white"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="mt-0.5 hidden truncate text-xs text-slate-500 sm:block dark:text-slate-400"
                        >
                            Panel central de administracion
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <AppearanceToggle />
                </div>
            </header>

            <main class="min-w-0 flex-1 px-4 py-6 md:px-6 xl:px-8">
                <div class="w-full">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
