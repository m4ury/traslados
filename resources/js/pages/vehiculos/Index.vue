<script setup lang="ts">
import ConfirmDialog from '@/components/fleet/ConfirmDialog.vue';
import DataPagination from '@/components/fleet/DataPagination.vue';
import EmptyState from '@/components/fleet/EmptyState.vue';
import FlashMessage from '@/components/fleet/FlashMessage.vue';
import PageHeader from '@/components/fleet/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce, formatKm } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import type { Marca, Paginated, Vehiculo } from '@/types/fleet';
import { Head, Link, router } from '@inertiajs/vue3';
import { Car, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = defineProps<{
    vehiculos: Paginated<Vehiculo>;
    marcas: Pick<Marca, 'id' | 'name'>[];
    filters: { search?: string; marca_id?: string; status?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Vehículos', href: '/vehicles' }];

const search = ref(props.filters.search ?? '');
const brandId = ref(props.filters.marca_id ?? 'all');
const status = ref(props.filters.status ?? 'all');

const applyFilters = () =>
    router.get(
        '/vehiculos',
        {
            search: search.value || undefined,
            marca_id: brandId.value === 'all' ? undefined : brandId.value,
            status: status.value === 'all' ? undefined : status.value,
        },
        { preserveState: true, replace: true },
    );

watch(search, debounce(applyFilters));
watch([brandId, status], applyFilters);

function availability(v: Vehiculo) {
    if (!v.activo) return { label: 'Inactivo', class: 'text-muted-foreground', dot: 'bg-zinc-400' };
    /* if (v.in_trip)
        return {
            label: 'En ruta',
            class: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-300',
            dot: 'bg-violet-500',
        }; */
    return {
        label: 'Disponible',
        class: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
        dot: 'bg-emerald-500',
    };
}

const toDelete = ref<Vehiculo | null>(null);
function destroy() {
    if (!toDelete.value) return;
    router.delete(`/vehiculos/${toDelete.value.id}`, { preserveScroll: true, onFinish: () => (toDelete.value = null) });
}
</script>

<template>
    <Head title="Vehículos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Vehículos" description="Flota disponible para traslados." :icon="Car">
                <template #actions>
                    <Button as-child>
                        <Link href="/vehiculos/create"><Plus class="size-4" /> Nuevo vehículo</Link>
                    </Button>
                </template>
            </PageHeader>

            <FlashMessage />

            <Card class="gap-0 overflow-hidden py-0">
                <div class="flex flex-col gap-3 border-b p-4 md:flex-row">
                    <div class="relative w-full md:max-w-xs">
                        <Search class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Buscar por patente…" class="pl-8 uppercase placeholder:normal-case" />
                    </div>
                    <Select v-model="brandId">
                        <SelectTrigger class="w-full md:w-48"><SelectValue placeholder="Marca" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las marcas</SelectItem>
                            <SelectItem v-for="b in marcas" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="status">
                        <SelectTrigger class="w-full md:w-44"><SelectValue placeholder="Estado" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los estados</SelectItem>
                            <SelectItem value="available">Disponibles</SelectItem>
                            <SelectItem value="in_trip">En ruta</SelectItem>
                            <SelectItem value="inactive">Inactivos</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Table v-if="vehiculos.data.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="pl-4">Patente</TableHead>
                            <TableHead>Vehículo</TableHead>
                            <TableHead class="text-center">Año</TableHead>
                            <TableHead class="text-right">Kilometraje</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="pr-4 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="v in vehiculos.data" :key="v.id">
                            <TableCell class="pl-4">
                                <span class="rounded-md border bg-muted px-2 py-1 font-mono text-xs font-semibold tracking-wider">{{ v.patente }}</span>
                            </TableCell>
                            <TableCell>
                                <p class="font-medium">{{ v.marca?.name }} {{ v.modelo?.name }}</p>
                                <!-- <p class="text-xs text-muted-foreground">{{ v.vehicle_type }}</p> -->
                            </TableCell>
                            <TableCell class="text-center tabular-nums">{{ v.anio }}</TableCell>
                            <TableCell class="text-right font-mono text-sm tabular-nums">{{ formatKm(v.actual_km) }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" :class="availability(v).class">
                                    <span class="mr-1 size-1.5 rounded-full" :class="availability(v).dot" />
                                    {{ availability(v).label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="pr-4 text-right">
                                <Button variant="ghost" size="icon" as-child title="Editar">
                                    <Link :href="`/vehicles/${v.id}/edit`"><Pencil class="size-4" /></Link>
                                </Button>
                                <Button variant="ghost" size="icon" title="Eliminar" class="text-destructive" @click="toDelete = v">
                                    <Trash2 class="size-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <EmptyState v-else :icon="Car" title="No hay vehículos" description="No se encontraron vehículos con los filtros aplicados." />

                <DataPagination :meta="vehiculos" />
            </Card>
        </div>

        <ConfirmDialog
            :open="!!toDelete"
            title="Eliminar vehículo"
            :description="`¿Eliminar el vehículo ${toDelete?.patente}? Si tiene viajes registrados solo se desactivará para conservar la bitácora.`"
            @update:open="(v) => !v && (toDelete = null)"
            @confirm="destroy"
        />
    </AppLayout>
</template>
