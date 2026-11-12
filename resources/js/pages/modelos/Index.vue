<script setup lang="ts">
import ActiveBadge from '@/components/fleet/ActiveBadge.vue';
import ActiveCheckbox from '@/components/fleet/ActiveCheckbox.vue';
import ConfirmDialog from '@/components/fleet/ConfirmDialog.vue';
import DataPagination from '@/components/fleet/DataPagination.vue';
import EmptyState from '@/components/fleet/EmptyState.vue';
import FlashMessage from '@/components/fleet/FlashMessage.vue';
import PageHeader from '@/components/fleet/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import type { Marca, Paginated, Modelo } from '@/types/fleet';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Layers, LoaderCircle, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    modelos: Paginated<Modelo>;
    marcas: Marca[];
    filters: { search?: string; marca_id?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Modelos', href: '/modelos' }];

// Filtros
const search = ref(props.filters.search ?? '');
const brandFilter = ref(props.filters.marca_id ?? 'all');

const applyFilters = () =>
    router.get(
        '/modelos',
        { search: search.value || undefined, marca_id: brandFilter.value === 'all' ? undefined : brandFilter.value },
        { preserveState: true, replace: true },
    );

watch(search, debounce(applyFilters));
watch(brandFilter, applyFilters);

// Crear / editar
const activeBrands = computed(() => props.marcas.filter((b) => b.is_active || String(b.id) === form.marca_id));
const dialogOpen = ref(false);
const editing = ref<Modelo | null>(null);
const form = useForm({ marca_id: '', name: '', is_active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    if (brandFilter.value !== 'all') form.marca_id = brandFilter.value;
    dialogOpen.value = true;
}

function openEdit(modelo: Modelo) {
    editing.value = modelo;
    form.clearErrors();
    form.marca_id = String(modelo.marca_id);
    form.name = modelo.name;
    form.is_active = modelo.is_active;
    dialogOpen.value = true;
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };
    if (editing.value) form.put(`/modelos/${editing.value.id}`, options);
    else form.post('/modelos', options);
}

// Eliminar
const toDelete = ref<Modelo | null>(null);
function destroy() {
    if (!toDelete.value) return;
    router.delete(`/modelos/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Modelos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Modelos" description="Modelos de vehículo asociados a cada marca." :icon="Layers">
                <template #actions>
                    <Button :disabled="!marcas.length" @click="openCreate"><Plus class="size-4" /> Nuevo modelo</Button>
                </template>
            </PageHeader>

            <FlashMessage />

            <Card class="gap-0 overflow-hidden py-0">
                <div class="flex flex-col gap-3 border-b p-4 sm:flex-row">
                    <div class="relative w-full max-w-sm">
                        <Search class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Buscar modelo…" class="pl-8" />
                    </div>
                    <Select v-model="brandFilter">
                        <SelectTrigger class="w-full sm:w-52">
                            <SelectValue placeholder="Marca" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las marcas</SelectItem>
                            <SelectItem v-for="marca in marcas" :key="marca.id" :value="String(marca.id)">{{ marca.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Table v-if="modelos.data.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="pl-4">Marca</TableHead>
                            <TableHead>Modelo</TableHead>
                            <TableHead class="text-center">Vehículos</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="pr-4 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="modelo in modelos.data" :key="modelo.id">
                            <TableCell class="pl-4 text-muted-foreground">{{ modelo.marca?.name }}</TableCell>
                            <TableCell class="font-medium">{{ modelo.name }}</TableCell>
                            <TableCell class="text-center tabular-nums">{{ modelo.vehicles_count ?? 0 }}</TableCell>
                            <TableCell><ActiveBadge :active="modelo.is_active" /></TableCell>
                            <TableCell class="pr-4 text-right">
                                <Button variant="ghost" size="icon" title="Editar" @click="openEdit(modelo)">
                                    <Pencil class="size-4" />
                                </Button>
                                <Button variant="ghost" size="icon" title="Eliminar" class="text-destructive" @click="toDelete = modelo">
                                    <Trash2 class="size-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <EmptyState
                    v-else
                    :icon="Layers"
                    title="No hay modelos"
                    :description="marcas.length ? 'Agrega modelos para poder registrar vehículos.' : 'Primero debes registrar una marca.'"
                />

                <DataPagination :meta="modelos" />
            </Card>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <form class="grid gap-5" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{ editing ? 'Editar modelo' : 'Nuevo modelo' }}</DialogTitle>
                        <DialogDescription>El nombre debe ser único dentro de la marca.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label>Marca</Label>
                        <Select v-model="form.marca_id">
                            <SelectTrigger class="w-full"><SelectValue placeholder="Selecciona una marca" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="marca in activeBrands" :key="marca.id" :value="String(marca.id)">
                                    {{ marca.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.marca_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Nombre del modelo</Label>
                        <Input id="name" v-model="form.name" placeholder="Ej: Hilux" autocomplete="off" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <ActiveCheckbox v-model="form.is_active" />

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Guardar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="!!toDelete"
            title="Eliminar modelo"
            :description="`Se eliminará el modelo «${toDelete?.marca?.name} ${toDelete?.name}».`"
            @update:open="(v) => !v && (toDelete = null)"
            @confirm="destroy"
        />
    </AppLayout>
</template>
