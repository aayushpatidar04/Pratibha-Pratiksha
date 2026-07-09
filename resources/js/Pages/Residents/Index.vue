<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive, computed, watch } from "vue";
import {
    Users,
    Plus,
    Search,
    Camera,
    UploadCloud,
    Settings,
    History,
    DoorOpen,
    ChevronDown,
    MoreVertical,
    Eye,
    Pencil,
    GraduationCap,
    Bike,
    ArrowRightLeft,
    LogOut as LogOutIcon,
    Trash2,
    Filter,
    X,
    Phone,
    Mail,
} from "lucide-vue-next";

const props = defineProps({
    view: String,
    tab: String,
    sub: String,
    filters: Object,
    studentWise: Object,
    hostelWise: Object,
    tabCounts: Object,
    buildings: Array,
    floors: Array,
    rooms: Array,
});

// ------------------------------------------------------------------
// Tabs / view / filters — every change round-trips to the server since
// the dataset (and even which prop is populated) differs per view.
// ------------------------------------------------------------------
const filters = reactive({
    search: props.filters?.search || "",
    gender: props.filters?.gender || "",
    course: props.filters?.course || "",
    bookings_filter: props.filters?.bookings_filter || "",
    country: props.filters?.country || "",
    state: props.filters?.state || "",
    city: props.filters?.city || "",
    living_up_to: props.filters?.living_up_to || "",
});
const showFilters = ref(false);

const go = (extra = {}) => {
    router.get(
        "/residents",
        {
            tab: props.tab,
            sub: props.sub,
            view: props.view,
            ...filters,
            ...extra,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const switchTab = (tab) => go({ tab, sub: "active" });
const switchSub = (sub) => go({ sub });
const switchView = (view) => go({ view });
const applyFilters = () => go();
const clearFilters = () => {
    Object.keys(filters).forEach((k) => (filters[k] = ""));
    go({
        search: undefined,
        gender: undefined,
        course: undefined,
        bookings_filter: undefined,
        country: undefined,
        state: undefined,
        city: undefined,
        living_up_to: undefined,
    });
};
const toggleBookingFilter = (key) =>
    go({ bookings_filter: filters.bookings_filter === key ? "" : key });

let searchTimeout = null;
watch(
    () => filters.search,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (filters.search.length >= 3 || filters.search.length === 0) go();
        }, 400);
    },
);

const statusColor = {
    active: "green",
    inactive: "gray",
    suspended: "red",
    left: "blue",
    upcoming: "amber",
};
const genderColor = { male: "blue", female: "pink", other: "purple" };

// ------------------------------------------------------------------
// Row actions dropdown
// ------------------------------------------------------------------
const openActionsFor = ref(null);
const toggleActions = (id) => {
    openActionsFor.value = openActionsFor.value === id ? null : id;
};

const destroy = (r) => {
    openActionsFor.value = null;
    if (confirm(`Remove ${r.first_name} ${r.last_name}?`))
        router.delete(`/residents/${r.id}`);
};

// ------------------------------------------------------------------
// View Details modal
// ------------------------------------------------------------------
const viewOpen = ref(false);
const viewing = ref(null);
const openView = (r) => {
    viewing.value = r;
    viewOpen.value = true;
    openActionsFor.value = null;
};

// ------------------------------------------------------------------
// Add Resident modal (unchanged from before — photo required, optional allotment)
// ------------------------------------------------------------------
const createOpen = ref(false);
const photoPreview = ref(null);
const createForm = useForm({
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    whatsapp_number: "",
    date_of_birth: "",
    gender: "male",
    blood_group: "",
    address: "",
    city: "",
    state: "",
    country: "India",
    pincode: "",
    course: "",
    year: "",
    batch: "",
    roll_number: "",
    institute: "",
    father_name: "",
    father_phone: "",
    mother_name: "",
    mother_phone: "",
    status: "upcoming",
    photo: null,
    building_id: "",
    floor_id: "",
    room_id: "",
    bed_id: "",
    check_in_date: "",
    rent_amount: "",
    deposit_amount: "",
});
const onPhotoChange = (e) => {
    const file = e.target.files[0];
    createForm.photo = file || null;
    photoPreview.value = file ? URL.createObjectURL(file) : null;
};
const floorsForBuilding = computed(() =>
    props.floors.filter(
        (f) => f.building_id === Number(createForm.building_id),
    ),
);
const roomsForFloor = computed(() =>
    props.rooms.filter((r) => r.floor_id === Number(createForm.floor_id)),
);
const selectedRoom = computed(() =>
    props.rooms.find((r) => r.id === Number(createForm.room_id)),
);
const vacantBeds = computed(
    () => selectedRoom.value?.beds?.filter((b) => b.status === "vacant") || [],
);
const onRoomChange = () => {
    createForm.bed_id = "";
    if (selectedRoom.value)
        createForm.rent_amount = selectedRoom.value.monthly_rent_per_bed;
};
const submitCreate = () => {
    createForm.post("/residents", {
        forceFormData: true,
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
            photoPreview.value = null;
        },
    });
};

// ------------------------------------------------------------------
// Bulk upload modal
// ------------------------------------------------------------------
const bulkOpen = ref(false);
const bulkForm = useForm({ file: null });
const onBulkFileChange = (e) => {
    bulkForm.file = e.target.files[0] || null;
};
const submitBulk = () => {
    bulkForm.post("/residents/bulk-upload", {
        forceFormData: true,
        onSuccess: () => {
            bulkOpen.value = false;
            bulkForm.reset();
        },
    });
};

const activeCount = computed(() => props.tabCounts?.active ?? 0);
</script>

<template>
    <Head title="Residents" />
    <AuthenticatedLayout>
        <template #header>Residents</template>

        <div class="space-y-4">
            <!-- Top action bar -->
            <div
                class="flex flex-col lg:flex-row lg:items-center justify-between gap-3"
            >
                <div class="relative w-full lg:w-80">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                    />
                    <input
                        v-model="filters.search"
                        placeholder="Start typing (min 3 characters)"
                        class="w-full pl-9 rounded-lg border-gray-300 text-sm"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        class="px-3 py-2 text-xs rounded-lg bg-green-600 text-white flex items-center gap-1.5"
                        @click="bulkOpen = true"
                    >
                        <UploadCloud class="h-3.5 w-3.5" /> Bulk upload
                    </button>
                    <PrimaryButton type="button" @click="createOpen = true"
                        ><Plus class="h-4 w-4" /> Add new</PrimaryButton
                    >
                    <Link
                        href="/residents/kyc/settings"
                        class="px-3 py-2 text-xs rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600"
                    >
                        <Settings class="h-3.5 w-3.5" /> Settings
                    </Link>
                    <Link
                        href="/residents/past"
                        class="px-3 py-2 text-xs rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600"
                    >
                        <History class="h-3.5 w-3.5" /> Past Residents
                    </Link>
                    <Link
                        href="/residents/room-change-requests"
                        class="px-3 py-2 text-xs rounded-lg bg-amber-400 text-white flex items-center gap-1.5"
                    >
                        <ArrowRightLeft class="h-3.5 w-3.5" /> Room Change
                        Requests
                    </Link>
                </div>
            </div>

            <!-- Top-level tabs -->
            <div
                class="flex gap-1 bg-white p-1 rounded-xl border border-gray-100 w-fit"
            >
                <button
                    v-for="t in [
                        { key: 'residents', label: 'Residents' },
                        {
                            key: 'upcoming_bookings',
                            label: 'Upcoming Bookings',
                        },
                        {
                            key: 'left_suspended',
                            label: 'Left-Out / Suspended',
                        },
                    ]"
                    :key="t.key"
                    @click="switchTab(t.key)"
                    class="px-4 py-2 rounded-lg text-sm font-medium"
                    :class="
                        tab === t.key
                            ? 'bg-blue-600 text-white'
                            : 'text-gray-600 hover:bg-gray-50'
                    "
                >
                    {{ t.label }}
                </button>
            </div>

            <!-- Sub-tabs (Residents tab only) -->
            <div
                v-if="tab === 'residents'"
                class="flex gap-5 border-b border-gray-200 text-sm"
            >
                <button
                    v-for="s in [
                        {
                            key: 'active',
                            label: `Active Resident (${activeCount})`,
                        },
                        { key: 'student_list', label: 'Student List' },
                        { key: 'new_joiners', label: 'New Joiners' },
                    ]"
                    :key="s.key"
                    @click="switchSub(s.key)"
                    class="pb-2 -mb-px border-b-2"
                    :class="
                        sub === s.key
                            ? 'border-blue-600 text-blue-600 font-medium'
                            : 'border-transparent text-gray-500'
                    "
                >
                    {{ s.label }}
                </button>
            </div>

            <!-- Filters toggle + view switch -->
            <div class="flex flex-wrap items-center justify-between gap-2">
                <button
                    class="px-3 py-1.5 text-xs rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600"
                    @click="showFilters = !showFilters"
                >
                    <Filter class="h-3.5 w-3.5" /> Filters
                </button>
                <div class="flex items-center gap-3">
                    <label
                        class="flex items-center gap-1.5 text-xs text-gray-600"
                    >
                        <input
                            type="radio"
                            :checked="view === 'student'"
                            @change="switchView('student')"
                        />
                        Student Wise
                    </label>
                    <label
                        class="flex items-center gap-1.5 text-xs text-gray-600"
                    >
                        <input
                            type="radio"
                            :checked="view === 'hostel'"
                            @change="switchView('hostel')"
                        />
                        Hostel Wise
                    </label>
                </div>
            </div>

            <!-- Filters panel -->
            <div
                v-if="showFilters"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 space-y-3"
            >
                <div
                    v-if="studentWise"
                    class="flex flex-wrap items-center gap-2"
                >
                    <span class="text-xs text-gray-500">Bookings:</span>
                    <button
                        class="px-3 py-1 text-xs rounded-full border"
                        :class="
                            filters.bookings_filter === 'no_living_end_date'
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'border-gray-300 text-gray-600'
                        "
                        @click="toggleBookingFilter('no_living_end_date')"
                    >
                        No Living End Date ({{
                            studentWise.bookingCounts.no_living_end_date
                        }})
                    </button>
                    <button
                        class="px-3 py-1 text-xs rounded-full border"
                        :class="
                            filters.bookings_filter === 'leaving_7'
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'border-gray-300 text-gray-600'
                        "
                        @click="toggleBookingFilter('leaving_7')"
                    >
                        Leaving After 7 Days ({{
                            studentWise.bookingCounts.leaving_7
                        }})
                    </button>
                    <button
                        class="px-3 py-1 text-xs rounded-full border"
                        :class="
                            filters.bookings_filter === 'leaving_30'
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'border-gray-300 text-gray-600'
                        "
                        @click="toggleBookingFilter('leaving_30')"
                    >
                        Leaving After 30 Days ({{
                            studentWise.bookingCounts.leaving_30
                        }})
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1"
                            >Gender</label
                        >
                        <select
                            v-model="filters.gender"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">All</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div v-if="studentWise">
                        <label class="block text-xs text-gray-500 mb-1"
                            >Country</label
                        >
                        <select
                            v-model="filters.country"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">No data Available</option>
                            <option
                                v-for="c in studentWise.locationOptions
                                    .countries"
                                :key="c"
                                :value="c"
                            >
                                {{ c }}
                            </option>
                        </select>
                    </div>
                    <div v-if="studentWise">
                        <label class="block text-xs text-gray-500 mb-1"
                            >State</label
                        >
                        <select
                            v-model="filters.state"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">No data Available</option>
                            <option
                                v-for="s in studentWise.locationOptions.states"
                                :key="s"
                                :value="s"
                            >
                                {{ s }}
                            </option>
                        </select>
                    </div>
                    <div v-if="studentWise">
                        <label class="block text-xs text-gray-500 mb-1"
                            >City</label
                        >
                        <select
                            v-model="filters.city"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">No data Available</option>
                            <option
                                v-for="c in studentWise.locationOptions.cities"
                                :key="c"
                                :value="c"
                            >
                                {{ c }}
                            </option>
                        </select>
                    </div>
                    <div v-if="studentWise">
                        <label class="block text-xs text-gray-500 mb-1"
                            >Tentative Living Up To</label
                        >
                        <input
                            type="date"
                            v-model="filters.living_up_to"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        class="px-4 py-1.5 text-sm rounded-lg border border-gray-300"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                    <button
                        class="px-4 py-1.5 text-sm rounded-lg bg-blue-600 text-white"
                        @click="applyFilters"
                    >
                        Apply
                    </button>
                </div>
            </div>

            <!-- ================= STUDENT-WISE TABLE ================= -->
            <div
                v-if="view === 'student' && studentWise"
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Id</th>
                            <th class="text-left px-4 py-3">Name</th>
                            <th class="text-left px-4 py-3">Room</th>
                            <th class="text-left px-4 py-3">
                                Academic Details
                            </th>
                            <th class="text-left px-4 py-3">Parent Details</th>
                            <th class="text-left px-4 py-3">Booked Stay</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in studentWise.residents.data" :key="r.id">
                            <td class="px-4 py-3 text-gray-400">{{ r.id }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img
                                        v-if="r.photo_url"
                                        :src="`/storage/${r.photo_url}`"
                                        class="h-9 w-9 rounded-full object-cover shrink-0"
                                    />
                                    <div
                                        v-else
                                        class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-xs shrink-0"
                                    >
                                        {{ r.first_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ r.first_name }} {{ r.last_name }}
                                        </p>
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="text-[10px] text-gray-400"
                                                >{{ r.resident_code }}</span
                                            >
                                            <Badge
                                                :color="genderColor[r.gender]"
                                                >{{ r.gender }}</Badge
                                            >
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div
                                    v-if="r.current_stay"
                                    class="flex items-center gap-1.5 text-xs"
                                >
                                    <DoorOpen
                                        class="h-3.5 w-3.5 text-blue-500"
                                    />
                                    {{ r.current_stay.building?.name }} -
                                    {{ r.current_stay.room?.room_number }} ({{
                                        r.current_stay.bed?.bed_number
                                    }})
                                </div>
                                <span v-else class="text-xs text-amber-600"
                                    >Not assigned</span
                                >
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <p v-if="r.course">
                                    {{ r.course }} · Year {{ r.year || "—" }}
                                </p>
                                <p class="text-gray-400">
                                    {{ r.institute || "—" }}
                                </p>
                                <p class="text-gray-400">
                                    Batch: {{ r.batch || "—" }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <p v-if="r.father_name">{{ r.father_name }}</p>
                                <p
                                    v-if="r.father_phone"
                                    class="flex items-center gap-1 text-gray-400"
                                >
                                    <Phone class="h-3 w-3" />{{
                                        r.father_phone
                                    }}
                                </p>
                                <p
                                    v-if="!r.father_name && !r.father_phone"
                                    class="text-gray-300"
                                >
                                    —
                                </p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <p v-if="r.current_stay">
                                    From {{ r.current_stay.check_in_date }}
                                </p>
                                <p
                                    v-if="
                                        r.current_stay?.expected_check_out_date
                                    "
                                >
                                    To
                                    {{ r.current_stay.expected_check_out_date }}
                                </p>
                                <p
                                    v-else-if="r.current_stay"
                                    class="text-gray-400"
                                >
                                    No end date
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[r.status]">{{
                                    r.status
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right relative">
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-gray-100"
                                    @click="toggleActions(r.id)"
                                >
                                    <MoreVertical
                                        class="h-4 w-4 text-gray-500"
                                    />
                                </button>
                                <div
                                    v-if="openActionsFor === r.id"
                                    class="absolute right-4 top-10 z-20 w-52 bg-white rounded-lg shadow-lg border border-gray-100 py-1 text-left"
                                >
                                    <button
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        @click="openView(r)"
                                    >
                                        <Eye class="h-3.5 w-3.5" /> View Details
                                    </button>
                                    <button
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        @click="openView(r)"
                                    >
                                        <Pencil class="h-3.5 w-3.5" /> Edit
                                        Details
                                    </button>
                                    <Link
                                        :href="`/residents/academic-details?search=${encodeURIComponent(r.first_name)}`"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        ><GraduationCap class="h-3.5 w-3.5" />
                                        Edit Academic Details</Link
                                    >
                                    <Link
                                        href="/residents/vehicles"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        ><Bike class="h-3.5 w-3.5" /> Vehicle
                                        Info</Link
                                    >
                                    <Link
                                        href="/residents/room-change-requests"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        ><ArrowRightLeft class="h-3.5 w-3.5" />
                                        Room Change Request</Link
                                    >
                                    <Link
                                        href="/checkinout"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        ><LogOutIcon class="h-3.5 w-3.5" />
                                        Check-Out</Link
                                    >
                                    <button
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50"
                                        @click="destroy(r)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" /> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!studentWise.residents.data.length">
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No residents found
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div
                    v-if="studentWise.residents.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template
                        v-for="link in studentWise.residents.links"
                        :key="link.label"
                    >
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

            <!-- ================= HOSTEL-WISE CARD GRID ================= -->
            <div v-else-if="view === 'hostel' && hostelWise" class="space-y-6">
                <div
                    v-for="building in hostelWise.buildings"
                    :key="building.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">
                        {{ building.name }}
                    </h2>
                    <div
                        v-for="floor in building.floors"
                        :key="floor.id"
                        class="mb-5"
                    >
                        <p class="text-xs font-semibold text-gray-500 mb-2">
                            {{ floor.name }}
                        </p>
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"
                        >
                            <div
                                v-for="room in floor.rooms"
                                :key="room.id"
                                class="border border-gray-100 rounded-lg p-3"
                            >
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Room {{ room.room_number }}
                                    </p>
                                    <span class="text-[10px] text-gray-400"
                                        >{{ room.occupied_beds }}/{{
                                            room.capacity
                                        }}</span
                                    >
                                </div>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="bed in room.beds"
                                        :key="bed.id"
                                        class="flex items-center gap-2 text-xs"
                                    >
                                        <span
                                            class="w-8 text-gray-400 shrink-0"
                                            >{{ bed.bed_number }}</span
                                        >
                                        <template v-if="bed.resident">
                                            <img
                                                v-if="bed.resident.photo_url"
                                                :src="`/storage/${bed.resident.photo_url}`"
                                                class="h-5 w-5 rounded-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="h-5 w-5 rounded-full bg-gray-100 flex items-center justify-center text-[9px] text-gray-400"
                                            >
                                                {{
                                                    bed.resident.name.charAt(0)
                                                }}
                                            </div>
                                            <span
                                                class="text-gray-700 truncate"
                                                >{{ bed.resident.name }}</span
                                            >
                                        </template>
                                        <span v-else class="text-gray-300"
                                            >Vacant</span
                                        >
                                    </div>
                                    <p
                                        v-if="!room.beds.length"
                                        class="text-xs text-gray-300"
                                    >
                                        No beds
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p
                    v-if="!hostelWise.buildings.length"
                    class="text-sm text-gray-400 text-center py-10"
                >
                    No buildings yet
                </p>
            </div>
        </div>

        <!-- View / basic edit modal -->
        <Modal :show="viewOpen" @close="viewOpen = false">
            <div class="p-6 space-y-3" v-if="viewing">
                <div class="flex items-center gap-3">
                    <img
                        v-if="viewing.photo_url"
                        :src="`/storage/${viewing.photo_url}`"
                        class="h-14 w-14 rounded-xl object-cover"
                    />
                    <div
                        v-else
                        class="h-14 w-14 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400"
                    >
                        {{ viewing.first_name?.charAt(0) }}
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ viewing.first_name }} {{ viewing.last_name }}
                        </h2>
                        <p class="text-xs text-gray-400">
                            {{ viewing.resident_code }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm pt-2">
                    <div>
                        <p class="text-xs text-gray-400">Phone</p>
                        <p>{{ viewing.phone }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <p>{{ viewing.email || "—" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Gender</p>
                        <p class="capitalize">{{ viewing.gender }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Course</p>
                        <p>{{ viewing.course || "—" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Father's Name</p>
                        <p>{{ viewing.father_name || "—" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Father's Phone</p>
                        <p>{{ viewing.father_phone || "—" }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400">Room</p>
                        <p v-if="viewing.current_stay">
                            {{ viewing.current_stay.room?.room_number }} · Bed
                            {{ viewing.current_stay.bed?.bed_number }} —
                            {{ viewing.current_stay.building?.name }}
                        </p>
                        <p v-else class="text-amber-600">
                            Not assigned — use Check-In / Check-Out to allot a
                            room
                        </p>
                    </div>
                </div>
                <div class="flex justify-end pt-4">
                    <button
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="viewOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Add Resident modal -->
        <Modal :show="createOpen" @close="createOpen = false" maxWidth="xl">
            <form
                @submit.prevent="submitCreate"
                class="p-6 space-y-4 max-h-[80vh] overflow-y-auto"
            >
                <h2 class="text-lg font-semibold text-gray-900">
                    Add New Resident
                </h2>

                <div class="flex items-center gap-4">
                    <div>
                        <InputLabel value="Photograph *" />

                        <div
                            class="relative group"
                            @dragover.prevent
                            @drop.prevent="handleDrop"
                        >
                            <!-- Hidden native input -->
                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/*"
                                @change="onPhotoChange"
                                required
                                class="sr-only"
                                id="photo-upload"
                            />

                            <!-- Styled upload area / preview -->
                            <label
                                for="photo-upload"
                                :class="[
                                    'flex flex-col items-center justify-center w-48 h-48 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200',
                                    createForm.errors.photo
                                        ? 'border-red-300 bg-red-50 hover:bg-red-100'
                                        : 'border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-gray-400',
                                    photoPreview
                                        ? 'border-solid border-indigo-200 bg-indigo-50'
                                        : '',
                                ]"
                            >
                                <div
                                    v-if="!photoPreview"
                                    class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center"
                                >
                                    <svg
                                        class="w-8 h-8 mb-3 text-gray-400 group-hover:text-gray-500 transition-colors"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <p class="text-sm text-gray-600">
                                        <span
                                            class="font-semibold text-indigo-600 hover:text-indigo-700"
                                            >Click to upload</span
                                        >
                                        or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        PNG, JPG, GIF up to 2MB
                                    </p>
                                </div>

                                <!-- Image Preview -->
                                <div v-else class="relative w-full h-full p-2">
                                    <img
                                        :src="photoPreview"
                                        alt="Photo preview"
                                        class="w-full h-full object-cover rounded-lg"
                                    />
                                    <button
                                        type="button"
                                        @click.prevent="clearPhoto"
                                        class="absolute top-3 right-3 p-1.5 bg-white/90 backdrop-blur-sm rounded-full shadow-sm hover:bg-red-50 text-gray-500 hover:text-red-600 transition-colors"
                                        title="Remove photo"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                    <div
                                        class="absolute bottom-3 left-3 right-3"
                                    >
                                        <p
                                            class="text-xs text-gray-600 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-md truncate"
                                        >
                                            {{ photoName }}
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <InputError
                            :message="createForm.errors.photo"
                            class="mt-2"
                        />

                        <p
                            class="text-xs text-gray-400 mt-2 flex items-start gap-1.5"
                        >
                            <svg
                                class="w-3.5 h-3.5 mt-0.5 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            A clear passport-size photo is required for ID and
                            gate records.
                        </p>
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-400 uppercase pt-1">
                    Personal Details
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="First Name *" /><TextInput
                            v-model="createForm.first_name"
                            required
                        /><InputError :message="createForm.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel value="Last Name" /><TextInput
                            v-model="createForm.last_name"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Phone *" /><TextInput
                            v-model="createForm.phone"
                            required
                        /><InputError :message="createForm.errors.phone" />
                    </div>
                    <div>
                        <InputLabel value="Email" /><TextInput
                            type="email"
                            v-model="createForm.email"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Gender *" />
                        <select
                            v-model="createForm.gender"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Date of Birth" /><TextInput
                            type="date"
                            v-model="createForm.date_of_birth"
                        />
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-400 uppercase pt-1">
                    Academic
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Course" /><TextInput
                            v-model="createForm.course"
                        />
                    </div>
                    <div>
                        <InputLabel value="Institute" /><TextInput
                            v-model="createForm.institute"
                        />
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-400 uppercase pt-1">
                    Parent / Guardian
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Father's Name" /><TextInput
                            v-model="createForm.father_name"
                        />
                    </div>
                    <div>
                        <InputLabel value="Father's Phone" /><TextInput
                            v-model="createForm.father_phone"
                        />
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-400 uppercase pt-1">
                    Room Allotment (optional now, can be done later via
                    Check-In)
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Building" />
                        <select
                            v-model="createForm.building_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Not assigned yet</option>
                            <option
                                v-for="b in buildings"
                                :key="b.id"
                                :value="b.id"
                            >
                                {{ b.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Floor" />
                        <select
                            v-model="createForm.floor_id"
                            :disabled="!createForm.building_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Select floor</option>
                            <option
                                v-for="f in floorsForBuilding"
                                :key="f.id"
                                :value="f.id"
                            >
                                {{ f.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4" v-if="createForm.floor_id">
                    <div>
                        <InputLabel value="Room" />
                        <select
                            v-model="createForm.room_id"
                            @change="onRoomChange"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Select room</option>
                            <option
                                v-for="r in roomsForFloor"
                                :key="r.id"
                                :value="r.id"
                                :disabled="r.occupied_beds >= r.capacity"
                            >
                                {{ r.room_number }} ({{ r.occupied_beds }}/{{
                                    r.capacity
                                }}
                                occupied)
                            </option>
                        </select>
                    </div>
                    <div v-if="createForm.room_id">
                        <InputLabel value="Bed" />
                        <select
                            v-model="createForm.bed_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Select bed</option>
                            <option
                                v-for="bed in vacantBeds"
                                :key="bed.id"
                                :value="bed.id"
                            >
                                {{ bed.bed_number }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4" v-if="createForm.bed_id">
                    <div>
                        <InputLabel value="Check-in Date" /><TextInput
                            type="date"
                            v-model="createForm.check_in_date"
                        />
                    </div>
                    <div>
                        <InputLabel value="Monthly Rent" /><TextInput
                            type="number"
                            v-model="createForm.rent_amount"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel value="Status" />
                    <select
                        v-model="createForm.status"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="upcoming">Upcoming</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                        <option value="left">Left</option>
                    </select>
                </div>

                <div
                    class="flex justify-end gap-2 pt-2 sticky bottom-0 bg-white"
                >
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="createForm.processing">{{
                        createForm.processing ? "Saving..." : "Add Resident"
                    }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Bulk Upload modal -->
        <Modal :show="bulkOpen" @close="bulkOpen = false">
            <form @submit.prevent="submitBulk" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Bulk Upload Residents
                </h2>
                <p class="text-xs text-gray-500">
                    CSV with header row:
                    <code class="bg-gray-100 px-1 rounded"
                        >first_name,last_name,phone,email,gender,course,institute,batch,year,roll_number,father_name,father_phone</code
                    >. Photos and room allotment aren't included in bulk upload
                    — add those individually afterwards.
                </p>
                <input
                    type="file"
                    accept=".csv,.txt"
                    @change="onBulkFileChange"
                    required
                    class="text-sm"
                />
                <InputError :message="bulkForm.errors.file" />
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="bulkOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="bulkForm.processing">{{
                        bulkForm.processing ? "Uploading..." : "Upload"
                    }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
