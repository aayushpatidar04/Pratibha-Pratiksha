<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, reactive, computed } from "vue";
import {
    UserCog,
    Plus,
    Trash2,
    KeyRound,
    ShieldCheck,
    ShieldAlert,
} from "lucide-vue-next";

const props = defineProps({ users: Array, modules: Array });
const currentUserId = usePage().props.auth.user.id;

// How many active super admins exist right now. Used to stop the very last one
// from being demoted/deactivated/deleted and locking everyone out of Admin.
const activeSuperAdminCount = computed(
    () =>
        props.users.filter((u) => u.role === "super_admin" && u.is_active)
            .length,
);
const isLastActiveSuperAdmin = (u) =>
    u.role === "super_admin" && u.is_active && activeSuperAdminCount.value <= 1;

const createOpen = ref(false);
const createForm = useForm({
    name: "",
    email: "",
    password: "",
    role: "staff",
    phone: "",
});
const submitCreate = () =>
    createForm.post("/admin/users", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

// Role dropdown: enabled for every user, including yourself — but if this row is the
// last active Super Admin, changing it away is blocked (with an explanation), and if
// you're changing your OWN role you get an extra confirmation since you'll immediately
// lose Admin access after saving.
const onRoleChange = (u, event) => {
    const newRole = event.target.value;

    if (isLastActiveSuperAdmin(u) && newRole !== "super_admin") {
        event.target.value = u.role; // revert the visual selection
        alert(
            "This is the last active Super Admin. Promote someone else to Super Admin first, then you can change this one.",
        );
        return;
    }

    if (
        u.id === currentUserId &&
        u.role === "super_admin" &&
        newRole !== "super_admin"
    ) {
        if (
            !confirm(
                "You're changing your own role away from Super Admin. You'll lose access to User Management immediately after this. Continue?",
            )
        ) {
            event.target.value = u.role; // revert the visual selection
            return;
        }
    }

    router.put(`/admin/users/${u.id}`, { role: newRole });
};

const toggleActive = (u) => {
    if (isLastActiveSuperAdmin(u)) {
        alert(
            "This is the last active Super Admin and can't be deactivated. Promote someone else to Super Admin first.",
        );
        return;
    }
    if (
        u.id === currentUserId &&
        !confirm("Deactivate your own account? You will be logged out.")
    ) {
        return;
    }
    router.put(`/admin/users/${u.id}`, { is_active: !u.is_active });
};

const destroy = (u) => {
    if (u.id === currentUserId) return; // button is hidden for self anyway
    if (isLastActiveSuperAdmin(u)) {
        alert(
            "This is the last active Super Admin and can't be deleted. Promote someone else to Super Admin first.",
        );
        return;
    }
    if (confirm("Remove this staff account?"))
        router.delete(`/admin/users/${u.id}`);
};

// --- Permission matrix modal ---
const permOpen = ref(false);
const permUser = ref(null);
const matrix = reactive({});

const openPermissions = (u) => {
    permUser.value = u;
    Object.keys(matrix).forEach((k) => delete matrix[k]);
    props.modules.forEach((m) => {
        matrix[m.key] = [...(u.permissions?.[m.key] || [])];
    });
    permOpen.value = true;
};

const toggleAction = (moduleKey, action) => {
    const current = matrix[moduleKey] || [];
    matrix[moduleKey] = current.includes(action)
        ? current.filter((a) => a !== action)
        : [...current, action];
};

const grantAllForModule = (moduleKey, actions) => {
    matrix[moduleKey] = [...actions];
};
const clearModule = (moduleKey) => {
    matrix[moduleKey] = [];
};

const permSaving = ref(false);
const savePermissions = () => {
    permSaving.value = true;
    router.put(
        `/admin/users/${permUser.value.id}/permissions`,
        { permissions: { ...matrix } },
        {
            onFinish: () => {
                permSaving.value = false;
                permOpen.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="User Management" />
    <AuthenticatedLayout>
        <template #header>Admin / User Management</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <UserCog class="h-6 w-6 text-blue-600" /> Staff & Admin
                        Users
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Manage accounts, roles, and exactly which modules each
                        person can view, create, edit or delete in
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> Add Staff</PrimaryButton
                >
            </div>

            <div
                v-if="activeSuperAdminCount <= 1"
                class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800"
            >
                <ShieldAlert class="h-4 w-4 shrink-0" />
                There's only one active Super Admin right now, so their
                role/status/account is protected from being changed or removed.
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Name</th>
                            <th class="text-left px-4 py-3">Email</th>
                            <th class="text-left px-4 py-3">Role</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Permissions</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="u in users" :key="u.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ u.name }}
                                <span
                                    v-if="u.id === currentUserId"
                                    class="text-xs text-gray-400 font-normal"
                                    >(you)</span
                                >
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ u.email }}
                            </td>
                            <td class="px-4 py-3">
                                <select
                                    :value="u.role"
                                    @change="onRoleChange(u, $event)"
                                    class="text-xs rounded-lg border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="isLastActiveSuperAdmin(u)"
                                    :title="
                                        isLastActiveSuperAdmin(u)
                                            ? 'Promote another Super Admin first'
                                            : ''
                                    "
                                >
                                    <option value="super_admin">
                                        Super Admin
                                    </option>
                                    <option value="hostel_admin">
                                        Hostel Admin
                                    </option>
                                    <option value="warden">Warden</option>
                                    <option value="accountant">
                                        Accountant
                                    </option>
                                    <option value="caretaker">Caretaker</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    @click="toggleActive(u)"
                                    :disabled="isLastActiveSuperAdmin(u)"
                                    :title="
                                        isLastActiveSuperAdmin(u)
                                            ? 'Promote another Super Admin first'
                                            : ''
                                    "
                                >
                                    <Badge
                                        :color="u.is_active ? 'green' : 'gray'"
                                        >{{
                                            u.is_active ? "Active" : "Inactive"
                                        }}</Badge
                                    >
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="u.role === 'super_admin'"
                                    class="inline-flex items-center gap-1 text-xs text-purple-700 font-medium"
                                >
                                    <ShieldCheck class="h-3.5 w-3.5" /> Full
                                    access
                                </span>
                                <button
                                    v-else
                                    class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline"
                                    @click="openPermissions(u)"
                                >
                                    <KeyRound class="h-3.5 w-3.5" /> Manage
                                    Permissions
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="u.id !== currentUserId"
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                                    :disabled="isLastActiveSuperAdmin(u)"
                                    :title="
                                        isLastActiveSuperAdmin(u)
                                            ? 'Promote another Super Admin first'
                                            : ''
                                    "
                                    @click="destroy(u)"
                                >
                                    <Trash2 class="h-3.5 w-3.5 text-red-500" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add staff modal -->
        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Add Staff Account
                </h2>
                <div>
                    <InputLabel value="Name *" /><TextInput
                        v-model="createForm.name"
                        required
                    /><InputError :message="createForm.errors.name" />
                </div>
                <div>
                    <InputLabel value="Email *" /><TextInput
                        type="email"
                        v-model="createForm.email"
                        required
                    /><InputError :message="createForm.errors.email" />
                </div>
                <div>
                    <InputLabel value="Password *" /><TextInput
                        type="password"
                        v-model="createForm.password"
                        required
                    /><InputError :message="createForm.errors.password" />
                </div>
                <div>
                    <InputLabel value="Role *" />
                    <select
                        v-model="createForm.role"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="staff">Staff</option>
                        <option value="caretaker">Caretaker</option>
                        <option value="warden">Warden</option>
                        <option value="accountant">Accountant</option>
                        <option value="hostel_admin">Hostel Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        Everyone except Super Admin starts view-only; grant more
                        from "Manage Permissions" after creating.
                    </p>
                </div>
                <div>
                    <InputLabel value="Phone" /><TextInput
                        v-model="createForm.phone"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="createForm.processing"
                        >Create Account</PrimaryButton
                    >
                </div>
            </form>
        </Modal>

        <!-- Permission matrix modal -->
        <Modal :show="permOpen" @close="permOpen = false" maxWidth="xl">
            <div class="p-6 space-y-4" v-if="permUser">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Manage Permissions — {{ permUser.name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Choose exactly which modules this user can view, create
                        in, edit, or delete from.
                    </p>
                </div>

                <div
                    class="max-h-[55vh] overflow-y-auto border border-gray-100 rounded-lg"
                >
                    <table class="w-full text-sm">
                        <thead
                            class="bg-gray-50 text-gray-500 text-xs uppercase sticky top-0"
                        >
                            <tr>
                                <th class="text-left px-4 py-2.5">Module</th>
                                <th class="text-center px-2 py-2.5 w-16">
                                    View
                                </th>
                                <th class="text-center px-2 py-2.5 w-16">
                                    Create
                                </th>
                                <th class="text-center px-2 py-2.5 w-16">
                                    Edit
                                </th>
                                <th class="text-center px-2 py-2.5 w-16">
                                    Delete
                                </th>
                                <th class="text-center px-2 py-2.5 w-24">
                                    Quick set
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="m in modules" :key="m.key">
                                <td class="px-4 py-2 text-gray-800">
                                    {{ m.label }}
                                </td>
                                <td
                                    v-for="action in [
                                        'view',
                                        'create',
                                        'edit',
                                        'delete',
                                    ]"
                                    :key="action"
                                    class="text-center px-2 py-2"
                                >
                                    <input
                                        v-if="m.actions.includes(action)"
                                        type="checkbox"
                                        :checked="
                                            (matrix[m.key] || []).includes(
                                                action,
                                            )
                                        "
                                        @change="toggleAction(m.key, action)"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span v-else class="text-gray-200">—</span>
                                </td>
                                <td
                                    class="text-center px-2 py-2 space-x-1 whitespace-nowrap"
                                >
                                    <button
                                        type="button"
                                        class="text-[11px] text-blue-600 hover:underline"
                                        @click="
                                            grantAllForModule(m.key, m.actions)
                                        "
                                    >
                                        All
                                    </button>
                                    <button
                                        type="button"
                                        class="text-[11px] text-gray-400 hover:underline"
                                        @click="clearModule(m.key)"
                                    >
                                        None
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="permOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton
                        type="button"
                        :disabled="permSaving"
                        @click="savePermissions"
                    >
                        {{ permSaving ? "Saving..." : "Save Permissions" }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>