<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import {
    Bike,
    CarFront,
    Download,
    Eye,
    FileText,
    Gauge,
    Palette,
    Pencil,
    Plus,
    Trash2,
    Upload,
    X,
} from "lucide-vue-next";
import { computed, ref } from "vue";

const props = defineProps({
    vehicles: {
        type: Array,
        default: () => [],
    },

    stats: {
        type: Object,
        default: () => ({}),
    },
});

const createOpen = ref(false);
const editOpen = ref(false);
const previewOpen = ref(false);

const selectedVehicle = ref(null);
const rcPreview = ref(null);

const createFileName = ref("");
const editFileName = ref("");

const vehicleTypes = [
    {
        value: "two_wheeler",
        label: "Two Wheeler",
    },
    {
        value: "four_wheeler",
        label: "Four Wheeler",
    },
    {
        value: "bicycle",
        label: "Bicycle",
    },
    {
        value: "other",
        label: "Other",
    },
];

const createForm = useForm({
    vehicle_type: "two_wheeler",
    vehicle_number: "",
    model: "",
    color: "",
    rc_file: null,
});

const editForm = useForm({
    vehicle_type: "",
    vehicle_number: "",
    model: "",
    color: "",
    rc_file: null,
    remove_rc_file: false,
});

const typeClasses = {
    two_wheeler: "border-blue-200 bg-blue-50 text-blue-700",

    four_wheeler: "border-purple-200 bg-purple-50 text-purple-700",

    bicycle: "border-emerald-200 bg-emerald-50 text-emerald-700",

    other: "border-slate-200 bg-slate-50 text-slate-700",
};

const vehicleIcon = (type) => {
    return type === "four_wheeler" ? CarFront : Bike;
};

const humanize = (value) => {
    return String(value || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
};

const openCreate = () => {
    createForm.reset();
    createForm.clearErrors();

    createForm.vehicle_type = "two_wheeler";

    createFileName.value = "";
    createOpen.value = true;
};

const onCreateRcChange = (event) => {
    const file = event.target.files?.[0] || null;

    createForm.rc_file = file;
    createFileName.value = file?.name || "";

    createForm.clearErrors("rc_file");
};

const submitCreate = () => {
    createForm.post(route("resident.vehicles.store"), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
            createFileName.value = "";
        },
    });
};

const openEdit = (vehicle) => {
    selectedVehicle.value = vehicle;

    editForm.reset();
    editForm.clearErrors();

    editForm.vehicle_type = vehicle.vehicle_type;

    editForm.vehicle_number = vehicle.vehicle_number;

    editForm.model = vehicle.model || "";

    editForm.color = vehicle.color || "";

    editForm.rc_file = null;
    editForm.remove_rc_file = false;

    editFileName.value = "";
    editOpen.value = true;
};

const onEditRcChange = (event) => {
    const file = event.target.files?.[0] || null;

    editForm.rc_file = file;
    editForm.remove_rc_file = false;

    editFileName.value = file?.name || "";

    editForm.clearErrors("rc_file");
};

const submitEdit = () => {
    if (!selectedVehicle.value) {
        return;
    }

    editForm
        .transform((data) => ({
            ...data,
            _method: "put",
        }))
        .post(
            route("resident.vehicles.update", {
                vehicle: selectedVehicle.value.id,
            }),
            {
                forceFormData: true,
                preserveScroll: true,

                onSuccess: () => {
                    editOpen.value = false;
                    selectedVehicle.value = null;

                    editForm.reset();
                    editFileName.value = "";
                },
            },
        );
};

const openRc = (vehicle) => {
    if (!vehicle.rc_file_url) {
        return;
    }

    if (vehicle.rc_is_pdf) {
        window.open(vehicle.rc_file_url, "_blank", "noopener,noreferrer");

        return;
    }

    selectedVehicle.value = vehicle;
    rcPreview.value = vehicle.rc_file_url;

    previewOpen.value = true;
};

const destroyVehicle = (vehicle) => {
    if (!confirm(`Remove vehicle ${vehicle.vehicle_number}?`)) {
        return;
    }

    router.delete(
        route("resident.vehicles.destroy", {
            vehicle: vehicle.id,
        }),
        {
            preserveScroll: true,
        },
    );
};

const totalVehicles = computed(() => {
    return Number(props.stats.total || 0);
});
</script>

<template>
    <Head title="My Vehicles" />

    <ResidentLayout title="My Vehicles">
        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-3xl border border-cyan-200 bg-[linear-gradient(135deg,#164e63_0%,#0891b2_52%,#22d3ee_100%)] text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-black/10"
                            >
                                <Bike class="h-7 w-7" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.18em] text-white"
                                >
                                    Resident Records
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                                >
                                    My Vehicles
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm font-medium leading-6 text-white"
                        >
                            Maintain a simple record of vehicles associated with
                            your hostel profile.
                        </p>
                    </div>

                    <div
                        class="flex items-center gap-4 rounded-2xl border border-white/20 bg-black/10 p-5"
                    >
                        <div>
                            <p class="text-3xl font-black text-white">
                                {{ totalVehicles }}
                            </p>

                            <p
                                class="mt-1 text-xs font-semibold uppercase tracking-wide text-white"
                            >
                                Registered Vehicles
                            </p>
                        </div>

                        <Gauge class="h-10 w-10 text-white" />
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <p class="text-2xl font-bold text-blue-700">
                        {{ stats.two_wheeler || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-blue-600">Two Wheelers</p>
                </div>

                <div
                    class="rounded-2xl border border-purple-200 bg-purple-50 p-4"
                >
                    <p class="text-2xl font-bold text-purple-700">
                        {{ stats.four_wheeler || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-purple-600">Four Wheelers</p>
                </div>

                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <p class="text-2xl font-bold text-emerald-700">
                        {{ stats.bicycle || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-emerald-600">Bicycles</p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <p class="text-2xl font-bold text-slate-800">
                        {{ stats.other || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">Other</p>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Registered Vehicles
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Add and maintain your vehicle details.
                        </p>
                    </div>

                    <PrimaryButton type="button" @click="openCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Vehicle
                    </PrimaryButton>
                </div>

                <div
                    v-if="vehicles.length"
                    class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <article
                        v-for="vehicle in vehicles"
                        :key="vehicle.id"
                        class="rounded-2xl border border-slate-200 p-5 transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                                    :class="typeClasses[vehicle.vehicle_type]"
                                >
                                    <component
                                        :is="vehicleIcon(vehicle.vehicle_type)"
                                        class="h-5 w-5"
                                    />
                                </div>

                                <div>
                                    <p
                                        class="text-base font-black uppercase tracking-wide text-slate-900"
                                    >
                                        {{ vehicle.vehicle_number }}
                                    </p>

                                    <span
                                        class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                        :class="
                                            typeClasses[vehicle.vehicle_type]
                                        "
                                    >
                                        {{ humanize(vehicle.vehicle_type) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p
                                    class="text-[10px] uppercase tracking-wide text-slate-400"
                                >
                                    Model
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-800"
                                >
                                    {{ vehicle.model || "Not added" }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-3">
                                <p
                                    class="text-[10px] uppercase tracking-wide text-slate-400"
                                >
                                    Color
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-800"
                                >
                                    {{ vehicle.color || "Not added" }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-5 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4"
                        >
                            <button
                                v-if="vehicle.rc_file_url"
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50"
                                title="View RC"
                                @click="openRc(vehicle)"
                            >
                                <Eye class="h-4 w-4" />
                            </button>

                            <a
                                v-if="vehicle.rc_file_url"
                                :href="
                                    route('resident.vehicles.rc.download', {
                                        vehicle: vehicle.id,
                                    })
                                "
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                title="Download RC"
                            >
                                <Download class="h-4 w-4" />
                            </a>

                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 text-amber-600 hover:bg-amber-50"
                                title="Edit"
                                @click="openEdit(vehicle)"
                            >
                                <Pencil class="h-4 w-4" />
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 hover:bg-red-50"
                                title="Delete"
                                @click="destroyVehicle(vehicle)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="mt-5 rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center"
                >
                    <Bike class="mx-auto h-12 w-12 text-slate-300" />

                    <h3 class="mt-4 text-sm font-bold text-slate-700">
                        No vehicles registered
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Add your first vehicle record.
                    </p>

                    <button
                        type="button"
                        class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                        @click="openCreate"
                    >
                        <Plus class="h-4 w-4" />
                        Add Vehicle
                    </button>
                </div>
            </section>
        </div>

        <Modal :show="createOpen" maxWidth="lg" @close="createOpen = false">
            <form class="space-y-5 p-6" @submit.prevent="submitCreate">
                <div class="flex justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Add Vehicle
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Enter the basic vehicle details.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="createOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Vehicle Type *" />

                        <select
                            v-model="createForm.vehicle_type"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option
                                v-for="type in vehicleTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.vehicle_type"
                        />
                    </div>

                    <div>
                        <InputLabel value="Vehicle Number *" />

                        <input
                            v-model="createForm.vehicle_number"
                            type="text"
                            required
                            maxlength="30"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm uppercase"
                            placeholder="RJ09AB1234"
                        />

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.vehicle_number"
                        />
                    </div>

                    <div>
                        <InputLabel value="Model" />

                        <input
                            v-model="createForm.model"
                            type="text"
                            maxlength="100"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            placeholder="Example: Activa 6G"
                        />

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.model"
                        />
                    </div>

                    <div>
                        <InputLabel value="Color" />

                        <input
                            v-model="createForm.color"
                            type="text"
                            maxlength="50"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            placeholder="Example: White"
                        />

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.color"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel value="RC File (Optional)" />

                    <label
                        class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-indigo-400 hover:bg-indigo-50"
                    >
                        <Upload class="h-7 w-7 text-indigo-500" />

                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ createFileName || "Choose RC document" }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            JPG, PNG, WEBP or PDF · Max 8 MB
                        </p>

                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="hidden"
                            @change="onCreateRcChange"
                        />
                    </label>

                    <InputError
                        class="mt-1"
                        :message="createForm.errors.rc_file"
                    />
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="createForm.processing">
                        {{
                            createForm.processing ? "Saving..." : "Add Vehicle"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="editOpen" maxWidth="lg" @close="editOpen = false">
            <form class="space-y-5 p-6" @submit.prevent="submitEdit">
                <div class="flex justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Edit Vehicle
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Update the vehicle record.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="editOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Vehicle Type *" />

                        <select
                            v-model="editForm.vehicle_type"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option
                                v-for="type in vehicleTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Vehicle Number *" />

                        <input
                            v-model="editForm.vehicle_number"
                            type="text"
                            required
                            maxlength="30"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm uppercase"
                        />

                        <InputError
                            class="mt-1"
                            :message="editForm.errors.vehicle_number"
                        />
                    </div>

                    <div>
                        <InputLabel value="Model" />

                        <input
                            v-model="editForm.model"
                            type="text"
                            maxlength="100"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        />
                    </div>

                    <div>
                        <InputLabel value="Color" />

                        <input
                            v-model="editForm.color"
                            type="text"
                            maxlength="50"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel value="Replace RC File" />

                    <label
                        class="mt-2 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 p-4"
                    >
                        <Upload class="h-5 w-5 text-indigo-600" />

                        <span class="truncate text-sm text-slate-700">
                            {{ editFileName || "Choose replacement RC file" }}
                        </span>

                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="hidden"
                            @change="onEditRcChange"
                        />
                    </label>

                    <label
                        v-if="selectedVehicle?.rc_file_url"
                        class="mt-3 flex items-center gap-2 text-sm text-red-600"
                    >
                        <input
                            v-model="editForm.remove_rc_file"
                            type="checkbox"
                            class="rounded border-slate-300 text-red-600"
                        />

                        Remove existing RC file
                    </label>

                    <InputError
                        class="mt-1"
                        :message="editForm.errors.rc_file"
                    />
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="editOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="editForm.processing">
                        {{ editForm.processing ? "Saving..." : "Save Changes" }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="previewOpen" maxWidth="4xl" @close="previewOpen = false">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Vehicle RC
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ selectedVehicle?.vehicle_number }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="previewOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
                >
                    <img
                        v-if="selectedVehicle?.rc_is_image"
                        :src="rcPreview"
                        alt="Vehicle RC"
                        class="max-h-[70vh] w-full object-contain"
                    />

                    <div
                        v-else
                        class="flex flex-col items-center justify-center p-14 text-center"
                    >
                        <FileText class="h-14 w-14 text-red-500" />

                        <p class="mt-4 text-sm font-bold text-slate-800">
                            Open the RC document to preview it.
                        </p>

                        <a
                            :href="selectedVehicle?.rc_file_url"
                            target="_blank"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            <Eye class="h-4 w-4" />
                            Open Document
                        </a>
                    </div>
                </div>
            </div>
        </Modal>
    </ResidentLayout>
</template>