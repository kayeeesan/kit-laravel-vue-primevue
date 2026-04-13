<script setup>
import { ref, onMounted, watch } from 'vue';
import useUsers from '../../composables/users';
import UserForm from '../../components/libraries/user/form.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

const { users, pagination, querySearch, is_loading, getUsers, destroyUser } = useUsers();

const show_form_modal = ref(false);
const currentUser = ref(null); // Holds user data for editing

const showModalForm = (val, user = null) => {
    currentUser.value = user ? { ...user } : { username: "", first_name: "", last_name: "", middle_name: "", user_roles: [] }; // clone to avoid reactive mutation
    show_form_modal.value = val;
}
onMounted(() => {
    getUsers();
});

// Watch for page changes
watch(() => querySearch.page, () => {
    getUsers();
});

// Reload
const reloadUsers = async () => {
    querySearch.page = 1;
    await getUsers();
};

const closeModal = () => {
  show_form_modal.value = false;
  currentUser.value = null;
};
</script>

<template>
  <div class="p-4">
    <h2 class="text-2xl font-bold mb-4">Users List</h2>

    <!-- Search + Reload -->
    <div class="mb-3 flex flex-col sm:flex-row justify-between items-center gap-2">
      <input
        v-model="querySearch.search"
        type="text"
        placeholder="Search users..."
        class="border rounded px-3 py-2 w-full sm:w-64 focus:ring-2 focus:ring-blue-500"
        @input="() => { querySearch.page = 1; getUsers(); }"
      />
      <Button label="New User" class="btn btn-primary" @click="showModalForm(true)" />
      <Button label="Reload" icon="pi pi-refresh" class="btn btn-primary" @click="reloadUsers" />
    </div>

    <!-- DataTable -->
    <DataTable
      :value="users"
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
      <Column field="username" header="Username" />
      <Column field="full_name" header="Full Name" />
      <Column header="Roles">
        <template #body="{ data }">
          <span v-if="data.roles.length">
            {{ data.roles.map(r => r.name).join(', ') }}
          </span>
          <span v-else>-</span>
        </template>
      </Column>

      <!-- Actions column using template slot -->
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
              @click="() => destroyUser(data.id)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- User Form Modal -->
     <Dialog
      header="User Form"
      v-model:visible="show_form_modal"
      modal
      class="w-1/2"
      :closable="true"
     >
      <UserForm
        :user="currentUser"
        @saved="() => { closeModal(); reloadUsers(); }"
      />
    </Dialog>
  </div>
</template>
