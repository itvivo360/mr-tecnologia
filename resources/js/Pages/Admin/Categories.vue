<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ElMessageBox } from 'element-plus';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ categories: Array });

const form = useForm({ name: '' });
const create = () => form.post(route('admin.categories.store'), { preserveScroll: true, onSuccess: () => form.reset() });

const editing = ref(null);
const editName = ref('');
const editError = ref(null);
const startEdit = (c) => {
    editing.value = c.id;
    editName.value = c.name;
    editError.value = null;
};
const save = (c) =>
    router.put(route('admin.categories.update', c.id), { name: editName.value }, {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
        onError: (errors) => (editError.value = errors.name),
    });

const destroy = async (c) => {
    const ok = await ElMessageBox.confirm(
        c.products_count ? `${c.products_count} producto(s) quedarán sin categoría.` : '¿Eliminar la categoría?',
        `Eliminar "${c.name}"`,
        { type: 'warning', confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' },
    ).then(() => true, () => false);
    if (!ok) return;
    router.delete(route('admin.categories.destroy', c.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Categorías" />
    <AdminLayout title="Categorías">
        <div class="card" style="max-width: 720px">
            <el-form class="row mb" @submit.prevent="create">
                <el-input v-model="form.name" placeholder="Nueva categoría" style="flex: 1" />
                <el-button type="primary" native-type="submit" :loading="form.processing">Agregar</el-button>
            </el-form>
            <div v-if="form.errors.name" class="danger small mb">{{ form.errors.name }}</div>

            <el-table :data="categories" empty-text="Sin categorías">
                <el-table-column label="Nombre" min-width="200">
                    <template #default="{ row }">
                        <template v-if="editing === row.id">
                            <el-input v-model="editName" size="small" @keydown.enter="save(row)" />
                            <div v-if="editError" class="danger small">{{ editError }}</div>
                        </template>
                        <span v-else>{{ row.name }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Productos" prop="products_count" width="110" align="center" />
                <el-table-column width="170" align="right">
                    <template #default="{ row }">
                        <template v-if="editing === row.id">
                            <el-button size="small" type="primary" @click="save(row)">Guardar</el-button>
                            <el-button size="small" @click="editing = null">Cancelar</el-button>
                        </template>
                        <template v-else>
                            <el-button text size="small" @click="startEdit(row)"><el-icon><IconEdit /></el-icon></el-button>
                            <el-button text size="small" type="danger" @click="destroy(row)"><el-icon><IconDelete /></el-icon></el-button>
                        </template>
                    </template>
                </el-table-column>
            </el-table>
        </div>
    </AdminLayout>
</template>
