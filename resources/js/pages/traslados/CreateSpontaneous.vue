<script setup lang="ts">
import EmptyState from '@/components/fleet/EmptyState.vue';
import PageHeader from '@/components/fleet/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatKm } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import type { Conductor, Vehiculo } from '@/types/fleet';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Car, CircleAlert, Gauge, LoaderCircle, MapPin, Navigation, UserRound, Users, Zap } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    vehiculos: Vehiculo[];
    conductores: Pick<Conductor, 'id' | 'nombres' | 'rut'>[];
    currentDriverId: number | null;
    canChooseDriver: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Traslados', href: '/traslados' },
    { title: 'Viaje espontáneo', href: '/traslados/spontaneous/create' },
];

const form = useForm({
    conductor_id: props.currentDriverId ? String(props.currentDriverId) : '',
    vehiculo_id: '',
    origen: '',
    destino: '',
    proposito: '',
    cantidad_pasajeros: 1,
    init_km: '' as number | '',
});

const selectedVehicle = computed(() => props.vehiculos.find((v) => String(v.id) === form.vehiculo_id));

// El chofer logueado no está en la lista si no está de turno o ya tiene un viaje en curso
const driverUnavailable = computed(
    () => !props.canChooseDriver && !!props.currentDriverId && !props.conductores.some((d) => d.id === props.currentDriverId),
);

function selectVehicle(v: Vehiculo) {
    form.vehiculo_id = String(v.id);
    form.init_km = v.actual_km;
}

const submit = () => form.post('/traslados/spontaneous');
</script>

<template>
    <Head title="Viaje espontáneo" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6" @submit.prevent="submit">
            <PageHeader
                title="Registrar viaje espontáneo"
                description="El chofer de turno toma un vehículo disponible. El viaje inicia de inmediato y queda en la bitácora."
                :icon="Zap"
            />

            <div
                v-if="driverUnavailable"
                class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
            >
                <CircleAlert class="mt-0.5 size-4 shrink-0" />
                No estás de turno o ya tienes un viaje en curso. Finaliza el viaje actual o solicita que te marquen de turno.
            </div>

            <!-- 1. Chofer -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><UserRound class="size-4" /> 1. Chofer de turno</CardTitle>
                    <CardDescription>Solo se muestran choferes activos, de turno y sin viaje en curso.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Select v-model="form.conductor_id" :disabled="!canChooseDriver">
                        <SelectTrigger class="w-full sm:w-96"><SelectValue placeholder="Selecciona el chofer" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="d in conductores" :key="d.id" :value="String(d.id)">{{ d.nombres }} · {{ d.rut }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-2" :message="form.errors.conductor_id" />
                </CardContent>
            </Card>

            <!-- 2. Vehículo -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Car class="size-4" /> 2. Vehículo disponible</CardTitle>
                    <CardDescription>Vehículos activos que no tienen un viaje en curso.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="vehiculos.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <button
                            v-for="v in vehiculos"
                            :key="v.id"
                            type="button"
                            class="flex flex-col items-start gap-2 rounded-lg border p-4 text-left transition-all hover:border-primary/50 hover:bg-accent/50"
                            :class="form.vehiculo_id === String(v.id) && 'border-primary bg-primary/5 ring-2 ring-primary/20'"
                            @click="selectVehicle(v)"
                        >
                            <span class="rounded-md border bg-muted px-2 py-0.5 font-mono text-xs font-semibold tracking-wider">{{ v.patente }}</span>
                            <span class="font-medium">{{ v.marca?.name }} {{ v.modelo?.name }}</span>
                            <span class="flex w-full justify-between text-xs text-muted-foreground">
                                <span><!-- {{ v.vehicle_type }} · --> {{ v.anio }}</span>
                                <span class="font-mono">{{ formatKm(v.actual_km) }}</span>
                            </span>
                        </button>
                    </div>
                    <EmptyState v-else :icon="Car" title="No hay vehículos disponibles" description="Todos los vehículos están en ruta o inactivos." />
                    <InputError class="mt-2" :message="form.errors.vehiculo_id" />
                </CardContent>
            </Card>

            <!-- 3. Datos del viaje -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><MapPin class="size-4" /> 3. Datos del viaje</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="origen">Origen</Label>
                        <Input id="origen" v-model="form.origen" placeholder="Ej: Base central" />
                        <InputError :message="form.errors.origen" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="destino">Destino</Label>
                        <Input id="destino" v-model="form.destino" placeholder="Ej: Hospital del Salvador" />
                        <InputError :message="form.errors.destino" />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="proposito">Motivo <span class="text-muted-foreground">(opcional)</span></Label>
                        <Input id="proposito" v-model="form.proposito" placeholder="Ej: Retiro de documentación" />
                        <InputError :message="form.errors.proposito" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cantidad_pasajeros" class="flex items-center gap-1.5"><Users class="size-3.5" /> Pasajeros</Label>
                        <Input id="cantidad_pasajeros" v-model.number="form.cantidad_pasajeros" type="number" min="1" max="60" />
                        <InputError :message="form.errors.cantidad_pasajeros" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="km" class="flex items-center gap-1.5"><Gauge class="size-3.5" /> Kilometraje inicial</Label>
                        <div class="relative">
                            <Input
                                id="km"
                                v-model.number="form.init_km"
                                type="number"
                                :min="selectedVehicle?.actual_km ?? 0"
                                :disabled="!selectedVehicle"
                                class="pr-10 font-mono"
                            />
                            <span class="absolute top-1/2 right-3 -translate-y-1/2 text-xs text-muted-foreground">km</span>
                        </div>
                        <p v-if="selectedVehicle" class="text-xs text-muted-foreground">
                            Último registro: {{ formatKm(selectedVehicle.actual_km) }}. Verifica el odómetro.
                        </p>
                        <InputError :message="form.errors.init_km" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child><Link href="/traslados">Cancelar</Link></Button>
                <Button type="submit" :disabled="form.processing || !form.vehiculo_id || !form.conductor_id">
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                    <Navigation v-else class="size-4" />
                    Iniciar viaje
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
