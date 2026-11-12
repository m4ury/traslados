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
import type { Driver, Vehicle } from '@/types/fleet';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Car, CircleAlert, Gauge, LoaderCircle, MapPin, Navigation, UserRound, Users, Zap } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    vehicles: Vehicle[];
    drivers: Pick<Driver, 'id' | 'full_name' | 'rut'>[];
    currentDriverId: number | null;
    canChooseDriver: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Traslados', href: '/trips' },
    { title: 'Viaje espontáneo', href: '/trips/spontaneous/create' },
];

const form = useForm({
    driver_id: props.currentDriverId ? String(props.currentDriverId) : '',
    vehicle_id: '',
    origin: '',
    destination: '',
    purpose: '',
    passengers_count: 1,
    initial_mileage_km: null as number | null,
});

const selectedVehicle = computed(() => props.vehicles.find((v) => String(v.id) === form.vehicle_id));

// El chofer logueado no está en la lista si no está de turno o ya tiene un viaje en curso
const driverUnavailable = computed(
    () => !props.canChooseDriver && !!props.currentDriverId && !props.drivers.some((d) => d.id === props.currentDriverId),
);

function selectVehicle(v: Vehicle) {
    form.vehicle_id = String(v.id);
    form.initial_mileage_km = v.current_mileage_km;
}

const submit = () => form.post('/trips/spontaneous');
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
                    <Select v-model="form.driver_id" :disabled="!canChooseDriver">
                        <SelectTrigger class="w-full sm:w-96"><SelectValue placeholder="Selecciona el chofer" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="d in drivers" :key="d.id" :value="String(d.id)">{{ d.full_name }} · {{ d.rut }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-2" :message="form.errors.driver_id" />
                </CardContent>
            </Card>

            <!-- 2. Vehículo -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Car class="size-4" /> 2. Vehículo disponible</CardTitle>
                    <CardDescription>Vehículos activos que no tienen un viaje en curso.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="vehicles.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <button
                            v-for="v in vehicles"
                            :key="v.id"
                            type="button"
                            class="flex flex-col items-start gap-2 rounded-lg border p-4 text-left transition-all hover:border-primary/50 hover:bg-accent/50"
                            :class="form.vehicle_id === String(v.id) && 'border-primary bg-primary/5 ring-2 ring-primary/20'"
                            @click="selectVehicle(v)"
                        >
                            <span class="rounded-md border bg-muted px-2 py-0.5 font-mono text-xs font-semibold tracking-wider">{{ v.plate }}</span>
                            <span class="font-medium">{{ v.brand?.name }} {{ v.vehicle_model?.name }}</span>
                            <span class="flex w-full justify-between text-xs text-muted-foreground">
                                <span>{{ v.vehicle_type }} · {{ v.year }}</span>
                                <span class="font-mono">{{ formatKm(v.current_mileage_km) }}</span>
                            </span>
                        </button>
                    </div>
                    <EmptyState v-else :icon="Car" title="No hay vehículos disponibles" description="Todos los vehículos están en ruta o inactivos." />
                    <InputError class="mt-2" :message="form.errors.vehicle_id" />
                </CardContent>
            </Card>

            <!-- 3. Datos del viaje -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><MapPin class="size-4" /> 3. Datos del viaje</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="origin">Origen</Label>
                        <Input id="origin" v-model="form.origin" placeholder="Ej: Base central" />
                        <InputError :message="form.errors.origin" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="destination">Destino</Label>
                        <Input id="destination" v-model="form.destination" placeholder="Ej: Hospital del Salvador" />
                        <InputError :message="form.errors.destination" />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="purpose">Motivo <span class="text-muted-foreground">(opcional)</span></Label>
                        <Input id="purpose" v-model="form.purpose" placeholder="Ej: Retiro de documentación" />
                        <InputError :message="form.errors.purpose" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="passengers" class="flex items-center gap-1.5"><Users class="size-3.5" /> Pasajeros</Label>
                        <Input id="passengers" v-model.number="form.passengers_count" type="number" min="1" max="60" />
                        <InputError :message="form.errors.passengers_count" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="km" class="flex items-center gap-1.5"><Gauge class="size-3.5" /> Kilometraje inicial</Label>
                        <div class="relative">
                            <Input
                                id="km"
                                v-model.number="form.initial_mileage_km"
                                type="number"
                                :min="selectedVehicle?.current_mileage_km ?? 0"
                                :disabled="!selectedVehicle"
                                class="pr-10 font-mono"
                            />
                            <span class="absolute top-1/2 right-3 -translate-y-1/2 text-xs text-muted-foreground">km</span>
                        </div>
                        <p v-if="selectedVehicle" class="text-xs text-muted-foreground">
                            Último registro: {{ formatKm(selectedVehicle.current_mileage_km) }}. Verifica el odómetro.
                        </p>
                        <InputError :message="form.errors.initial_mileage_km" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" as-child><Link href="/trips">Cancelar</Link></Button>
                <Button type="submit" :disabled="form.processing || !form.vehicle_id || !form.driver_id">
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                    <Navigation v-else class="size-4" />
                    Iniciar viaje
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
