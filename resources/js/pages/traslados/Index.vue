<script setup lang="ts">
import DataPagination from '@/components/fleet/DataPagination.vue';
import EmptyState from '@/components/fleet/EmptyState.vue';
import FlashMessage from '@/components/fleet/FlashMessage.vue';
import PageHeader from '@/components/fleet/PageHeader.vue';
import TripStatusBadge from '@/components/fleet/TripStatusBadge.vue';
import TripTypeBadge from '@/components/fleet/TripTypeBadge.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce, formatDateTime, tripStatusMeta, tripStatusOrder } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import type { EstadoTraslado, Paginated, TipoTraslado, Traslado } from '@/types/fleet';
import { index as trasladosIndex } from '@/routes/traslados';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarPlus, ChevronRight, Route, Search, Zap } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    traslados: Paginated<Traslado>;
    counts: Partial<Record<EstadoTraslado, number>>;
    filters: { estado?: EstadoTraslado; tipo_traslado?: TipoTraslado; search?: string };
    can: { manage: boolean; spontaneous: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Traslados', href: '/traslados' }];

const status = ref<EstadoTraslado | 'all'>(props.filters.estado ?? 'all');
const type = ref<TipoTraslado | 'all'>(props.filters.tipo_traslado ?? 'all');
const search = ref(props.filters.search ?? '');

const total = computed(() => Object.values(props.counts).reduce((a, b) => a + Number(b ?? 0), 0));

const applyFilters = () =>
    router.get(
        trasladosIndex.url({
            query: {
                estado: status.value === 'all' ? undefined : status.value,
                tipo_traslado: type.value === 'all' ? undefined : type.value,
                search: search.value || undefined,
            },
        }),
        {},
        { preserveState: true, replace: true },
    );

watch(search, debounce(applyFilters));
watch([status, type], applyFilters);

const tripDate = (t: Traslado) => t.salida_at ?? t.programado_at ?? t.created_at;
</script>

<template>
    <Head title="Traslados" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Traslados" description="Solicitudes, viajes en curso y viajes realizados." :icon="Route">
                <template #actions>
                    <Button v-if="can.spontaneous" variant="outline" as-child>
                        <Link href="/traslados/spontaneous/create"><Zap class="size-4" /> Viaje espontáneo</Link>
                    </Button>
                    <Button as-child>
                        <Link href="/traslados/request/create"><CalendarPlus class="size-4" /> Solicitar viaje</Link>
                    </Button>
                </template>
            </PageHeader>

            <FlashMessage />

            <!-- Filtro rápido por estado -->
            <div class="flex gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition-colors"
                    :class="status === 'all' ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    @click="status = 'all'"
                >
                    Todos <span class="tabular-nums opacity-70">{{ total }}</span>
                </button>
                <button
                    v-for="s in tripStatusOrder"
                    :key="s"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition-colors"
                    :class="status === s ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    @click="status = s"
                >
                    {{ tripStatusMeta[s].label }}
                    <span class="tabular-nums opacity-70">{{ counts[s] ?? 0 }}</span>
                </button>
            </div>

            <Card class="gap-0 overflow-hidden py-0">
                <div class="flex flex-col gap-3 border-b p-4 sm:flex-row">
                    <div class="relative w-full sm:max-w-sm">
                        <Search class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Origen, destino, patente o chofer…" class="pl-8" />
                    </div>
                    <Select v-model="type">
                        <SelectTrigger class="w-full sm:w-44"><SelectValue placeholder="Tipo" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los tipos</SelectItem>
                            <SelectItem value="spontaneous">Espontáneos</SelectItem>
                            <SelectItem value="scheduled">Programados</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Table v-if="traslados.data.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="pl-4">N°</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Trayecto</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Vehículo</TableHead>
                            <TableHead>Chofer</TableHead>
                            <TableHead class="pr-4"><span class="sr-only">Ver</span></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="t in traslados.data" :key="t.id" class="cursor-pointer" @click="router.visit(`/traslados/${t.id}`)">
                            <TableCell class="pl-4 font-mono text-xs text-muted-foreground">#{{ t.id }}</TableCell>
                            <TableCell>
                                <div class="flex flex-col items-start gap-1">
                                    <TripStatusBadge :status="t.estado" />
                                    <TripTypeBadge :type="t.tipo_traslado" />
                                </div>
                            </TableCell>
                            <TableCell class="max-w-72">
                                <p class="flex items-center gap-1.5 truncate font-medium">
                                    <span class="truncate">{{ t.origen }}</span>
                                    <ArrowRight class="size-3.5 shrink-0 text-muted-foreground" />
                                    <span class="truncate">{{ t.destino }}</span>
                                </p>
                                <p v-if="t.proposito" class="truncate text-xs text-muted-foreground">{{ t.proposito }}</p>
                            </TableCell>
                            <TableCell class="text-sm whitespace-nowrap">{{ formatDateTime(tripDate(t)) }}</TableCell>
                            <TableCell>
                                <span v-if="t.vehiculo" class="font-mono text-xs font-semibold">{{ t.vehiculo.patente }}</span>
                                <span v-else class="text-xs text-muted-foreground italic">Sin asignar</span>
                            </TableCell>
                            <TableCell class="text-sm">
                                <span v-if="t.conductor">{{ t.conductor.nombres }}</span>
                                <span v-else class="text-xs text-muted-foreground italic">Sin asignar</span>
                            </TableCell>
                            <TableCell class="pr-4 text-right"><ChevronRight class="ml-auto size-4 text-muted-foreground" /></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <EmptyState v-else :icon="Route" title="No hay traslados" description="No se encontraron traslados con los filtros aplicados." />

                <DataPagination :meta="traslados" />
            </Card>
        </div>
    </AppLayout>
</template>
