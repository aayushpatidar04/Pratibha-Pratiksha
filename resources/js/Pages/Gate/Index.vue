<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { ShieldCheck, Plus, Check, X } from "lucide-vue-next";

const props = defineProps({
    passes: Object,
    stats: Object,
    filters: Object,
    residents: Array,
});

const statusColor = {
    pending: "amber",
    approved: "green",
    rejected: "red",
    cancelled: "gray",
    expired: "gray",
    used: "blue",
};

const filters = reactive({ status: props.filters?.status || "all" });
const applyFilters = () =>
    router.get(
        "/gate",
        { status: filters.status !== "all" ? filters.status : undefined },
        { preserveState: true, replace: true },
    );

const createOpen = ref(false);
const createForm = useForm({
    resident_id: "",
    pass_type: "day_out",
    from_time: "",
    to_time: "",
    purpose: "",
    visitor_name: "",
    visitor_phone: "",
});
const submitCreate = () =>
    createForm.post("/gate", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const setStatus = (p, status) => router.put(`/gate/${p.id}`, { status });
</script>

<template>
    <Head title="Gate Management" />
    <AuthenticatedLayout>
        <template #header>Gate Management</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <ShieldCheck class="h-6 w-6 text-blue-600" /> Gate
                        Passes
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Day outs, night passes, visitor entries and late entries
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> New Gate Pass</PrimaryButton
                >
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ stats.pending }}
                    </p>
                    <p class="text-xs text-gray-400">Pending</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ stats.approved }}
                    </p>
                    <p class="text-xs text-gray-400">Approved</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-blue-600">
                        {{ stats.used }}
                    </p>
                    <p class="text-xs text-gray-400">Used</p>
                </div>
            </div>

            <select
                v-model="filters.status"
                @change="applyFilters"
                class="rounded-lg border-gray-300 text-sm w-52"
            >
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="used">Used</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Type</th>
                            <th class="text-left px-4 py-3">From → To</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in passes.data" :key="p.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ p.resident?.first_name }}
                                {{ p.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 capitalize">
                                {{ p.pass_type.replace("_", " ") }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ new Date(p.from_time).toLocaleString() }} →
                                {{ new Date(p.to_time).toLocaleString() }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[p.status]">{{
                                    p.status
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right space-x-1">
                                <template v-if="p.status === 'pending'">
                                    <button
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-green-50"
                                        @click="setStatus(p, 'approved')"
                                    >
                                        <Check
                                            class="h-3.5 w-3.5 text-green-600"
                                        />
                                    </button>
                                    <button
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                        @click="setStatus(p, 'rejected')"
                                    >
                                        <X class="h-3.5 w-3.5 text-red-500" />
                                    </button>
                                </template>
                                <button
                                    v-if="p.status === 'approved'"
                                    class="text-xs font-medium text-blue-600 hover:underline"
                                    @click="setStatus(p, 'used')"
                                >
                                    Mark Used
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!passes.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No gate passes found
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="passes.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in passes.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 text-xs rounded-lg"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-100'
                            "
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1 text-xs text-gray-300"
                        />
                    </template>
                </div>
            </div>
        </div>

        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    New Gate Pass
                </h2>
                <div>
                    <InputLabel value="Resident *" />
                    <select
                        v-model="createForm.resident_id"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="" disabled>Select resident</option>
                        <option
                            v-for="r in residents"
                            :key="r.id"
                            :value="r.id"
                        >
                            {{ r.first_name }} {{ r.last_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Pass Type *" />
                    <select
                        v-model="createForm.pass_type"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="day_out">Day Out</option>
                        <option value="night_pass">Night Pass</option>
                        <option value="visitor_pass">Visitor Pass</option>
                        <option value="late_entry">Late Entry</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="From *" /><TextInput
                            type="datetime-local"
                            v-model="createForm.from_time"
                            required
                        />
                    </div>
                    <div>
                        <InputLabel value="To *" /><TextInput
                            type="datetime-local"
                            v-model="createForm.to_time"
                            required
                        />
                    </div>
                </div>
                <div
                    v-if="createForm.pass_type === 'visitor_pass'"
                    class="grid grid-cols-2 gap-4"
                >
                    <div>
                        <InputLabel value="Visitor Name" /><TextInput
                            v-model="createForm.visitor_name"
                        />
                    </div>
                    <div>
                        <InputLabel value="Visitor Phone" /><TextInput
                            v-model="createForm.visitor_phone"
                        />
                    </div>
                </div>
                <div>
                    <InputLabel value="Purpose" /><textarea
                        v-model="createForm.purpose"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    ></textarea>
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
                        >Create Pass</PrimaryButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
