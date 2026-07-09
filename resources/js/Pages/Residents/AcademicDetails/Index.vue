<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { GraduationCap, Search, Pencil } from "lucide-vue-next";

const props = defineProps({
    residents: Object,
    filters: Object,
    filterOptions: Object,
});

const filters = reactive({
    search: props.filters?.search || "",
    course: props.filters?.course || "",
    batch: props.filters?.batch || "",
});
let t = null;
const applyFilters = (debounce = false) => {
    clearTimeout(t);
    const fn = () =>
        router.get(
            "/residents/academic-details",
            { ...filters },
            { preserveState: true },
        );
    debounce ? (t = setTimeout(fn, 400)) : fn();
};

const editOpen = ref(false);
const editForm = useForm({
    course: "",
    institute: "",
    batch: "",
    year: "",
    roll_number: "",
});
const editingId = ref(null);

const openEdit = (r) => {
    editingId.value = r.id;
    editForm.course = r.course || "";
    editForm.institute = r.institute || "";
    editForm.batch = r.batch || "";
    editForm.year = r.year || "";
    editForm.roll_number = r.roll_number || "";
    editOpen.value = true;
};

const submitEdit = () =>
    editForm.put(`/residents/academic-details/${editingId.value}`, {
        onSuccess: () => (editOpen.value = false),
    });
</script>

<template>
    <Head title="Academic Details" />
    <AuthenticatedLayout>
        <template #header>Residents / Academic Details</template>

        <div class="space-y-5">
            <div>
                <h1
                    class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                >
                    <GraduationCap class="h-6 w-6 text-blue-600" /> Academic
                    Details
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Course, institute, batch, year and roll number — kept
                    separate from the main resident profile
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-[220px]">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                    />
                    <input
                        v-model="filters.search"
                        @input="applyFilters(true)"
                        placeholder="Search by name or roll no..."
                        class="w-full pl-9 rounded-lg border-gray-300 text-sm"
                    />
                </div>
                <select
                    v-model="filters.course"
                    @change="applyFilters()"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Courses</option>
                    <option
                        v-for="c in filterOptions.courses"
                        :key="c"
                        :value="c"
                    >
                        {{ c }}
                    </option>
                </select>
                <select
                    v-model="filters.batch"
                    @change="applyFilters()"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Batches</option>
                    <option
                        v-for="b in filterOptions.batches"
                        :key="b"
                        :value="b"
                    >
                        {{ b }}
                    </option>
                </select>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Course</th>
                            <th class="text-left px-4 py-3">Institute</th>
                            <th class="text-left px-4 py-3">Batch</th>
                            <th class="text-left px-4 py-3">Year</th>
                            <th class="text-left px-4 py-3">Roll No</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in residents.data" :key="r.id">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img
                                        v-if="r.photo_url"
                                        :src="`/storage/${r.photo_url}`"
                                        class="h-16 w-16 rounded-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-xs text-gray-400"
                                    >
                                        {{ r.first_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ r.first_name }} {{ r.last_name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ r.resident_code }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ r.course || "—" }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ r.institute || "—" }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ r.batch || "—" }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ r.year || "—" }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ r.roll_number || "—" }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-gray-100"
                                    @click="openEdit(r)"
                                >
                                    <Pencil class="h-3.5 w-3.5 text-gray-600" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!residents.data.length">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No residents found
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="residents.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in residents.links" :key="link.label">
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

        <Modal :show="editOpen" @close="editOpen = false">
            <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Edit Academic Details
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Course" /><TextInput
                            v-model="editForm.course"
                        />
                    </div>
                    <div>
                        <InputLabel value="Institute" /><TextInput
                            v-model="editForm.institute"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <InputLabel value="Batch" /><TextInput
                            v-model="editForm.batch"
                        />
                    </div>
                    <div>
                        <InputLabel value="Year" /><TextInput
                            type="number"
                            v-model="editForm.year"
                        />
                    </div>
                    <div>
                        <InputLabel value="Roll Number" /><TextInput
                            v-model="editForm.roll_number"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="editOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="editForm.processing"
                        >Save Changes</PrimaryButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
