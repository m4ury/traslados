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
import type { Conductor, Traslado, Vehiculo } from '@/types/fleet';
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
/* import { Conductor } from '../../types/fleet'; */

const props = defineProps<{
    traslados: Traslado;
    can: { approve: boolean; start: boolean; complete: boolean; cancel: boolean; log: boolean };
    vehiculos: Vehiculo[];
    conductores: (Pick<Conductor, 'id' | 'nombres' | 'rut'> & { en_turno?: boolean })[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Traslados', href: '/traslados' },
    { title: `Viaje #${props.traslados.id}`, href: `/traslados/${props.traslados.id}` },
];

type DialogName = 'approve' | 'start' | 'complete' | 'cancel';
const activeDialog = ref<DialogName | null>(null);
const close = () => (activeDialog.value = null);
const onOpenChange = (open: boolean) => !open && close();

const opts = { preserveScroll: true, onSuccess: close };

/* ---- Autorizar: asigna vehículo + chofer ---- */
const approveForm = useForm({ vehiculo_id: '', conductor_id: '' });
const approve = () => approveForm.patch(`/traslados/${props.traslados.id}/authorize`, opts);

/* ---- Iniciar: kilometraje inicial ---- */
const startForm = useForm({ init_km: props.traslados.vehiculo?.actual_km ?? ('' as number | '') });
const start = () => startForm.patch(`/traslados/${props.traslados.id}/start`, opts);

/* ---- Finalizar: kilometraje final ---- */
const completeForm = useForm({ final_km: 0 as number | '', observaciones: '' });
const distancePreview = computed(() =>
    completeForm.final_km != '' && props.traslados.init_km != null
        ? completeForm.final_km - props.traslados.init_km
        : null,
);
const complete = () => completeForm.patch(`/traslados/${props.traslados.id}/complete`, opts);

/* ---- Cancelar ---- */
const cancelForm = useForm({ reason: '' });
const cancel = () => cancelForm.patch(`/traslados/${props.traslados.id}/cancel`, opts);

/* ---- Novedad en bitácora ---- */
const logForm = useForm({ event_type: 'checkpoint', distancia_km: '' as number | '', location: '', description: '' });
const addLog = () =>
    logForm.post(`/traslados/${props.traslados.id}/logs`, {
        preserveScroll: true,
        onSuccess: () => logForm.reset(),
    });

const statusError = (form: { errors: object }) => (form.errors as Record<string, string>).status;

const details = computed(() => [
    { label: 'Fecha programada', value: formatDateTime(props.traslados.programado_at), show: props.traslados.tipo_traslado === 'scheduled' },
    { label: 'Salida', value: formatDateTime(props.traslados.salida_at), show: true },
    { label: 'Regreso', value: formatDateTime(props.traslados.regreso_at), show: true },
    { label: 'Duración', value: formatDuration(props.traslados.salida_at, props.traslados.regreso_at), show: !!props.traslados.regreso_at },
    { label: 'Solicitado por', value: props.traslados.requester?.name ?? '—', show: true },
    { label: 'Autorizado por', value: props.traslados.authorizer?.name ?? '—', show: props.traslados.authorizer },
]);
</script>

<template>
    <Head :title="`Viaje #${traslados.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-sm text-muted-foreground">#{{ traslados.id }}</span>
                        <TripStatusBadge :status="traslados.estado" />
                        <TripTypeBadge :type="traslados.tipo_traslado" />
                    </div>
                    <h1 class="flex flex-wrap items-center gap-2 text-xl font-semibold tracking-tight">
                        {{ traslados.origen }} <ArrowRight class="size-5 text-muted-foreground" /> {{ traslados.destino }}
                    </h1>
                    <p v-if="traslados.proposito" class="text-sm text-muted-foreground">{{ traslados.proposito }}</p>
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

            <Card v-if="traslados.estado !== 'cancelado'">
                <CardContent class="pt-6"><TripStepper :traslado="traslados" /></CardContent>
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
                                <div v-if="traslados.vehiculo" class="space-y-1">
                                    <span class="rounded-md border bg-muted px-2 py-0.5 font-mono text-xs font-semibold tracking-wider">{{ traslados.vehiculo.patente }}</span>
                                    <p class="pt-1 font-medium">{{ traslados.vehiculo.marca?.name }} {{ traslados.vehiculo.modelo?.name }}</p>
                                    <p class="text-xs text-muted-foreground"><!-- {{ traslados.vehiculo.vehiculo_type }} · --> {{ traslados.vehiculo.anio }}</p>
                                </div>
                                <p v-else class="text-sm text-muted-foreground italic">Pendiente de asignación</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-2 text-base"><UserRound class="size-4" /> Chofer</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="traslados.conductor" class="space-y-1">
                                    <p class="font-medium">{{ traslados.conductor.nombres }}</p>
                                    <p class="text-xs text-muted-foreground">RUT {{ traslados.conductor.rut }}</p>
                                    <p v-if="traslados.conductor.phone" class="text-xs text-muted-foreground">{{ traslados.conductor.phone }}</p>
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
                                <p class="font-mono text-lg font-semibold">{{ formatKm(traslados.init_km) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Final</p>
                                <p class="font-mono text-lg font-semibold">{{ formatKm(traslados.final_km) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Recorrido</p>
                                <p class="font-mono text-lg font-semibold text-primary">{{ formatKm(traslados.distancia_km) }}</p>
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
                                    <dd class="flex items-center gap-1 text-sm font-medium"><Users class="size-3.5" /> {{ traslados.cantidad_pasajeros }}</dd>
                                </div>
                                <div v-if="traslados.observaciones" class="sm:col-span-2">
                                    <dt class="text-xs text-muted-foreground">Observaciones</dt>
                                    <dd class="text-sm whitespace-pre-line">{{ traslados.observaciones }}</dd>
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
                                <Input v-model.number="logForm.distancia_km" type="number" placeholder="Km (opcional)" class="bg-background" />
                            </div>
                            <Input v-model="logForm.location" placeholder="Ubicación (opcional)" class="bg-background" />
                            <Textarea v-model="logForm.description" rows="2" placeholder="Describe la novedad…" class="bg-background" />
                            <InputError :message="logForm.errors.description || logForm.errors.distancia_km" />
                            <Button type="submit" size="sm" :disabled="logForm.processing || !logForm.description">
                                <LoaderCircle v-if="logForm.processing" class="size-4 animate-spin" />
                                <Plus v-else class="size-4" /> Agregar registro
                            </Button>
                        </form>

                        <TripTimeline v-if="traslados.logs?.length" :logs="traslados.logs" />
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
                        <DialogDescription>Salida programada: {{ formatDateTime(traslados.programado_at) }}. Se valida que el vehículo y el chofer no tengan otro viaje ese día.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label>Vehículo</Label>
                        <Select v-model="approveForm.vehiculo_id">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona un vehículo" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="v in vehiculos" :key="v.id" :value="String(v.id)">
                                    {{ v.patente }} · {{ v.marca?.name }} {{ v.modelo?.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="approveForm.errors.vehiculo_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Chofer</Label>
                        <Select v-model="approveForm.conductor_id">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona un chofer" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="d in conductores" :key="d.id" :value="String(d.id)">
                                    {{ d.nombres }}{{ d.en_turno ? ' · de turno' : '' }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="approveForm.errors.conductor_id" />
                    </div>
                    <InputError :message="statusError(approveForm)" />
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="close">Volver</Button>
                        <Button type="submit" :disabled="approveForm.processing || !approveForm.vehiculo_id || !approveForm.conductor_id">
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
                        <DialogDescription>Registra el kilometraje que marca el odómetro de {{ traslados.vehiculo?.patente }} al salir.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="initial_km">Kilometraje inicial</Label>
                        <Input id="initial_km" v-model.number="startForm.init_km" type="number" :min="traslados.vehiculo?.actual_km ?? 0" class="font-mono" />
                        <p class="text-xs text-muted-foreground">Último registro del vehículo: {{ formatKm(traslados.vehiculo?.actual_km) }}</p>
                        <InputError :message="startForm.errors.init_km || statusError(startForm)" />
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
                        <DialogDescription>Kilometraje inicial: {{ formatKm(traslados.init_km) }}</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="final_km">Kilometraje final</Label>
                        <Input id="final_km" v-model.number="completeForm.final_km" type="number" :min="traslados.init_km ?? 0" class="font-mono" />
                        <p v-if="distancePreview !== null" class="text-xs" :class="distancePreview < 0 ? 'text-destructive' : 'text-muted-foreground'">
                            Recorrido: {{ formatKm(distancePreview) }}
                        </p>
                        <InputError :message="completeForm.errors.final_km || statusError(completeForm)" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Observaciones <span class="text-muted-foreground">(opcional)</span></Label>
                        <Textarea id="notes" v-model="completeForm.observaciones" rows="3" placeholder="Estado del vehículo, novedades…" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="close">Volver</Button>
                        <Button type="submit" :disabled="completeForm.processing || completeForm.final_km === null">
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
