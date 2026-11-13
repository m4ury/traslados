<script setup lang="ts">
import ActiveCheckbox from '@/components/fleet/ActiveCheckbox.vue';
import PageHeader from '@/components/fleet/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Marca, Vehiculo } from '@/types/fleet';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Car, LoaderCircle, Save } from '@lucide/vue';
import { computed, watch } from 'vue';

const props = defineProps<{
    vehiculo: Vehiculo | null;
    marcas: Marca[];
    types: string[];
}>();

const isEdit = computed(() => !!props.vehiculo);
const title = computed(() => (isEdit.value ? `Editar ${props.vehiculo?.patente}` : 'Nuevo vehículo'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehículos', href: '/vehiculos' },
    { title: props.vehiculo ? 'Editar' : 'Nuevo', href: '#' },
];

const form = useForm({
    patente: props.vehiculo?.patente ?? '',
    marca_id: props.vehiculo ? String(props.vehiculo.marca_id) : '',
    modelo_id: props.vehiculo ? String(props.vehiculo.modelo_id) : '',
    anio: props.vehiculo?.anio ?? new Date().getFullYear(),
    //vehiculo_type: props.vehiculo?.vehiculo_type ?? '',
    actual_km: props.vehiculo?.actual_km ?? 0,
    activo: props.vehiculo?.activo ?? true,
});

// Select dependiente: modelos según la marca elegida
const modelos = computed(() => props.marcas.find((b) => String(b.id) === form.marca_id)?.modelos ?? []);

watch(
    () => form.marca_id,
    () => {
        if (!modelos.value.some((m) => String(m.id) === form.modelo_id)) form.modelo_id = '';
    },
);

function submit() {
    if (props.vehiculo) form.put(`/vehiculos/${props.vehiculo.id}`);
    else form.post('/vehiculos');
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
            <PageHeader :title="title" description="Datos de identificación del vehículo." :icon="Car" />

            <form @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del vehículo</CardTitle>
                        <CardDescription>La patente se guarda en mayúsculas y sin guiones.</CardDescription>
                    </CardHeader>

                    <CardContent class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="patente">Patente</Label>
                            <Input id="patente" v-model="form.patente" placeholder="ABCD12" maxlength="10" class="font-mono uppercase" required="required" />
                            <InputError :message="form.errors.patente" />
                        </div>

                        <!-- <div class="grid gap-2">
                            <Label>Tipo de vehículo</Label>
                            <Select v-model="form.vehicle_type">
                                <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona un tipo" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in types" :key="t" :value="t">{{ t }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.vehicle_type" />
                        </div> -->

                        <div class="grid gap-2">
                            <Label>Marca</Label>
                            <Select v-model="form.marca_id">
                                <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona una marca" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="b in marcas" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.marca_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label>Modelo</Label>
                            <Select v-model="form.modelo_id" :disabled="!form.marca_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue :placeholder="form.marca_id ? 'Selecciona un modelo' : 'Primero elige la marca'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="m in modelos" :key="m.id" :value="String(m.id)">{{ m.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.marca_id && !modelos.length" class="text-xs text-muted-foreground">
                                Esta marca no tiene modelos.
                                <Link href="/modelos" class="underline">Agregar modelo</Link>
                            </p>
                            <InputError :message="form.errors.modelo_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="anio">Año</Label>
                            <Input id="anio" v-model.number="form.anio" type="number" min="1980" :max="new Date().getFullYear() + 1" />
                            <InputError :message="form.errors.anio" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="km">Kilometraje actual</Label>
                            <div class="relative">
                                <Input id="km" v-model.number="form.actual_km" type="number" min="0" class="pr-10" />
                                <span class="absolute top-1/2 right-3 -translate-y-1/2 text-xs text-muted-foreground">km</span>
                            </div>
                            <p class="text-xs text-muted-foreground">Se actualiza automáticamente al finalizar cada viaje.</p>
                            <InputError :message="form.errors.actual_km" />
                        </div>

                        <div class="sm:col-span-2">
                            <ActiveCheckbox v-model="form.activo" hint="Un vehículo inactivo no puede asignarse a traslados." />
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end gap-2 border-t pt-6">
                        <Button variant="outline" as-child><Link href="/vehiculos">Cancelar</Link></Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            <Save v-else class="size-4" />
                            {{ isEdit ? 'Guardar cambios' : 'Registrar vehículo' }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
