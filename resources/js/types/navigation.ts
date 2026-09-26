import type { IconName } from '@/types/icon';

export interface NavigationItem {
    name: string;
    href: string;
    icon: IconName;
    /** Permission required to see the item; omit for always-visible items. */
    permission?: string;
    /** Catalog module the tenant must have contracted (tenant panels only). */
    module?: string;
}

export interface NavigationSection {
    label: string;
    items: NavigationItem[];
}

export interface Breadcrumb {
    label: string;
    href?: string;
}
