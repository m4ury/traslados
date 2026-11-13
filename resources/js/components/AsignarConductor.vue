<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'

const usuarios = ref([])
const userId = ref('')
const licencia = ref('')
const tipoLicencia = ref('')
const mensaje = ref('')
const cargando = ref(false)

const cargarUsuarios = async () => {
    const { data } = await axios.get('/api/usuarios-disponibles-conductores')
    usuarios.value = data.data
}

const asignar = async () => {
    cargando.value = true
    mensaje.value = ''

    try {
        await axios.post('/api/conductores', {
            user_id: userId.value,
            license_number: licencia.value,
            tipo_licencia: tipoLicencia.value,
            activo: true,
        })

        mensaje.value = 'Usuario asignado como conductor.'
        userId.value = ''
        licencia.value = ''
        tipoLicencia.value = ''

        await cargarUsuarios()
    } catch (error) {
        mensaje.value = error.response?.data?.message ?? 'No se pudo asignar el conductor.'
    } finally {
        cargando.value = false
    }
}

onMounted(cargarUsuarios)
</script>

<template>
    <form @submit.prevent="asignar">
        <select v-model="userId" required>
            <option value="" disabled>Selecciona un usuario</option>

            <option
                v-for="usuario in usuarios"
                :key="usuario.id"
                :value="usuario.id"
            >
                {{ usuario.name }} — {{ usuario.email }}
            </option>
        </select>

        <input
            v-model="licencia"
            type="text"
            placeholder="Licencia de conducir"
            required
        />

        <input
            v-model="tipoLicencia"
            type="text"
            placeholder="Tipo de licencia"
        />

        <button type="submit" :disabled="cargando || !userId">
            Asignar como conductor
        </button>

        <p v-if="mensaje">{{ mensaje }}</p>
    </form>
</template>
