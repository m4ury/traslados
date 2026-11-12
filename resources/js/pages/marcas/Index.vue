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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/fleet';
import type { BreadcrumbItem } from '@/types';
import type { Marca, Paginated } from '@/types/fleet';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Pencil, Plus, Search, Tags, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = defineProps<{
    marcas: Paginated<Marca>;
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Marcas', href: '/marcas' }];

// Búsqueda
const search = ref(props.filters.search ?? '');
watch(
    search,
    debounce((value: string) => {
        router.get('/marcas', { search: value || undefined }, { preserveState: true, replace: true });
    }),
);

// Crear / editar
const dialogOpen = ref(false);
const editing = ref<Marca | null>(null);
const form = useForm({ name: '', is_active: true });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(marca: Marca) {
    editing.value = marca;
    form.clearErrors();
    form.name = marca.name;
    form.is_active = marca.is_active;
    dialogOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    };
    if (editing.value) form.put(`/marcas/${editing.value.id}`, options);
    else form.post('/marcas', options);
}

// Eliminar
const toDelete = ref<Marca | null>(null);
function destroy() {
    if (!toDelete.value) return;
    router.delete(`/marcas/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
}
</script>

<template>
    <Head title="Marcas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <PageHeader title="Marcas" description="Catálogo de marcas de los vehículos de la flota." :icon="Tags">
                <template #actions>
                    <Button @click="openCreate"><Plus class="size-4" /> Nueva marca</Button>
                </template>
            </PageHeader>

            <FlashMessage />

            <Card class="gap-0 overflow-hidden py-0">
                <div class="border-b p-4">
                    <div class="relative w-full max-w-sm">
                        <Search class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Buscar marca…" class="pl-8" />
                    </div>
                </div>

                <Table v-if="marcas.data.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="pl-4">Nombre</TableHead>
                            <TableHead class="text-center">Modelos</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="pr-4 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="marca in marcas.data" :key="marca.id">
                            <TableCell class="pl-4 font-medium">{{ marca.name }}</TableCell>
                            <TableCell class="text-center tabular-nums">{{ marca.vehicle_models_count ?? 0 }}</TableCell>
                            <TableCell><ActiveBadge :active="marca.is_active" /></TableCell>
                            <TableCell class="pr-4 text-right">
                                <Button variant="ghost" size="icon" title="Editar" @click="openEdit(marca)">
                                    <Pencil class="size-4" />
                                </Button>
                                <Button variant="ghost" size="icon" title="Eliminar" class="text-destructive" @click="toDelete = marca">
                                    <Trash2 class="size-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <EmptyState v-else :icon="Tags" title="No hay marcas" description="Registra la primera marca para luego asociarle modelos.">
                    <Button size="sm" class="mt-2" @click="openCreate"><Plus class="size-4" /> Nueva marca</Button>
                </EmptyState>

                <DataPagination :meta="marcas" />
            </Card>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <form class="grid gap-5" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{ editing ? 'Editar marca' : 'Nueva marca' }}</DialogTitle>
                        <DialogDescription>Nombre comercial de la marca, por ejemplo Toyota o Hyundai.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" v-model="form.name" autocomplete="off" />
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
            title="Eliminar marca"
            :description="`Se eliminará la marca «${toDelete?.name}». Esta acción no se puede deshacer.`"
            @update:open="(v) => !v && (toDelete = null)"
            @confirm="destroy"
        />
    </AppLayout>
</template>
