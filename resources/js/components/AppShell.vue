<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoMark from '@/components/AppLogoMark.vue';
import FlashToast from '@/components/FlashToast.vue';
import Icon from '@/components/Icon.vue';
import { useSidebar } from '@/composables/useSidebar';
import type {
    Breadcrumb,
    NavigationItem,
    NavigationSection,
} from '@/types/navigation';

/**
 * Shared authenticated chrome for every panel (central console and tenant
 * workspace): brand sidebar, top bar with breadcrumbs, user menu and flash
 * toasts. Panel layouts only describe their navigation and URLs.
 */
const props = defineProps<{
    title: string;
    sections: NavigationSection[];
    homeHref: string;
    logoutHref: string;
    profileHref?: string;
    /** Context under the logo, e.g. "Consola central" or the tenant name. */
    caption: string;
    /** User menu subtitle when the user has no email. */
    userFallback: string;
}>();

const page = usePage();
const {
    isCollapsed: isSidebarCollapsed,
    isRestored,
    restore,
    toggle: toggleSidebar,
} = useSidebar();
const isMobileNavigationOpen = ref(false);
const isUserMenuOpen = ref(false);
const userMenuRoot = ref<HTMLElement | null>(null);

const authUser = computed(
    () => page.props.auth?.user as { name: string; email?: string } | null,
);
const permissions = computed(
    () => (page.props.auth?.permissions as string[] | undefined) ?? [],
);
const tenantModules = computed(
    () => (page.props.tenant as { modules?: string[] } | null)?.modules ?? [],
);

const visibleSections = computed(() =>
    props.sections
        .map((section) => ({
            ...section,
            items: section.items.filter(
                (item) =>
                    (!item.permission ||
                        permissions.value.includes(item.permission)) &&
                    (!item.module || tenantModules.value.includes(item.module)),
            ),
        }))
        .filter((section) => section.items.length > 0),
);

const currentPath = computed(() => page.url.split('?')[0].replace(/\/$/, ''));

function pathOf(href: string): string {
    return new URL(href, 'http://localhost').pathname.replace(/\/$/, '');
}

/**
 * Only the most specific matching item is active, and matches respect path
 * segments — so "/tenants/create" doesn't also light up "/tenants", and
 * "/plans" never matches "/plans-archive".
 */
const activeItem = computed<NavigationItem | null>(() => {
    let best: NavigationItem | null = null;
    let bestLength = -1;

    for (const section of visibleSections.value) {
        for (const item of section.items) {
            const itemPath = pathOf(item.href);
            // The home item is the panel's root prefix ("/central"), so it
            // must match exactly or it would light up on every page.
            const isHome = itemPath === pathOf(props.homeHref);
            const matches =
                currentPath.value === itemPath ||
                (!isHome &&
                    itemPath !== '' &&
                    currentPath.value.startsWith(`${itemPath}/`));

            if (matches && itemPath.length > bestLength) {
                best = item;
                bestLength = itemPath.length;
            }
        }
    }

    return best;
});

const activeSection = computed(() =>
    visibleSections.value.find((section) =>
        section.items.some((item) => item === activeItem.value),
    ),
);

const breadcrumbs = computed<Breadcrumb[]>(() => {
    const crumbs: Breadcrumb[] = [];
    const item = activeItem.value;

    if (!item) {
        return crumbs;
    }

    const isOnItemPage = currentPath.value === pathOf(item.href);

    // The home page needs no trail back to itself.
    if (isOnItemPage && pathOf(item.href) === pathOf(props.homeHref)) {
        return crumbs;
    }

    if (activeSection.value && activeSection.value.label !== item.name) {
        crumbs.push({ label: activeSection.value.label });
    }

    crumbs.push({
        label: item.name,
        href: isOnItemPage ? undefined : item.href,
    });

    if (!isOnItemPage && props.title !== item.name) {
        crumbs.push({ label: props.title });
    }

    return crumbs;
});

const userInitials = computed(() =>
    (authUser.value?.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join(''),
);

const collapseLabel = computed(() =>
    isSidebarCollapsed.value ? 'Expandir menú' : 'Contraer menú',
);

function isActive(item: NavigationItem): boolean {
    return activeItem.value === item;
}

function closeMenus(): void {
    isMobileNavigationOpen.value = false;
    isUserMenuOpen.value = false;
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeMenus();
    }
}

function handlePointerDown(event: PointerEvent): void {
    if (
        isUserMenuOpen.value &&
        userMenuRoot.value &&
        !userMenuRoot.value.contains(event.target as Node)
    ) {
        isUserMenuOpen.value = false;
    }
}

// Lock page scroll behind the mobile drawer.
watch(isMobileNavigationOpen, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

let removeNavigateListener: (() => void) | undefined;

onMounted(() => {
    restore();
    document.addEventListener('keydown', handleKeydown);
    document.addEventListener('pointerdown', handlePointerDown);
    removeNavigateListener = router.on('navigate', closeMenus);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('pointerdown', handlePointerDown);
    removeNavigateListener?.();
    document.body.style.overflow = '';
});
</script>

<template>
    <div
        class="min-h-screen bg-ink-50 text-ink-950 dark:bg-ink-950 dark:text-white"
    >
        <a
            href="#main-content"
            class="sr-only z-[70] rounded-lg bg-white px-4 py-2 text-sm font-semibold text-ink-950 shadow-lg focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
        >
            Saltar al contenido
        </a>

        <!-- Desktop sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 hidden flex-col overflow-hidden bg-brand-night text-ink-200 lg:flex dark:border-r dark:border-white/5',
                isRestored && 'transition-[width] duration-200',
                isSidebarCollapsed ? 'w-[4.75rem]' : 'w-72',
            ]"
        >
            <!-- Decorative open-hexagon watermark -->
            <AppLogoMark
                tone="light"
                class="pointer-events-none absolute -right-16 -bottom-14 size-64 opacity-[0.04]"
            />

            <div
                :class="[
                    'relative flex h-full flex-col',
                    isSidebarCollapsed ? 'px-3 py-5' : 'px-4 py-5',
                ]"
            >
                <div
                    :class="[
                        'flex h-10 items-center',
                        isSidebarCollapsed
                            ? 'justify-center'
                            : 'justify-between gap-2 px-1',
                    ]"
                >
                    <Link
                        :href="homeHref"
                        class="min-w-0 rounded-lg"
                        :aria-label="`Ir al inicio · ${caption}`"
                    >
                        <AppLogoMark
                            v-if="isSidebarCollapsed"
                            tone="light"
                            class="size-9"
                        />
                        <AppLogo
                            v-else
                            tone="light"
                            size="sm"
                            :caption="caption"
                        />
                    </Link>

                    <button
                        v-if="!isSidebarCollapsed"
                        type="button"
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg text-ink-400 transition hover:bg-white/10 hover:text-white"
                        :aria-label="collapseLabel"
                        :title="collapseLabel"
                        :aria-expanded="!isSidebarCollapsed"
                        @click="toggleSidebar"
                    >
                        <Icon name="arrow-left" class="size-4" />
                    </button>
                </div>

                <button
                    v-if="isSidebarCollapsed"
                    type="button"
                    class="mx-auto mt-4 flex size-9 items-center justify-center rounded-lg text-ink-400 transition hover:bg-white/10 hover:text-white"
                    :aria-label="collapseLabel"
                    :title="collapseLabel"
                    :aria-expanded="!isSidebarCollapsed"
                    @click="toggleSidebar"
                >
                    <Icon name="arrow-right" class="size-4" />
                </button>

                <nav
                    :class="[
                        'mt-8 flex-1 overflow-x-hidden overflow-y-auto',
                        isSidebarCollapsed ? 'space-y-4' : 'space-y-6',
                    ]"
                    aria-label="Navegación principal"
                >
                    <section
                        v-for="section in visibleSections"
                        :key="section.label"
                        class="space-y-1"
                    >
                        <div
                            v-if="isSidebarCollapsed"
                            class="mx-auto mb-2 h-px w-6 bg-white/10"
                        />
                        <p v-else class="mb-2 px-3 eyebrow text-ink-400">
                            {{ section.label }}
                        </p>

                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            :title="isSidebarCollapsed ? item.name : undefined"
                            :aria-current="isActive(item) ? 'page' : undefined"
                            :class="[
                                'group relative flex h-10 items-center rounded-lg text-sm font-semibold transition-colors',
                                isSidebarCollapsed
                                    ? 'justify-center'
                                    : 'gap-3 px-3',
                                isActive(item)
                                    ? 'bg-white/10 text-white'
                                    : 'text-ink-300 hover:bg-white/5 hover:text-white',
                            ]"
                        >
                            <span
                                v-if="isActive(item)"
                                class="absolute top-1/2 left-0 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-accent-500"
                            />
                            <Icon
                                :name="item.icon"
                                :class="[
                                    'size-[1.125rem] shrink-0',
                                    isActive(item)
                                        ? 'text-primary-300'
                                        : 'text-ink-400 group-hover:text-ink-200',
                                ]"
                            />
                            <span v-if="isSidebarCollapsed" class="sr-only">
                                {{ item.name }}
                            </span>
                            <span v-else class="truncate">{{ item.name }}</span>
                        </Link>
                    </section>
                </nav>

                <div v-if="authUser" ref="userMenuRoot" class="relative pt-4">
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="translate-y-1 opacity-0"
                        leave-active-class="transition duration-100 ease-in"
                        leave-to-class="translate-y-1 opacity-0"
                    >
                        <div
                            v-if="isUserMenuOpen"
                            :class="[
                                'absolute z-40 rounded-xl border border-ink-200 bg-white p-1.5 text-ink-950 shadow-2xl shadow-ink-950/20 dark:border-ink-800 dark:bg-ink-900 dark:text-white',
                                isSidebarCollapsed
                                    ? 'bottom-0 left-full ml-3 w-64'
                                    : 'right-0 bottom-full left-0 mb-2',
                            ]"
                            role="menu"
                        >
                            <div
                                class="border-b border-ink-100 px-2.5 py-2.5 dark:border-ink-800"
                            >
                                <p class="truncate text-sm font-semibold">
                                    {{ authUser.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-ink-500 dark:text-ink-400"
                                >
                                    {{ authUser.email ?? userFallback }}
                                </p>
                            </div>
                            <div class="space-y-0.5 pt-1.5">
                                <Link
                                    v-if="profileHref"
                                    :href="profileHref"
                                    role="menuitem"
                                    class="flex h-9 items-center gap-2.5 rounded-lg px-2.5 text-sm font-medium text-ink-700 transition hover:bg-ink-100 dark:text-ink-200 dark:hover:bg-ink-800"
                                >
                                    <Icon name="settings" class="size-4.5" />
                                    Mi perfil
                                </Link>
                                <Link
                                    :href="logoutHref"
                                    method="post"
                                    as="button"
                                    role="menuitem"
                                    class="flex h-9 w-full items-center gap-2.5 rounded-lg px-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                                >
                                    <Icon name="log-out" class="size-4.5" />
                                    Cerrar sesión
                                </Link>
                            </div>
                        </div>
                    </Transition>

                    <button
                        type="button"
                        :class="[
                            'flex w-full items-center rounded-xl text-left transition hover:bg-white/10',
                            isSidebarCollapsed
                                ? 'justify-center p-1.5'
                                : 'gap-3 p-2',
                            isUserMenuOpen && 'bg-white/10',
                        ]"
                        :aria-expanded="isUserMenuOpen"
                        aria-haspopup="menu"
                        :title="isSidebarCollapsed ? authUser.name : undefined"
                        @click="isUserMenuOpen = !isUserMenuOpen"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-xs font-bold text-white"
                        >
                            {{ userInitials }}
                        </span>
                        <span v-if="!isSidebarCollapsed" class="min-w-0 flex-1">
                            <span
                                class="block truncate text-sm font-semibold text-white"
                            >
                                {{ authUser.name }}
                            </span>
                            <span class="block truncate text-xs text-ink-400">
                                {{ authUser.email ?? userFallback }}
                            </span>
                        </span>
                        <Icon
                            v-if="!isSidebarCollapsed"
                            name="chevrons-up-down"
                            class="size-4 text-ink-400"
                        />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile drawer -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isMobileNavigationOpen"
                class="fixed inset-0 z-40 bg-ink-950/60 backdrop-blur-sm lg:hidden"
                @click="closeMenus"
            />
        </Transition>

        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-80 max-w-[calc(100vw-3rem)] flex-col bg-brand-night text-ink-200 shadow-2xl transition-transform duration-200 lg:hidden',
                isMobileNavigationOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
            :aria-hidden="!isMobileNavigationOpen"
            :inert="!isMobileNavigationOpen || undefined"
            aria-label="Navegación móvil"
        >
            <div class="flex h-full flex-col px-4 py-5">
                <div class="flex items-center justify-between gap-3 px-1">
                    <Link :href="homeHref" class="min-w-0 rounded-lg">
                        <AppLogo tone="light" size="sm" :caption="caption" />
                    </Link>
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg text-ink-400 transition hover:bg-white/10 hover:text-white"
                        aria-label="Cerrar menú"
                        @click="closeMenus"
                    >
                        <Icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <nav class="mt-8 flex-1 space-y-6 overflow-y-auto">
                    <section
                        v-for="section in visibleSections"
                        :key="section.label"
                        class="space-y-1"
                    >
                        <p class="mb-2 px-3 eyebrow text-ink-400">
                            {{ section.label }}
                        </p>
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            :aria-current="isActive(item) ? 'page' : undefined"
                            :class="[
                                'relative flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold transition-colors',
                                isActive(item)
                                    ? 'bg-white/10 text-white'
                                    : 'text-ink-300 hover:bg-white/5 hover:text-white',
                            ]"
                        >
                            <span
                                v-if="isActive(item)"
                                class="absolute top-1/2 left-0 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-accent-500"
                            />
                            <Icon
                                :name="item.icon"
                                :class="[
                                    'size-[1.125rem] shrink-0',
                                    isActive(item)
                                        ? 'text-primary-300'
                                        : 'text-ink-400',
                                ]"
                            />
                            <span class="truncate">{{ item.name }}</span>
                        </Link>
                    </section>
                </nav>

                <div
                    v-if="authUser"
                    class="space-y-3 border-t border-white/10 pt-4"
                >
                    <div class="flex items-center gap-3 px-1">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-xs font-bold text-white"
                        >
                            {{ userInitials }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="block truncate text-sm font-semibold text-white"
                            >
                                {{ authUser.name }}
                            </span>
                            <span class="block truncate text-xs text-ink-400">
                                {{ authUser.email ?? userFallback }}
                            </span>
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <Link
                            v-if="profileHref"
                            :href="profileHref"
                            class="flex h-10 items-center justify-center gap-2 rounded-lg bg-white/5 text-sm font-semibold text-ink-100 transition hover:bg-white/10"
                        >
                            <Icon name="settings" class="size-4" />
                            Perfil
                        </Link>
                        <Link
                            :href="logoutHref"
                            method="post"
                            as="button"
                            :class="[
                                'flex h-10 items-center justify-center gap-2 rounded-lg bg-white/5 text-sm font-semibold text-red-300 transition hover:bg-red-500/15',
                                !profileHref && 'col-span-2',
                            ]"
                        >
                            <Icon name="log-out" class="size-4" />
                            Salir
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Content column -->
        <div
            :class="[
                'flex min-h-screen w-full flex-col',
                isRestored && 'transition-[padding] duration-200',
                isSidebarCollapsed ? 'lg:pl-[4.75rem]' : 'lg:pl-72',
            ]"
        >
            <header
                class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-ink-200/80 bg-white/90 px-4 backdrop-blur-md md:px-6 xl:px-8 dark:border-ink-800 dark:bg-ink-950/85"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="-ml-1 flex size-10 shrink-0 items-center justify-center rounded-lg text-ink-600 transition hover:bg-ink-100 hover:text-ink-950 lg:hidden dark:text-ink-300 dark:hover:bg-ink-800 dark:hover:text-white"
                        aria-label="Abrir menú"
                        :aria-expanded="isMobileNavigationOpen"
                        @click="isMobileNavigationOpen = true"
                    >
                        <Icon name="menu" class="size-5" />
                    </button>

                    <Link
                        :href="homeHref"
                        class="shrink-0 lg:hidden"
                        aria-label="Inicio"
                    >
                        <AppLogoMark class="size-8" />
                    </Link>

                    <div class="min-w-0">
                        <nav
                            v-if="breadcrumbs.length > 1"
                            aria-label="Ruta de navegación"
                            class="hidden items-center gap-1 text-xs font-medium text-ink-500 sm:flex dark:text-ink-400"
                        >
                            <template
                                v-for="(crumb, index) in breadcrumbs.slice(
                                    0,
                                    -1,
                                )"
                                :key="index"
                            >
                                <Link
                                    v-if="crumb.href"
                                    :href="crumb.href"
                                    class="truncate rounded hover:text-primary-700 dark:hover:text-primary-300"
                                >
                                    {{ crumb.label }}
                                </Link>
                                <span v-else class="truncate">{{
                                    crumb.label
                                }}</span>
                                <Icon
                                    name="chevron-right"
                                    class="size-3 shrink-0 text-ink-300 dark:text-ink-600"
                                />
                            </template>
                        </nav>
                        <h1
                            class="truncate text-base font-bold text-ink-950 md:text-lg dark:text-white"
                        >
                            {{ title }}
                        </h1>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <slot name="header-actions" />
                    <AppearanceToggle />
                </div>
            </header>

            <main
                id="main-content"
                tabindex="-1"
                class="min-w-0 flex-1 px-4 py-6 focus:outline-none md:px-6 md:py-8 xl:px-8"
            >
                <div class="app-grid">
                    <slot />
                </div>
            </main>
        </div>

        <FlashToast />
    </div>
</template>
