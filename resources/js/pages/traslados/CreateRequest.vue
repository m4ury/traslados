<script setup lang="ts">
import PageHeader from '@/components/fleet/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { nowForInput } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarPlus, CircleCheck, ClipboardList, LoaderCircle, Navigation, Send, ShieldCheck } from '@lucide/vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Traslados', href: '/traslados' },
    { title: 'Solicitar viaje', href: '/traslados/request/create' },
];

const form = useForm({
    programado_at: '',
    origen: '',
    destino: '',
    proposito: '',
    cantidad_pasajeros: 1,
    observaciones: '',
});

const steps = [
    { icon: ClipboardList, title: 'Solicitud', text: 'Envías los datos del traslado.' },
    { icon: ShieldCheck, title: 'Autorización', text: 'Un supervisor asigna vehículo y chofer según disponibilidad.' },
    { icon: Navigation, title: 'Ejecución', text: 'El chofer inicia el viaje registrando el kilometraje.' },
    { icon: CircleCheck, title: 'Bitácora', text: 'Al finalizar queda registrado para estadísticas.' },
];

const submit = () => form.post('/traslados/request');
</script>

<template>
    <Head title="Solicitar viaje" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Solicitar viaje programado" description="La solicitud queda pendiente hasta ser autorizada." :icon="CalendarPlus" />

            <div class="grid gap-6 lg:grid-cols-3">
                <form class="lg:col-span-2" @submit.prevent="submit">
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos del traslado</CardTitle>
                            <CardDescription>Indica cuándo, desde dónde y hacia dónde necesitas el traslado.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="programado_at">Fecha y hora de salida</Label>
                                <Input id="programado_at" v-model="form.programado_at" type="datetime-local" :min="nowForInput()" />
                                <InputError :message="form.errors.programado_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="cantidad_pasajeros">Pasajeros</Label>
                                <Input id="cantidad_pasajeros" v-model.number="form.cantidad_pasajeros" type="number" min="1" max="60" />
                                <InputError :message="form.errors.cantidad_pasajeros" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="origen">Origen</Label>
                                <Input id="origen" v-model="form.origen" placeholder="Ej: Oficina central" />
                                <InputError :message="form.errors.origen" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="destino">Destino</Label>
                                <Input id="destino" v-model="form.destino" placeholder="Ej: Aeropuerto SCL" />
                                <InputError :message="form.errors.destino" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="proposito">Motivo del viaje</Label>
                                <Input id="proposito" v-model="form.proposito" placeholder="Ej: Traslado de personal a reunión" />
                                <InputError :message="form.errors.proposito" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="observaciones">Observaciones <span class="text-muted-foreground">(opcional)</span></Label>
                                <Textarea id="observaciones" v-model="form.observaciones" rows="3" placeholder="Hora estimada de regreso, carga, requisitos especiales…" />
                                <InputError :message="form.errors.observaciones" />
                            </div>
                        </CardContent>
                        <CardFooter class="flex justify-end gap-2 border-t pt-6">
                            <Button variant="outline" as-child><Link href="/traslados">Cancelar</Link></Button>
                            <Button type="submit" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                                <Send v-else class="size-4" />
                                Enviar solicitud
                            </Button>
                        </CardFooter>
                    </Card>
                </form>

                <Card class="h-fit">
                    <CardHeader>
                        <CardTitle class="text-base">¿Cómo sigue tu solicitud?</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ol class="space-y-4">
                            <li v-for="(s, i) in steps" :key="s.title" class="flex gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <component :is="s.icon" class="size-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">{{ i + 1 }}. {{ s.title }}</p>
                                    <p class="text-xs text-muted-foreground">{{ s.text }}</p>
                                </div>
                            </li>
                        </ol>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
