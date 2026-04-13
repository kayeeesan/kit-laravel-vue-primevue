<script setup>
import { ref, onMounted, watch } from 'vue';
import useRoles from '../../composables/roles';
import RoleForm from '../../components/libraries/role/form.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

const { roles, pagination, querySearch, is_loading, getRoles, destroyRole, role: roleStore, storeRole, updateRole } = useRoles();

const show_form_modal = ref(false);
const currentRole = ref(null); // Holds role data for editing

const showModalForm = (val, role = null) => {
    currentRole.value = role ? { ...role } : { name: "", slug: "" }; // clone to avoid reactive mutation
    show_form_modal.value = val;
};

onMounted(() => {
    getRoles();
});

// Watch page changes
watch(() => querySearch.page, () => {
    getRoles();
});

// Reload roles
const reloadRoles = async () => {
    querySearch.page = 1;
    await getRoles();
};

const closeModal = () => {
  show_form_modal.value = false;
  currentRole.value = null;
}
</script>


<template>
  <div class="p-4">
    <h2 class="text-2xl font-bold mb-4">Roles List</h2>

    <!-- Search + Reload -->
    <div class="mb-3 flex flex-col sm:flex-row justify-between items-center gap-2">
      <input
        v-model="querySearch.search"
        type="text"
        placeholder="Search roles..."
        class="border rounded px-3 py-2 w-full sm:w-64 focus:ring-2 focus:ring-blue-500"
        @input="() => { querySearch.page = 1; getRoles(); }"
      />
      <!-- Open modal button -->
      <Button label="New Role" class="btn btn-primary" @click="showModalForm(true)" />
      <Button label="Reload" icon="pi pi-refresh" class="btn btn-primary" @click="reloadRoles" />
    </div>

    <!-- DataTable -->
    <DataTable
      :value="roles"
      :loading="is_loading"
      :paginator="true"
      :first="(querySearch.page - 1) * pagination.per_page"
      :rows="pagination.per_page"
      :totalRecords="pagination.total"
      :lazy="true"
      @page="(event) => { querySearch.page = event.page + 1 }"
      class="shadow-lg rounded-lg overflow-hidden"
    >
      <Column field="id" header="#" style="width: 50px" />
      <Column field="name" header="Role Name" />

      
      <Column header="Actions" style="width: 160px">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button
              icon="pi pi-pencil"
              class="btn btn-outline-primary btn-sm"
              @click="() => showModalForm(true, data)"
            />

            <Button
              icon="pi pi-trash"
              class="btn btn-danger btn-sm"
              @click="() => destroyRole(data.id)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Modal -->
    <Dialog
      header="Role Form"
      v-model:visible="show_form_modal"
      modal
      class="w-1/2"
      :closable="true"
      @hide="closeModal"
    >
      <RoleForm 
          :role="currentRole" 
          @saved="() => { closeModal(); reloadRoles(); }" 
      />
    </Dialog>

  </div>
</template>

