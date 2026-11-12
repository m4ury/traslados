<script setup lang="ts">
import EmptyState from '@/components/fleet/EmptyState.vue';
import FlashMessage from '@/components/fleet/FlashMessage.vue';
import TripStatusBadge from '@/components/fleet/TripStatusBadge.vue';
import TripStepper from '@/components/fleet/TripStepper.vue';
import TripTimeline from '@/components/fleet/TripTimeline.vue';
import TripTypeBadge from '@/components/fleet/TripTypeBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime, formatDuration, formatKm, logEventMeta, manualLogEvents } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import type { Driver, Trip, Vehicle } from '@/types/fleet';
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    Ban,
    Car,
    CircleCheck,
    Flag,
    Gauge,
    LoaderCircle,
    NotebookPen,
    Plus,
    ShieldCheck,
    UserRound,
    Users,
    Navigation,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    trip: Trip;
    can: { approve: boolean; start: boolean; complete: boolean; cancel: boolean; log: boolean };
    vehicles: Vehicle[];
    drivers: (Pick<Driver, 'id' | 'full_name' | 'rut'> & { is_on_duty?: boolean })[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Traslados', href: '/trips' },
    { title: `Viaje #${props.trip.id}`, href: `/trips/${props.trip.id}` },
];

type DialogName = 'approve' | 'start' | 'complete' | 'cancel';
const activeDialog = ref<DialogName | null>(null);
const close = () => (activeDialog.value = null);
const onOpenChange = (open: boolean) => !open && close();

const opts = { preserveScroll: true, onSuccess: close };

/* ---- Autorizar: asigna vehículo + chofer ---- */
const approveForm = useForm({ vehicle_id: '', driver_id: '' });
const approve = () => approveForm.patch(`/trips/${props.trip.id}/authorize`, opts);

/* ---- Iniciar: kilometraje inicial ---- */
const startForm = useForm({ initial_mileage_km: props.trip.vehicle?.current_mileage_km ?? (null as number | null) });
const start = () => startForm.patch(`/trips/${props.trip.id}/start`, opts);

/* ---- Finalizar: kilometraje final ---- */
const completeForm = useForm({ final_mileage_km: null as number | null, notes: '' });
const distancePreview = computed(() =>
    completeForm.final_mileage_km != null && props.trip.initial_mileage_km != null
        ? completeForm.final_mileage_km - props.trip.initial_mileage_km
        : null,
);
const complete = () => completeForm.patch(`/trips/${props.trip.id}/complete`, opts);

/* ---- Cancelar ---- */
const cancelForm = useForm({ reason: '' });
const cancel = () => cancelForm.patch(`/trips/${props.trip.id}/cancel`, opts);

/* ---- Novedad en bitácora ---- */
const logForm = useForm({ event_type: 'checkpoint', mileage_km: null as number | null, location: '', description: '' });
const addLog = () =>
    logForm.post(`/trips/${props.trip.id}/logs`, {
        preserveScroll: true,
        onSuccess: () => logForm.reset(),
    });

const statusError = (form: { errors: object }) => (form.errors as Record<string, string>).status;

const details = computed(() => [
    { label: 'Fecha programada', value: formatDateTime(props.trip.scheduled_at), show: props.trip.trip_type === 'scheduled' },
    { label: 'Salida', value: formatDateTime(props.trip.departure_at), show: true },
    { label: 'Regreso', value: formatDateTime(props.trip.return_at), show: true },
    { label: 'Duración', value: formatDuration(props.trip.departure_at, props.trip.return_at), show: !!props.trip.return_at },
    { label: 'Solicitado por', value: props.trip.requester?.name ?? '—', show: true },
    { label: 'Autorizado por', value: props.trip.authorizer?.name ?? '—', show: props.trip.trip_type === 'scheduled' },
]);
</script>

<template>
    <Head :title="`Viaje #${trip.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-sm text-muted-foreground">#{{ trip.id }}</span>
                        <TripStatusBadge :status="trip.status" />
                        <TripTypeBadge :type="trip.trip_type" />
                    </div>
                    <h1 class="flex flex-wrap items-center gap-2 text-xl font-semibold tracking-tight">
                        {{ trip.origin }} <ArrowRight class="size-5 text-muted-foreground" /> {{ trip.destination }}
                    </h1>
                    <p v-if="trip.purpose" class="text-sm text-muted-foreground">{{ trip.purpose }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button v-if="can.cancel" variant="outline" class="text-destructive" @click="activeDialog = 'cancel'">
                        <Ban class="size-4" /> Cancelar
                    </Button>
                    <Button v-if="can.approve" @click="activeDialog = 'approve'"><ShieldCheck class="size-4" /> Asignar y autorizar</Button>
                    <Button v-if="can.start" @click="activeDialog = 'start'"><Navigation class="size-4" /> Iniciar viaje</Button>
                    <Button v-if="can.complete" @click="activeDialog = 'complete'"><Flag class="size-4" /> Finalizar viaje</Button>
                </div>
            </div>

            <FlashMessage />

            <Card v-if="trip.status !== 'cancelled'">
                <CardContent class="pt-6"><TripStepper :trip="trip" /></CardContent>
            </Card>
            <div
                v-else
                class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-500/30 dark:bg-zinc-500/10 dark:text-zinc-300"
            >
                <Ban class="size-4" /> Este viaje fue cancelado. El motivo está registrado en la bitácora.
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Columna izquierda -->
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <div class="grid gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-2 text-base"><Car class="size-4" /> Vehículo</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="trip.vehicle" class="space-y-1">
                                    <span class="rounded-md border bg-muted px-2 py-0.5 font-mono text-xs font-semibold tracking-wider">{{ trip.vehicle.plate }}</span>
                                    <p class="pt-1 font-medium">{{ trip.vehicle.brand?.name }} {{ trip.vehicle.vehicle_model?.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ trip.vehicle.vehicle_type }} · {{ trip.vehicle.year }}</p>
                                </div>
                                <p v-else class="text-sm text-muted-foreground italic">Pendiente de asignación</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-2 text-base"><UserRound class="size-4" /> Chofer</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="trip.driver" class="space-y-1">
                                    <p class="font-medium">{{ trip.driver.full_name }}</p>
                                    <p class="text-xs text-muted-foreground">RUT {{ trip.driver.rut }}</p>
                                    <p v-if="trip.driver.phone" class="text-xs text-muted-foreground">{{ trip.driver.phone }}</p>
                                </div>
                                <p v-else class="text-sm text-muted-foreground italic">Pendiente de asignación</p>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-base"><Gauge class="size-4" /> Kilometraje</CardTitle>
                        </CardHeader>
                        <CardContent class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Inicial</p>
                                <p class="font-mono text-lg font-semibold">{{ formatKm(trip.initial_mileage_km) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Final</p>
                                <p class="font-mono text-lg font-semibold">{{ formatKm(trip.final_mileage_km) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Recorrido</p>
                                <p class="font-mono text-lg font-semibold text-primary">{{ formatKm(trip.distance_km) }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-3"><CardTitle class="text-base">Detalle</CardTitle></CardHeader>
                        <CardContent>
                            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                                <template v-for="d in details" :key="d.label">
                                    <div v-if="d.show">
                                        <dt class="text-xs text-muted-foreground">{{ d.label }}</dt>
                                        <dd class="text-sm font-medium">{{ d.value }}</dd>
                                    </div>
                                </template>
                                <div>
                                    <dt class="text-xs text-muted-foreground">Pasajeros</dt>
                                    <dd class="flex items-center gap-1 text-sm font-medium"><Users class="size-3.5" /> {{ trip.passengers_count }}</dd>
                                </div>
                                <div v-if="trip.notes" class="sm:col-span-2">
                                    <dt class="text-xs text-muted-foreground">Observaciones</dt>
                                    <dd class="text-sm whitespace-pre-line">{{ trip.notes }}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <!-- Bitácora -->
                <Card class="h-fit">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base"><NotebookPen class="size-4" /> Bitácora</CardTitle>
                        <CardDescription>Cada cambio de estado y novedad queda registrado.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <form v-if="can.log" class="grid gap-3 rounded-lg border bg-muted/40 p-3" @submit.prevent="addLog">
                            <div class="grid grid-cols-2 gap-2">
                                <Select v-model="logForm.event_type">
                                    <SelectTrigger class="w-full bg-background"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="e in manualLogEvents" :key="e" :value="e">{{ logEventMeta[e].label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input v-model.number="logForm.mileage_km" type="number" placeholder="Km (opcional)" class="bg-background" />
                            </div>
                            <Input v-model="logForm.location" placeholder="Ubicación (opcional)" class="bg-background" />
                            <Textarea v-model="logForm.description" rows="2" placeholder="Describe la novedad…" class="bg-background" />
                            <InputError :message="logForm.errors.description || logForm.errors.mileage_km" />
                            <Button type="submit" size="sm" :disabled="logForm.processing || !logForm.description">
                                <LoaderCircle v-if="logForm.processing" class="size-4 animate-spin" />
                                <Plus v-else class="size-4" /> Agregar registro
                            </Button>
                        </form>

                        <TripTimeline v-if="trip.logs?.length" :logs="trip.logs" />
                        <EmptyState v-else :icon="NotebookPen" title="Sin registros" />
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Dialog: autorizar -->
        <Dialog :open="activeDialog === 'approve'" @update:open="onOpenChange">
            <DialogContent class="sm:max-w-lg">
                <form class="grid gap-5" @submit.prevent="approve">
                    <DialogHeader>
                        <DialogTitle>Asignar y autorizar viaje</DialogTitle>
                        <DialogDescription>Salida programada: {{ formatDateTime(trip.scheduled_at) }}. Se valida que el vehículo y el chofer no tengan otro viaje ese día.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label>Vehículo</Label>
                        <Select v-model="approveForm.vehicle_id">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona un vehículo" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="v in vehicles" :key="v.id" :value="String(v.id)">
                                    {{ v.plate }} · {{ v.brand?.name }} {{ v.vehicle_model?.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="approveForm.errors.vehicle_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Chofer</Label>
                        <Select v-model="approveForm.driver_id">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona un chofer" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="d in drivers" :key="d.id" :value="String(d.id)">
                                    {{ d.full_name }}{{ d.is_on_duty ? ' · de turno' : '' }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="approveForm.errors.driver_id" />
                    </div>
                    <InputError :message="statusError(approveForm)" />
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="close">Volver</Button>
                        <Button type="submit" :disabled="approveForm.processing || !approveForm.vehicle_id || !approveForm.driver_id">
                            <LoaderCircle v-if="approveForm.processing" class="size-4 animate-spin" />
                            <ShieldCheck v-else class="size-4" /> Autorizar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog: iniciar -->
        <Dialog :open="activeDialog === 'start'" @update:open="onOpenChange">
            <DialogContent class="sm:max-w-md">
                <form class="grid gap-5" @submit.prevent="start">
                    <DialogHeader>
                        <DialogTitle>Iniciar viaje</DialogTitle>
                        <DialogDescription>Registra el kilometraje que marca el odómetro de {{ trip.vehicle?.plate }} al salir.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="initial_km">Kilometraje inicial</Label>
                        <Input id="initial_km" v-model.number="startForm.initial_mileage_km" type="number" :min="trip.vehicle?.current_mileage_km ?? 0" class="font-mono" />
                        <p class="text-xs text-muted-foreground">Último registro del vehículo: {{ formatKm(trip.vehicle?.current_mileage_km) }}</p>
                        <InputError :message="startForm.errors.initial_mileage_km || statusError(startForm)" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="close">Volver</Button>
                        <Button type="submit" :disabled="startForm.processing">
                            <LoaderCircle v-if="startForm.processing" class="size-4 animate-spin" />
                            <Navigation v-else class="size-4" /> Iniciar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog: finalizar -->
        <Dialog :open="activeDialog === 'complete'" @update:open="onOpenChange">
            <DialogContent class="sm:max-w-md">
                <form class="grid gap-5" @submit.prevent="complete">
                    <DialogHeader>
                        <DialogTitle>Finalizar viaje</DialogTitle>
                        <DialogDescription>Kilometraje inicial: {{ formatKm(trip.initial_mileage_km) }}</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="final_km">Kilometraje final</Label>
                        <Input id="final_km" v-model.number="completeForm.final_mileage_km" type="number" :min="trip.initial_mileage_km ?? 0" class="font-mono" />
                        <p v-if="distancePreview !== null" class="text-xs" :class="distancePreview < 0 ? 'text-destructive' : 'text-muted-foreground'">
                            Recorrido: {{ formatKm(distancePreview) }}
                        </p>
                        <InputError :message="completeForm.errors.final_mileage_km || statusError(completeForm)" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Observaciones <span class="text-muted-foreground">(opcional)</span></Label>
                        <Textarea id="notes" v-model="completeForm.notes" rows="3" placeholder="Estado del vehículo, novedades…" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="close">Volver</Button>
                        <Button type="submit" :disabled="completeForm.processing || completeForm.final_mileage_km === null">
                            <LoaderCircle v-if="completeForm.processing" class="size-4 animate-spin" />
                            <CircleCheck v-else class="size-4" /> Finalizar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog: cancelar -->
        <Dialog :open="activeDialog === 'cancel'" @update:open="onOpenChange">
            <DialogContent class="sm:max-w-md">
                <form class="grid gap-5" @submit.prevent="cancel">
                    <DialogHeader>
                        <DialogTitle>Cancelar viaje</DialogTitle>
                        <DialogDescription>El motivo quedará registrado en la bitácora.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="reason">Motivo</Label>
                        <Textarea id="reason" v-model="cancelForm.reason" rows="3" />
                        <InputError :message="cancelForm.errors.reason || statusError(cancelForm)" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="close">Volver</Button>
                        <Button type="submit" variant="destructive" :disabled="cancelForm.processing || !cancelForm.reason">
                            <LoaderCircle v-if="cancelForm.processing" class="size-4 animate-spin" />
                            Cancelar viaje
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
