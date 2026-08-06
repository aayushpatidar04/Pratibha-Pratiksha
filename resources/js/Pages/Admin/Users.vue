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
    Check,
    ChevronDown,
    ChevronRight,
    UserCog,
    Plus,
    Trash2,
    KeyRound,
    Search,
    ShieldCheck,
    ShieldAlert,
    X,
} from "lucide-vue-next";

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },

    modules: {
        type: Array,
        default: () => [],
    },

    filters: {
        type: Object,
        default: () => ({}),
    },
});
const currentUserId = usePage().props.auth.user.id;

// How many active super admins exist right now. Used to stop the very last one
// from being demoted/deactivated/deleted and locking everyone out of Admin.
const activeSuperAdminCount = computed(
    () =>
        props.users.data.filter((u) => u.role === "super_admin" && u.is_active)
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
const permSaving = ref(false);
const permissionSearch = ref("");

const expandedGroups = reactive({});
const matrix = reactive({});

const actionLabels = {
    view: "View",
    create: "Create",
    edit: "Edit",
    delete: "Delete",

    allot_room: "Allot Room",
    confirm_checkin: "Confirm Check-In",

    start_review: "Start Review",
    assign_inspector: "Assign Inspector",
    manage_dues: "Manage Dues",
    hold: "Put on Hold",
    reject: "Reject",
    final_approve: "Final Approve",
    regenerate_exit_token:
        "Regenerate Exit Token",
    override: "Emergency Override",

    start: "Start",
    save: "Save Draft",
    approve: "Approve",

    verify_exit: "Verify Exit",
    complete_checkout:
        "Complete Checkout",

    publish: "Publish",
};

const dangerousActions = [
    "delete",
    "reject",
    "override",
    "complete_checkout",
];

const humanizeAction = (action) => {
    if (actionLabels[action]) {
        return actionLabels[action];
    }

    return String(action || "")
        .replaceAll("_", " ")
        .replace(
            /\b\w/g,
            (character) =>
                character.toUpperCase(),
        );
};

const moduleGroup = (module) => {
    const label = String(
        module.label || ""
    );

    if (!label.includes(":")) {
        return "Other";
    }

    return label
        .split(":")[0]
        .trim();
};

const moduleDisplayLabel = (module) => {
    const label = String(
        module.label || ""
    );

    if (!label.includes(":")) {
        return label;
    }

    return label
        .split(":")
        .slice(1)
        .join(":")
        .trim();
};

const normalizedModules = computed(() =>
    props.modules.map((module) => ({
        key: module.key,

        label: moduleDisplayLabel(
            module
        ),

        full_label: module.label,

        group: moduleGroup(module),

        actions: Array.isArray(
            module.actions
        )
            ? module.actions
            : [],
    })),
);

const groupedModules = computed(() => {
    const keyword =
        permissionSearch.value
            .trim()
            .toLowerCase();

    const filtered =
        normalizedModules.value.filter(
            (module) => {
                if (!keyword) {
                    return true;
                }

                return [
                    module.label,
                    module.full_label,
                    module.group,
                    module.key,

                    ...module.actions.map(
                        (action) =>
                            humanizeAction(
                                action
                            ),
                    ),
                ].some((value) =>
                    String(value || "")
                        .toLowerCase()
                        .includes(keyword),
                );
            },
        );

    return filtered.reduce(
        (groups, module) => {
            if (!groups[module.group]) {
                groups[module.group] = [];
            }

            groups[module.group].push(
                module
            );

            return groups;
        },
        {},
    );
});

const selectedPermissionCount =
    computed(() =>
        Object.values(matrix).reduce(
            (total, actions) =>
                total
                + (
                    Array.isArray(actions)
                        ? actions.length
                        : 0
                ),
            0,
        ),
    );

const totalPermissionCount =
    computed(() =>
        normalizedModules.value.reduce(
            (total, module) =>
                total
                + module.actions.length,
            0,
        ),
    );

const ensureModuleMatrix = (
    moduleKey
) => {
    if (
        !Array.isArray(
            matrix[moduleKey]
        )
    ) {
        matrix[moduleKey] = [];
    }
};

const hasAction = (
    moduleKey,
    action
) => {
    ensureModuleMatrix(moduleKey);

    return matrix[moduleKey].includes(
        action
    );
};

const moduleHasAny = (module) => {
    ensureModuleMatrix(module.key);

    return (
        matrix[module.key].length > 0
    );
};

const moduleHasAll = (module) => {
    ensureModuleMatrix(module.key);

    if (!module.actions.length) {
        return false;
    }

    return module.actions.every(
        (action) =>
            matrix[module.key].includes(
                action
            ),
    );
};

const groupHasAny = (modules) =>
    modules.some((module) =>
        moduleHasAny(module)
    );

const groupHasAll = (modules) =>
    modules.length > 0
    && modules.every((module) =>
        moduleHasAll(module)
    );

const toggleAction = (
    module,
    action
) => {
    ensureModuleMatrix(module.key);

    const current = [
        ...matrix[module.key],
    ];

    /*
     * Removing view clears the complete module.
     * Other actions without view should not remain.
     */
    if (
        action === "view"
        && current.includes("view")
    ) {
        matrix[module.key] = [];
        return;
    }

    if (current.includes(action)) {
        matrix[module.key] =
            current.filter(
                (item) =>
                    item !== action
            );

        return;
    }

    const next = [
        ...current,
        action,
    ];

    /*
     * Selecting any operational action
     * automatically includes view if the
     * module supports view.
     */
    if (
        action !== "view"
        && module.actions.includes(
            "view"
        )
        && !next.includes("view")
    ) {
        next.push("view");
    }

    matrix[module.key] = [
        ...new Set(next),
    ];
};

const grantAllForModule = (
    module
) => {
    matrix[module.key] = [
        ...module.actions,
    ];
};

const clearModule = (
    moduleKey
) => {
    matrix[moduleKey] = [];
};

const grantGroup = (modules) => {
    modules.forEach((module) => {
        grantAllForModule(module);
    });
};

const clearGroup = (modules) => {
    modules.forEach((module) => {
        clearModule(module.key);
    });
};

const grantEverything = () => {
    normalizedModules.value.forEach(
        (module) => {
            grantAllForModule(module);
        },
    );
};

const clearEverything = () => {
    normalizedModules.value.forEach(
        (module) => {
            clearModule(module.key);
        },
    );
};

const toggleGroup = (
    groupName
) => {
    expandedGroups[groupName] =
        !expandedGroups[groupName];
};

const openPermissions = (user) => {
    permUser.value = user;

    Object.keys(matrix).forEach(
        (key) => {
            delete matrix[key];
        },
    );

    normalizedModules.value.forEach(
        (module) => {
            const existingActions =
                user.permissions?.[
                    module.key
                ] || [];

            matrix[module.key] =
                existingActions.filter(
                    (action) =>
                        module.actions.includes(
                            action
                        ),
                );
        },
    );

    Object.keys(
        groupedModules.value
    ).forEach((groupName) => {
        expandedGroups[groupName] =
            true;
    });

    permissionSearch.value = "";
    permOpen.value = true;
};

const closePermissions = () => {
    if (permSaving.value) {
        return;
    }

    permOpen.value = false;
    permUser.value = null;
    permissionSearch.value = "";
};

const savePermissions = () => {
    if (!permUser.value) {
        return;
    }

    permSaving.value = true;

    const permissions = {};

    normalizedModules.value.forEach(
        (module) => {
            permissions[module.key] = [
                ...(matrix[module.key] || []),
            ];
        },
    );

    router.put(
        route(
            "admin.users.permissions",
            {
                user:
                    permUser.value.id,
            },
        ),

        {
            permissions,
        },

        {
            preserveScroll: true,

            onSuccess: () => {
                permOpen.value = false;
                permUser.value = null;
            },

            onFinish: () => {
                permSaving.value = false;
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
                    <p class="text-sm text-gray-700 mt-0.5">
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
                    <thead class="bg-gray-50 text-gray-700 text-xs uppercase">
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
                        <tr v-for="u in users.data" :key="u.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ u.name }}
                                <span
                                    v-if="u.id === currentUserId"
                                    class="text-xs text-gray-600 font-normal"
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
                    <p class="text-xs text-gray-600 mt-1">
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
        <Modal
            :show="permOpen"
            maxWidth="2xl"
            @close="closePermissions"
        >
            <div
                v-if="permUser"
                class="flex max-h-[94vh] flex-col overflow-hidden"
            >
                <!-- Header -->
                <div
                    class="shrink-0 border-b border-slate-200 bg-white px-6 py-5"
                >
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div
                            class="flex items-start gap-3"
                        >
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"
                            >
                                <ShieldCheck
                                    class="h-6 w-6"
                                />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-bold text-slate-900"
                                >
                                    Manage Permissions
                                </h2>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-700"
                                >
                                    {{ permUser.name }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-slate-500"
                                >
                                    {{ permUser.email }}
                                    ·
                                    {{
                                        String(
                                            permUser.role
                                        )
                                            .replaceAll(
                                                "_",
                                                " ",
                                            )
                                            .replace(
                                                /\b\w/g,
                                                (
                                                    character,
                                                ) =>
                                                    character.toUpperCase(),
                                            )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-2 gap-2"
                        >
                            <div
                                class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-center"
                            >
                                <p
                                    class="text-xl font-bold text-indigo-700"
                                >
                                    {{
                                        selectedPermissionCount
                                    }}
                                </p>

                                <p
                                    class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-600"
                                >
                                    Selected
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center"
                            >
                                <p
                                    class="text-xl font-bold text-slate-700"
                                >
                                    {{
                                        totalPermissionCount
                                    }}
                                </p>

                                <p
                                    class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                                >
                                    Available
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-5 flex flex-col gap-3 sm:flex-row"
                    >
                        <div
                            class="relative min-w-0 flex-1"
                        >
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />

                            <input
                                v-model="
                                    permissionSearch
                                "
                                type="search"
                                placeholder="Search module or permission..."
                                class="w-full rounded-xl border-slate-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div
                            class="flex shrink-0 gap-2"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                @click="
                                    grantEverything
                                "
                            >
                                Select All
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                @click="
                                    clearEverything
                                "
                            >
                                Clear All
                            </button>
                        </div>
                    </div>

                    <div
                        class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3"
                    >
                        <p
                            class="text-xs leading-5 text-blue-700"
                        >
                            Permissions are assigned by individual
                            action. Selecting an action such as
                            <strong>Final Approve</strong> or
                            <strong>Verify Exit</strong>
                            automatically enables
                            <strong>View</strong> for that module.
                        </p>
                    </div>
                </div>

                <!-- Permission groups -->
                <div
                    class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50 px-6 py-5"
                >
                    <section
                        v-for="(
                            groupModules,
                            groupName
                        ) in groupedModules"
                        :key="groupName"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"
                        >
                            <button
                                type="button"
                                class="flex min-w-0 items-center gap-3 text-left"
                                @click="
                                    toggleGroup(
                                        groupName
                                    )
                                "
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                    :class="
                                        groupHasAny(
                                            groupModules
                                        )
                                            ? 'bg-indigo-100 text-indigo-700'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                >
                                    <ChevronDown
                                        v-if="
                                            expandedGroups[
                                                groupName
                                            ]
                                        "
                                        class="h-4 w-4"
                                    />

                                    <ChevronRight
                                        v-else
                                        class="h-4 w-4"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <h3
                                        class="truncate text-sm font-bold text-slate-900"
                                    >
                                        {{ groupName }}
                                    </h3>

                                    <p
                                        class="mt-0.5 text-xs text-slate-500"
                                    >
                                        {{
                                            groupModules.length
                                        }}
                                        module(s)
                                    </p>
                                </div>
                            </button>

                            <div
                                class="flex items-center gap-2"
                            >
                                <span
                                    v-if="
                                        groupHasAll(
                                            groupModules
                                        )
                                    "
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700"
                                >
                                    <Check
                                        class="h-3 w-3"
                                    />
                                    Full group access
                                </span>

                                <button
                                    type="button"
                                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-[11px] font-semibold text-indigo-700"
                                    @click="
                                        grantGroup(
                                            groupModules
                                        )
                                    "
                                >
                                    Select Group
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600"
                                    @click="
                                        clearGroup(
                                            groupModules
                                        )
                                    "
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div
                            v-show="
                                expandedGroups[
                                    groupName
                                ]
                            "
                            class="grid grid-cols-1 gap-4 p-4 xl:grid-cols-2"
                        >
                            <article
                                v-for="module in groupModules"
                                :key="module.key"
                                class="overflow-hidden rounded-xl border transition"
                                :class="
                                    moduleHasAny(module)
                                        ? 'border-indigo-200 bg-indigo-50/30'
                                        : 'border-slate-200 bg-white'
                                "
                            >
                                <div
                                    class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3"
                                >
                                    <div class="min-w-0">
                                        <h4
                                            class="truncate text-sm font-bold text-slate-900"
                                        >
                                            {{
                                                module.label
                                            }}
                                        </h4>

                                        <p
                                            class="mt-1 font-mono text-[10px] text-slate-400"
                                        >
                                            {{
                                                module.key
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex shrink-0 gap-1"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-lg px-2.5 py-1 text-[10px] font-bold transition"
                                            :class="
                                                moduleHasAll(
                                                    module
                                                )
                                                    ? 'bg-indigo-600 text-white'
                                                    : 'bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700'
                                            "
                                            @click="
                                                grantAllForModule(
                                                    module
                                                )
                                            "
                                        >
                                            All
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 transition hover:bg-slate-200"
                                            @click="
                                                clearModule(
                                                    module.key
                                                )
                                            "
                                        >
                                            None
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-2 p-3 sm:grid-cols-2"
                                >
                                    <label
                                        v-for="action in module.actions"
                                        :key="action"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                                        :class="
                                            hasAction(
                                                module.key,
                                                action
                                            )
                                                ? dangerousActions.includes(
                                                    action
                                                )
                                                    ? 'border-red-300 bg-red-50'
                                                    : 'border-indigo-300 bg-indigo-50'
                                                : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                                        "
                                    >
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            :checked="
                                                hasAction(
                                                    module.key,
                                                    action
                                                )
                                            "
                                            @change="
                                                toggleAction(
                                                    module,
                                                    action
                                                )
                                            "
                                        />

                                        <div class="min-w-0">
                                            <p
                                                class="text-xs font-semibold"
                                                :class="
                                                    dangerousActions.includes(
                                                        action
                                                    )
                                                        ? 'text-red-800'
                                                        : hasAction(
                                                                module.key,
                                                                action
                                                            )
                                                        ? 'text-indigo-800'
                                                        : 'text-slate-700'
                                                "
                                            >
                                                {{
                                                    humanizeAction(
                                                        action
                                                    )
                                                }}
                                            </p>

                                            <p
                                                class="mt-0.5 break-all font-mono text-[9px] text-slate-400"
                                            >
                                                {{ action }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </article>
                        </div>
                    </section>

                    <div
                        v-if="
                            !Object.keys(
                                groupedModules
                            ).length
                        "
                        class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
                    >
                        <KeyRound
                            class="mx-auto h-10 w-10 text-slate-300"
                        />

                        <p
                            class="mt-3 text-sm font-bold text-slate-700"
                        >
                            No matching permissions
                        </p>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            Try another module or action name.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex shrink-0 flex-col gap-3 border-t border-slate-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p
                        class="text-xs text-slate-500"
                    >
                        Permission changes apply on the user’s next
                        request or page reload.
                    </p>

                    <div
                        class="flex justify-end gap-3"
                    >
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                            :disabled="permSaving"
                            @click="
                                closePermissions
                            "
                        >
                            Cancel
                        </button>

                        <PrimaryButton
                            type="button"
                            :disabled="permSaving"
                            @click="
                                savePermissions
                            "
                        >
                            {{
                                permSaving
                                    ? "Saving..."
                                    : "Save Permissions"
                            }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>