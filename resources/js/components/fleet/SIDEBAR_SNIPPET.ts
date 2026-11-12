// resources/js/components/AppSidebar.vue -> reemplaza/extiende mainNavItems
import type { NavItem } from '@/types';
import { Car, Layers, LayoutGrid, NotebookPen, Route, Tags } from '@lucide/vue';

export const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Traslados', href: '/trips', icon: Route },
    { title: 'Bitácora', href: '/logbook', icon: NotebookPen },
    { title: 'Vehículos', href: '/vehicles', icon: Car },
    { title: 'Marcas', href: '/brands', icon: Tags },
    { title: 'Modelos', href: '/vehicle-models', icon: Layers },
];
