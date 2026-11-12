<script setup lang="ts">
import DataPagination from '@/components/fleet/DataPagination.vue';
import EmptyState from '@/components/fleet/EmptyState.vue';
import PageHeader from '@/components/fleet/PageHeader.vue';
import TripTypeBadge from '@/components/fleet/TripTypeBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime, formatDuration, formatKm } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Trip } from '@/types/fleet';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarClock, Eraser, Filter, Gauge, NotebookPen, Route, TrendingUp, Zap } from '@lucide/vue';
import { computed, reactive } from 'vue';

const props = defineProps<{
    trips: Paginated<Trip>;
    summary: { trips: number | string; km: number | string; avg_km: number | string; spontaneous: number | string | null };
    topVehicles: { vehicle_id: number; trips: number; km: number | string; vehicle?: { id: number; plate: string } }[];
    filters: { from?: string; to?: string; vehicle_id?: string; driver_id?: string; type?: string };
    vehicles: { id: number; plate: string }[];
    drivers: { id: number; full_name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bitácora', href: '/logbook' }];

const f = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    vehicle_id: props.filters.vehicle_id ? String(props.filters.vehicle_id) : 'all',
    driver_id: props.filters.driver_id ? String(props.filters.driver_id) : 'all',
    type: props.filters.type ?? 'all',
});

function apply() {
    const query: Record<string, string> = {};
    if (f.from) query.from = f.from;
    if (f.to) query.to = f.to;
    if (f.vehicle_id !== 'all') query.vehicle_id = f.vehicle_id;
    if (f.driver_id !== 'all') query.driver_id = f.driver_id;
    if (f.type !== 'all') query.type = f.type;
    router.get('/logbook', query, { preserveState: true, replace: true });
}

function clear() {
    Object.assign(f, { from: '', to: '', vehicle_id: 'all', driver_id: 'all', type: 'all' });
    apply();
}

const totalTrips = computed(() => Number(props.summary.trips ?? 0));
const spontaneous = computed(() => Number(props.summary.spontaneous ?? 0));

const kpis = computed(() => [
    { label: 'Viajes realizados', value: totalTrips.value.toLocaleString('es-CL'), icon: Route },
    { label: 'Kilómetros recorridos', value: formatKm(props.summary.km), icon: Gauge },
    { label: 'Promedio por viaje', value: formatKm(Math.round(Number(props.summary.avg_km))), icon: TrendingUp },
    {
        label: 'Espontáneos / Programados',
        value: `${spontaneous.value} / ${totalTrips.value - spontaneous.value}`,
        icon: Zap,
    },
]);

const maxKm = computed(() => Math.max(1, ...props.topVehicles.map((v) => Number(v.km))));
</script>

<template>
    <Head title="Bitácora" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Bitácora de viajes" description="Registro de viajes finalizados para control y estadísticas." :icon="NotebookPen" />

            <!-- Filtros -->
            <Card>
                <CardContent class="grid gap-4 pt-6 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
                    <div class="grid gap-2">
                        <Label for="from">Desde</Label>
                        <Input id="from" v-model="f.from" type="date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="to">Hasta</Label>
                        <Input id="to" v-model="f.to" type="date" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Vehículo</Label>
                        <Select v-model="f.vehicle_id">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem v-for="v in vehicles" :key="v.id" :value="String(v.id)">{{ v.plate }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Chofer</Label>
                        <Select v-model="f.driver_id">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem v-for="d in drivers" :key="d.id" :value="String(d.id)">{{ d.full_name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Tipo</Label>
                        <Select v-model="f.type">
                            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="spontaneous">Espontáneos</SelectItem>
                                <SelectItem value="scheduled">Programados</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex gap-2">
                        <Button class="flex-1" @click="apply"><Filter class="size-4" /> Filtrar</Button>
                        <Button variant="outline" size="icon" title="Limpiar filtros" @click="clear"><Eraser class="size-4" /></Button>
                    </div>
                </CardContent>
            </Card>

            <!-- KPIs -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="k in kpis" :key="k.label">
                    <CardContent class="flex items-center gap-4 pt-6">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <component :is="k.icon" class="size-5" />
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">{{ k.label }}</p>
                            <p class="text-xl font-semibold tabular-nums">{{ k.value }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 xl:grid-cols-4">
                <!-- Top vehículos -->
                <Card class="h-fit xl:order-last">
                    <CardHeader><CardTitle class="text-base">Vehículos con más km</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="v in topVehicles" :key="v.vehicle_id" class="space-y-1.5">
                            <div class="flex justify-between text-sm">
                                <span class="font-mono font-semibold">{{ v.vehicle?.plate }}</span>
                                <span class="text-muted-foreground tabular-nums">{{ formatKm(v.km) }} · {{ v.trips }} viajes</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted">
                                <div class="h-2 rounded-full bg-primary" :style="{ width: `${(Number(v.km) / maxKm) * 100}%` }" />
                            </div>
                        </div>
                        <p v-if="!topVehicles.length" class="text-sm text-muted-foreground">Sin datos para el período.</p>
                    </CardContent>
                </Card>

                <!-- Tabla -->
                <Card class="gap-0 overflow-hidden py-0 xl:col-span-3">
                    <Table v-if="trips.data.length">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="pl-4">Regreso</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Trayecto</TableHead>
                                <TableHead>Vehículo</TableHead>
                                <TableHead>Chofer</TableHead>
                                <TableHead class="text-right">Km inicio</TableHead>
                                <TableHead class="text-right">Km fin</TableHead>
                                <TableHead class="text-right">Recorrido</TableHead>
                                <TableHead class="pr-4 text-right">Duración</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="t in trips.data" :key="t.id">
                                <TableCell class="pl-4 text-sm whitespace-nowrap">
                                    <Link :href="`/trips/${t.id}`" class="hover:underline">{{ formatDateTime(t.return_at) }}</Link>
                                </TableCell>
                                <TableCell><TripTypeBadge :type="t.trip_type" /></TableCell>
                                <TableCell class="max-w-56">
                                    <p class="flex items-center gap-1 truncate text-sm">
                                        <span class="truncate">{{ t.origin }}</span>
                                        <ArrowRight class="size-3 shrink-0 text-muted-foreground" />
                                        <span class="truncate">{{ t.destination }}</span>
                                    </p>
                                </TableCell>
                                <TableCell class="font-mono text-xs font-semibold">{{ t.vehicle?.plate }}</TableCell>
                                <TableCell class="text-sm">{{ t.driver?.full_name }}</TableCell>
                                <TableCell class="text-right font-mono text-xs tabular-nums">{{ formatKm(t.initial_mileage_km) }}</TableCell>
                                <TableCell class="text-right font-mono text-xs tabular-nums">{{ formatKm(t.final_mileage_km) }}</TableCell>
                                <TableCell class="text-right font-mono text-xs font-semibold tabular-nums">{{ formatKm(t.distance_km) }}</TableCell>
                                <TableCell class="pr-4 text-right text-xs whitespace-nowrap text-muted-foreground">
                                    {{ formatDuration(t.departure_at, t.return_at) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <EmptyState v-else :icon="CalendarClock" title="Sin viajes finalizados" description="Ajusta los filtros o el rango de fechas." />
                    <DataPagination :meta="trips" />
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
