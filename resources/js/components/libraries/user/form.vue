<script setup>
import { ref, reactive, watch, onMounted } from "vue";
import Multiselect from "vue-multiselect";
import axios from "axios"
import useUsers from "../../../composables/users";
import useRoles from "../../../composables/roles";

const { errors, is_loading, is_success, storeUser, updateUser } = useUsers();
const { roles, getRoles } = useRoles();
const props = defineProps({
    user: Object
});
const emit = defineEmits(["saved"]);

const user = ref({ 
    username: "",
    first_name: "",
    last_name: "",
    middle_name: "",
    user_roles: []
});

onMounted(() => {
    getRoles();
});


watch(
  () => props.user,
  (newUser) => {
    if (newUser) {
      user.value = {
        ...newUser,
        user_roles: newUser.roles ?? []
      };
    }
  },
  { immediate: true }
);

const submitForm = async () => {

    const payload = {
        ...user.value,
        user_roles: user.value.user_roles.map(r => r.id)
    };

    if (user.value.id) {
        await updateUser(payload);
    } else {
        await storeUser(payload);
    }

    emit("saved");
};
</script>
<template>
<div>
    <div class="flex flex-col gap-2">
      <input v-model="user.username" placeholder="Username" class="border p-2 rounded" />
      <input v-model="user.first_name" placeholder="First Name" class="border p-2 rounded" />
      <input v-model="user.last_name" placeholder="Last Name" class="border p-2 rounded" />
      <input v-model="user.middle_name" placeholder="Middle Name" class="border p-2 rounded" />

      <label class="font-semibold mt-2">Roles</label>
      <Multiselect
        v-model="user.user_roles"
        :options="roles"
        :multiple="true"
        :close-on-select="false"
        :clear-on-select="false"
        placeholder="Select roles"
        label="name"
        track-by="id"
      />
      <button @click="submitForm" :disabled="is_loading" class="btn btn-primary">
        {{ user.id ? 'Update' : 'Save' }}
      </button>
    </div>
</div>
</template>