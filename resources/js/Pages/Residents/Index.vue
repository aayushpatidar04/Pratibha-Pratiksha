<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import {
    ref,
    reactive,
    computed,
    watch,
    onMounted,
    onBeforeUnmount,
} from "vue";
import AccordionItem from "@/Components/AccordionItem.vue";

import {
    Users,
    User,
    Plus,
    Search,
    Footprints,
    UploadCloud,
    Upload,
    Settings,
    History,
    DoorOpen,
    PlaneTakeoff,
    MoreVertical,
    Eye,
    Pencil,
    GraduationCap,
    Bike,
    ArrowRightLeft,
    LogOut as LogOutIcon,
    Trash2,
    Filter,
    ReceiptText,
    Phone,
    ScanLine,
    Building2,
    AlertTriangle,
    Calendar,
    Clock3,
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

const toggleActions = (id, event) => {
    event.stopPropagation();

    openActionsFor.value = openActionsFor.value === id ? null : id;
};

const closeActions = () => {
    openActionsFor.value = null;
};

const handleClickOutside = (event) => {
    const clickedInsideDropdown = event.target.closest(
        "[data-resident-actions]",
    );

    if (!clickedInsideDropdown) {
        closeActions();
    }
};

const handleEscape = (event) => {
    if (event.key === "Escape") {
        closeActions();
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    document.addEventListener("keydown", handleEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
    document.removeEventListener("keydown", handleEscape);
});

const destroy = (r) => {
    openActionsFor.value = null;
    if (confirm(`Remove ${r.first_name} ${r.last_name}?`))
        router.delete(`/residents/${r.id}`);
};

// ------------------------------------------------------------------
// View Details modal
// ------------------------------------------------------------------

const residentSections = ref({
    basic: true,
    contact: false,
    academic: false,
    guardian: false,
    hostel: false,
});

const viewOpen = ref(false);
const viewing = ref(null);
const openView = (r) => {
    viewing.value = r;

    residentSections.value = {
        basic: true,
        contact: false,
        academic: false,
        guardian: false,
        hostel: false,
    };

    viewOpen.value = true;
    openActionsFor.value = null;
};

const editForm = useForm({
    first_name: "",
    last_name: "",
    phone: "",
    whatsapp_number: "",
    email: "",
    gender: "",
    date_of_birth: "",
    blood_group: "",
    address: "",
    city: "",
    state: "",
    country: "",
    pincode: "",
    institute: "",
    course: "",
    year: "",
    batch: "",
    roll_number: "",
    father_name: "",
    father_phone: "",
    father_email: "",
    mother_name: "",
    mother_phone: "",
    status: "",
    photo: null,
});

const editOpen = ref(false);
const photoPreview2 = ref(null);

const activeTab = ref("basic");

const tabs = [
    {
        key: "basic",
        title: "Basic",
        icon: User,
    },
    {
        key: "contact",
        title: "Contact",
        icon: Phone,
    },
    {
        key: "academic",
        title: "Academic",
        icon: GraduationCap,
    },
    {
        key: "guardian",
        title: "Guardian",
        icon: Users,
    },
    {
        key: "hostel",
        title: "Hostel",
        icon: Building2,
    },
];

const openEdit = (resident) => {
    viewing.value = resident;

    editForm.reset();
    editForm.clearErrors();

    photoPreview2.value = resident.photo_url
        ? `/storage/${resident.photo_url}`
        : null;

    activeTab.value = "basic";

    Object.assign(editForm, {
        id: resident.id,
        first_name: resident.first_name,
        last_name: resident.last_name,
        phone: resident.phone,
        whatsapp_number: resident.whatsapp_number,
        email: resident.email,
        gender: resident.gender,
        date_of_birth: resident.date_of_birth,
        blood_group: resident.blood_group,
        address: resident.address,
        city: resident.city,
        state: resident.state,
        country: resident.country,
        pincode: resident.pincode,
        institute: resident.institute,
        course: resident.course,
        year: resident.year,
        batch: resident.batch,
        roll_number: resident.roll_number,
        father_name: resident.father_name,
        father_phone: resident.father_phone,
        father_email: resident.father_email,
        mother_name: resident.mother_name,
        mother_phone: resident.mother_phone,
        status: resident.status,
        photo: null,
    });

    editOpen.value = true;
};

const selectPhoto = (event) => {
    const file = event.target.files[0];

    if (!file) return;

    editForm.photo = file;

    photoPreview2.value = URL.createObjectURL(file);
};

const updateResident = () => {
    editForm
        .transform((data) => ({
            ...data,
            _method: "put",
        }))
        .post(route("residents.update", editForm.id), {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                editOpen.value = false;
                photoPreview.value = null;
                editForm.reset();
            },
        });
};

const currentTab = computed(() => tabs.find((t) => t.key === activeTab.value));

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
    check_in_date: new Date().toISOString().slice(0, 10),
    expected_check_out_date: "",
    billing_basis: "monthly",
    rent_amount: "",
    daily_rate: 350,
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
    createForm.post(route("residents.store"), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
            createForm.clearErrors();

            photoPreview.value = null;
            photoName.value = "";
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

const estimatedStayDays = computed(() => {
    if (
        createForm.billing_basis !== "daily" ||
        !createForm.check_in_date ||
        !createForm.expected_check_out_date
    ) {
        return 0;
    }

    const checkIn = new Date(`${createForm.check_in_date}T00:00:00`);

    const checkOut = new Date(`${createForm.expected_check_out_date}T00:00:00`);

    if (
        Number.isNaN(checkIn.getTime()) ||
        Number.isNaN(checkOut.getTime()) ||
        checkOut < checkIn
    ) {
        return 0;
    }

    const millisecondsPerDay = 1000 * 60 * 60 * 24;

    return (
        Math.floor(
            (checkOut.getTime() - checkIn.getTime()) / millisecondsPerDay,
        ) + 1
    );
});

const estimatedDailyAmount = computed(() => {
    return estimatedStayDays.value * Number(createForm.daily_rate || 0);
});

watch(
    () => createForm.billing_basis,
    (basis) => {
        if (basis === "monthly") {
            createForm.expected_check_out_date = "";
            createForm.daily_rate = 350;
        } else {
            createForm.rent_amount = "";
            createForm.daily_rate = Number(createForm.daily_rate) || 350;
        }

        createForm.clearErrors(
            "rent_amount",
            "daily_rate",
            "expected_check_out_date",
        );
    },
);

const stayDatesOpen = ref(false);
const stayDatesResident = ref(null);

const stayDatesForm = useForm({
    check_in_date: "",
    expected_check_out_date: "",
    reason: "",
});

const openStayDates = (resident) => {
    const stay = resident.current_stay;

    if (!stay) {
        alert("No active or upcoming stay was found for this resident.");
        return;
    }

    stayDatesResident.value = resident;

    stayDatesForm.reset();
    stayDatesForm.clearErrors();

    stayDatesForm.check_in_date = stay.check_in_date || "";

    stayDatesForm.expected_check_out_date = stay.expected_check_out_date || "";

    stayDatesForm.reason = "";

    openActionsFor.value = null;
    stayDatesOpen.value = true;
};

const closeStayDates = () => {
    if (stayDatesForm.processing) {
        return;
    }

    stayDatesOpen.value = false;
    stayDatesResident.value = null;
    stayDatesForm.reset();
};

const submitStayDates = () => {
    if (!stayDatesResident.value) {
        return;
    }

    stayDatesForm.put(
        route("residents.stay-dates.update", {
            resident: stayDatesResident.value.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                stayDatesOpen.value = false;
                stayDatesResident.value = null;
                stayDatesForm.reset();
            },
        },
    );
};
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
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-visible"
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
                                        class="h-16 w-16 rounded object-cover shrink-0"
                                    />
                                    <div
                                        v-else
                                        class="h-16 w-16 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs shrink-0"
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
                            <td
                                data-resident-actions
                                class="px-4 py-3 text-right relative"
                            >
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-gray-100"
                                    @click="toggleActions(r.id, $event)"
                                >
                                    <MoreVertical
                                        class="h-4 w-4 text-gray-500"
                                    />
                                </button>
                                <div
                                    v-if="openActionsFor === r.id"
                                    class="absolute right-2 top-15 z-50 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5 max-h-[400px] overflow-y-auto"
                                >
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <div
                                        class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide bg-gray-100 text-gray-600"
                                    >
                                        Profile
                                    </div>
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <button
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        @click="openView(r)"
                                    >
                                        <Eye class="h-3.5 w-3.5" /> View Details
                                    </button>
                                    <button
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        @click="openEdit(r)"
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
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <div
                                        class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide bg-gray-100 text-gray-600"
                                    >
                                        Accommodation
                                    </div>
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <button
                                        type="button"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                        @click="openStayDates(r)"
                                    >
                                        <Calendar class="h-3.5 w-3.5" />
                                        Edit Stay Dates
                                    </button>
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
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <div
                                        class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide bg-gray-100 text-gray-600"
                                    >
                                        Movement
                                    </div>
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <Link
                                        href="#"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                    >
                                        <ScanLine
                                            class="h-3.5 w-3.5"
                                        />Entry/Exit
                                    </Link>
                                    <Link
                                        href="#"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                    >
                                        <PlaneTakeoff
                                            class="h-3.5 w-3.5"
                                        />Leave History
                                    </Link>
                                    <Link
                                        href="#"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
                                    >
                                        <Footprints class="h-3.5 w-3.5" />Day
                                        Out
                                    </Link>
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <div
                                        class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide bg-gray-100 text-gray-600"
                                    >
                                        Records
                                    </div>
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <Link
                                        :href="
                                            route('billing.resident.history', {
                                                resident: r.id,
                                            })
                                        "
                                        class="px-3 py-2 text-xs rounded-lg flex items-center gap-1.5 text-gray-600 hover:bg-gray-50"
                                    >
                                        <ReceiptText class="h-4 w-4" /> Bill
                                        History
                                    </Link>
                                    <Link
                                        :href="
                                            route(
                                                'residents.amenity-override.edit',
                                                { resident: r.id },
                                            )
                                        "
                                        class="px-3 py-2 text-xs rounded-lg flex items-center gap-1.5 text-gray-600 hover:bg-gray-50"
                                    >
                                        <Users class="h-4 w-4" /> Resident
                                        Overrides
                                    </Link>
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
                                    <div
                                        class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide bg-gray-100 text-gray-600"
                                    >
                                        Documents
                                    </div>
                                    <div
                                        class="border-t border-gray-200 my-1"
                                    ></div>
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

        <!-- View / basic modal -->
        <Modal :show="viewOpen" @close="viewOpen = false">
            <div
                v-if="viewing"
                class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden bg-white"
            >
                <!-- Header -->
                <div
                    class="flex shrink-0 items-center gap-4 border-b border-gray-100 px-6 py-4"
                >
                    <img
                        v-if="viewing.photo_url"
                        :src="`/storage/${viewing.photo_url}`"
                        class="h-20 w-20 rounded-xl border border-gray-200 object-cover"
                    />

                    <div
                        v-else
                        class="flex h-20 w-20 items-center justify-center rounded-xl bg-gray-100 text-3xl font-semibold text-gray-500"
                    >
                        {{ viewing.first_name?.charAt(0) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-xl font-bold text-gray-900">
                            {{ viewing.first_name }}
                            {{ viewing.last_name }}
                        </h2>

                        <p class="mt-0.5 text-sm text-gray-500">
                            {{ viewing.resident_code }}
                        </p>

                        <span
                            class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                            :class="{
                                'bg-green-100 text-green-700':
                                    viewing.status === 'active',
                                'bg-amber-100 text-amber-700':
                                    viewing.status === 'upcoming',
                                'bg-gray-100 text-gray-700':
                                    viewing.status === 'inactive',
                                'bg-red-100 text-red-700':
                                    viewing.status === 'suspended' ||
                                    viewing.status === 'left',
                            }"
                        >
                            {{ viewing.status }}
                        </span>
                    </div>
                </div>

                <!-- Scrollable content -->
                <div class="flex-1 space-y-3 overflow-y-auto p-6">
                    <!-- Basic information -->
                    <AccordionItem
                        v-model:open="residentSections.basic"
                        title="Basic Information"
                    >
                        <template #icon>
                            <User class="h-4 w-4 text-blue-600" />
                        </template>

                        <div
                            class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div>
                                <p class="text-xs text-gray-400">First Name</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.first_name || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Last Name</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.last_name || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">
                                    Resident Code
                                </p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.resident_code || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Gender</p>
                                <p class="font-medium capitalize text-gray-900">
                                    {{ viewing.gender || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">
                                    Date of Birth
                                </p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.date_of_birth || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Blood Group</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.blood_group || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Status</p>
                                <p class="font-medium capitalize text-gray-900">
                                    {{ viewing.status || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Created At</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.created_at || "—" }}
                                </p>
                            </div>
                        </div>
                    </AccordionItem>

                    <!-- Contact information -->
                    <AccordionItem
                        v-model:open="residentSections.contact"
                        title="Contact Information"
                    >
                        <template #icon>
                            <Phone class="h-4 w-4 text-green-600" />
                        </template>

                        <div
                            class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2"
                        >
                            <div>
                                <p class="text-xs text-gray-400">Phone</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.phone || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">
                                    WhatsApp Number
                                </p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.whatsapp_number || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Email</p>
                                <p class="break-all font-medium text-gray-900">
                                    {{ viewing.email || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">City</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.city || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">State</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.state || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Country</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.country || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Pincode</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.pincode || "—" }}
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="text-xs text-gray-400">Address</p>
                                <p
                                    class="whitespace-pre-line font-medium text-gray-900"
                                >
                                    {{ viewing.address || "—" }}
                                </p>
                            </div>
                        </div>
                    </AccordionItem>

                    <!-- Academic information -->
                    <AccordionItem
                        v-model:open="residentSections.academic"
                        title="Academic Information"
                    >
                        <template #icon>
                            <GraduationCap class="h-4 w-4 text-purple-600" />
                        </template>

                        <div
                            class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div>
                                <p class="text-xs text-gray-400">Institute</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.institute || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Course</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.course || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">
                                    Academic Year
                                </p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.year || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Batch</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.batch || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Roll Number</p>
                                <p class="font-medium text-gray-900">
                                    {{ viewing.roll_number || "—" }}
                                </p>
                            </div>
                        </div>
                    </AccordionItem>

                    <!-- Guardian information -->
                    <AccordionItem
                        v-model:open="residentSections.guardian"
                        title="Guardian Information"
                    >
                        <template #icon>
                            <Users class="h-4 w-4 text-amber-600" />
                        </template>

                        <div class="space-y-5">
                            <div>
                                <h4
                                    class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Father Details
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3"
                                >
                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Name
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ viewing.father_name || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Phone
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ viewing.father_phone || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Email
                                        </p>
                                        <p
                                            class="break-all font-medium text-gray-900"
                                        >
                                            {{ viewing.father_email || "—" }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <h4
                                    class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Mother Details
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2"
                                >
                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Name
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ viewing.mother_name || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Phone
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ viewing.mother_phone || "—" }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </AccordionItem>

                    <!-- Hostel information -->
                    <AccordionItem
                        v-model:open="residentSections.hostel"
                        title="Hostel Information"
                    >
                        <template #icon>
                            <Building2 class="h-4 w-4 text-cyan-600" />
                        </template>

                        <div v-if="viewing.current_stay">
                            <div
                                class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <div>
                                    <p class="text-xs text-gray-400">
                                        Building
                                    </p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            viewing.current_stay.building
                                                ?.name || "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">Floor</p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            viewing.current_stay.floor?.name ||
                                            viewing.current_stay.floor
                                                ?.floor_number ||
                                            "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">Room</p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            viewing.current_stay.room
                                                ?.room_number || "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">Bed</p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            viewing.current_stay.bed
                                                ?.bed_number || "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Check-in Date
                                    </p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            viewing.current_stay
                                                .check_in_date || "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Expected Check-out
                                    </p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            viewing.current_stay
                                                .expected_check_out_date || "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Actual Check-out
                                    </p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            viewing.current_stay
                                                .actual_check_out_date || "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Rent Amount
                                    </p>
                                    <p class="font-medium text-gray-900">
                                        ₹{{
                                            Number(
                                                viewing.current_stay
                                                    .rent_amount || 0,
                                            ).toLocaleString("en-IN")
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Deposit Amount
                                    </p>
                                    <p class="font-medium text-gray-900">
                                        ₹{{
                                            Number(
                                                viewing.current_stay
                                                    .deposit_amount || 0,
                                            ).toLocaleString("en-IN")
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Bill Type
                                    </p>
                                    <p
                                        class="font-medium capitalize text-gray-900"
                                    >
                                        {{
                                            viewing.current_stay.bill_type?.replace(
                                                "_",
                                                " ",
                                            ) || "—"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Stay Status
                                    </p>
                                    <p
                                        class="font-medium capitalize text-gray-900"
                                    >
                                        {{ viewing.current_stay.status || "—" }}
                                    </p>
                                </div>

                                <div class="sm:col-span-2 lg:col-span-3">
                                    <p class="text-xs text-gray-400">Notes</p>
                                    <p
                                        class="whitespace-pre-line font-medium text-gray-900"
                                    >
                                        {{ viewing.current_stay.notes || "—" }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700"
                        >
                            Resident has not been allotted a room. Use the
                            Check-In / Check-Out action to assign a room and
                            bed.
                        </div>
                    </AccordionItem>
                </div>

                <!-- Footer -->
                <div
                    class="flex shrink-0 justify-end border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        @click="viewOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="editOpen" @close="editOpen = false">
            <form
                v-if="editForm.id"
                @submit.prevent="updateResident"
                class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden bg-white"
            >
                <!-- Header -->
                <div
                    class="flex shrink-0 items-center gap-4 border-b border-gray-100 px-6 py-4"
                >
                    <div class="relative shrink-0">
                        <img
                            v-if="photoPreview2"
                            :src="photoPreview2"
                            class="h-20 w-20 rounded-xl border border-gray-200 object-cover"
                            alt="Resident photo"
                        />

                        <div
                            v-else
                            class="flex h-20 w-20 items-center justify-center rounded-xl bg-gray-100 text-3xl font-semibold text-gray-500"
                        >
                            {{ editForm.first_name?.charAt(0) || "R" }}
                        </div>

                        <label
                            for="resident-edit-photo"
                            class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-blue-600 text-white shadow-sm transition hover:bg-blue-700"
                            title="Change photo"
                        >
                            <Upload class="h-4 w-4" />
                        </label>

                        <input
                            id="resident-edit-photo"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="selectPhoto"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-xl font-bold text-gray-900">
                            Edit Resident
                        </h2>

                        <p class="mt-0.5 truncate text-sm text-gray-500">
                            {{ editForm.first_name }}
                            {{ editForm.last_name }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            Update personal, contact, academic, guardian, and
                            hostel details.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        @click="editOpen = false"
                    >
                        Close
                    </button>
                </div>

                <!-- Global validation errors -->
                <div
                    v-if="Object.keys(editForm.errors).length"
                    class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 p-3"
                >
                    <p class="text-sm font-semibold text-red-700">
                        Please correct the highlighted fields.
                    </p>

                    <ul class="mt-1 list-inside list-disc text-xs text-red-600">
                        <li
                            v-for="(error, field) in editForm.errors"
                            :key="field"
                        >
                            {{ error }}
                        </li>
                    </ul>
                </div>

                <!-- Tabs -->
                <div
                    class="shrink-0 overflow-x-auto border-b border-gray-100 px-6 pt-4"
                >
                    <div class="flex min-w-max gap-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-t-lg border-b-2 px-4 py-3 text-sm font-medium transition-colors"
                            :class="
                                activeTab === tab.key
                                    ? 'border-blue-600 bg-blue-50 text-blue-700'
                                    : 'border-transparent text-gray-500 hover:bg-gray-50 hover:text-gray-800'
                            "
                            @click="activeTab = tab.key"
                        >
                            <component :is="tab.icon" class="h-4 w-4" />
                            {{ tab.title }}
                        </button>
                    </div>
                </div>

                <!-- Scrollable body -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Basic tab -->
                    <div v-show="activeTab === 'basic'" class="space-y-5">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Basic Information
                            </h3>
                            <p class="text-xs text-gray-500">
                                Main identity and status details of the
                                resident.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel
                                    for="edit_first_name"
                                    value="First Name *"
                                />
                                <TextInput
                                    id="edit_first_name"
                                    v-model="editForm.first_name"
                                    type="text"
                                    required
                                    class="w-full"
                                    autocomplete="given-name"
                                />
                                <InputError
                                    :message="editForm.errors.first_name"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_last_name"
                                    value="Last Name"
                                />
                                <TextInput
                                    id="edit_last_name"
                                    v-model="editForm.last_name"
                                    type="text"
                                    class="w-full"
                                    autocomplete="family-name"
                                />
                                <InputError
                                    :message="editForm.errors.last_name"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_gender"
                                    value="Gender *"
                                />
                                <select
                                    id="edit_gender"
                                    v-model="editForm.gender"
                                    required
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>
                                        Select gender
                                    </option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                <InputError :message="editForm.errors.gender" />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_date_of_birth"
                                    value="Date of Birth"
                                />
                                <TextInput
                                    id="edit_date_of_birth"
                                    v-model="editForm.date_of_birth"
                                    type="date"
                                    class="w-full"
                                />
                                <InputError
                                    :message="editForm.errors.date_of_birth"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_blood_group"
                                    value="Blood Group"
                                />
                                <select
                                    id="edit_blood_group"
                                    v-model="editForm.blood_group"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select blood group</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                                <InputError
                                    :message="editForm.errors.blood_group"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_status"
                                    value="Resident Status *"
                                />
                                <select
                                    id="edit_status"
                                    v-model="editForm.status"
                                    required
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>
                                        Select status
                                    </option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="left">Left</option>
                                </select>
                                <InputError :message="editForm.errors.status" />
                            </div>
                        </div>

                        <div
                            class="rounded-lg border border-blue-100 bg-blue-50 p-4"
                        >
                            <p class="text-sm font-medium text-blue-800">
                                Resident photo
                            </p>
                            <p class="mt-1 text-xs text-blue-600">
                                Click the upload icon near the photo to replace
                                it. Maximum recommended size: 5 MB.
                            </p>
                            <InputError :message="editForm.errors.photo" />
                        </div>
                    </div>

                    <!-- Contact tab -->
                    <div v-show="activeTab === 'contact'" class="space-y-5">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Contact Information
                            </h3>
                            <p class="text-xs text-gray-500">
                                Phone, email, WhatsApp, and permanent address.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel for="edit_phone" value="Phone *" />
                                <TextInput
                                    id="edit_phone"
                                    v-model="editForm.phone"
                                    type="text"
                                    required
                                    class="w-full"
                                    autocomplete="tel"
                                />
                                <InputError :message="editForm.errors.phone" />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_whatsapp_number"
                                    value="WhatsApp Number"
                                />
                                <TextInput
                                    id="edit_whatsapp_number"
                                    v-model="editForm.whatsapp_number"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError
                                    :message="editForm.errors.whatsapp_number"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="edit_email" value="Email" />
                                <TextInput
                                    id="edit_email"
                                    v-model="editForm.email"
                                    type="email"
                                    class="w-full"
                                    autocomplete="email"
                                />
                                <InputError :message="editForm.errors.email" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel
                                    for="edit_address"
                                    value="Address"
                                />
                                <textarea
                                    id="edit_address"
                                    v-model="editForm.address"
                                    rows="3"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Permanent address"
                                ></textarea>
                                <InputError
                                    :message="editForm.errors.address"
                                />
                            </div>

                            <div>
                                <InputLabel for="edit_city" value="City" />
                                <TextInput
                                    id="edit_city"
                                    v-model="editForm.city"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError :message="editForm.errors.city" />
                            </div>

                            <div>
                                <InputLabel for="edit_state" value="State" />
                                <TextInput
                                    id="edit_state"
                                    v-model="editForm.state"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError :message="editForm.errors.state" />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_country"
                                    value="Country"
                                />
                                <TextInput
                                    id="edit_country"
                                    v-model="editForm.country"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError
                                    :message="editForm.errors.country"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_pincode"
                                    value="Pincode"
                                />
                                <TextInput
                                    id="edit_pincode"
                                    v-model="editForm.pincode"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError
                                    :message="editForm.errors.pincode"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Academic tab -->
                    <div v-show="activeTab === 'academic'" class="space-y-5">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Academic Information
                            </h3>
                            <p class="text-xs text-gray-500">
                                Institute, course, year, batch, and roll number.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <InputLabel
                                    for="edit_institute"
                                    value="Institute"
                                />
                                <TextInput
                                    id="edit_institute"
                                    v-model="editForm.institute"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError
                                    :message="editForm.errors.institute"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="edit_course" value="Course" />
                                <TextInput
                                    id="edit_course"
                                    v-model="editForm.course"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError :message="editForm.errors.course" />
                            </div>

                            <div>
                                <InputLabel
                                    for="edit_year"
                                    value="Academic Year"
                                />
                                <TextInput
                                    id="edit_year"
                                    v-model="editForm.year"
                                    type="number"
                                    min="1"
                                    max="20"
                                    class="w-full"
                                    placeholder="e.g. 1, 2, 3"
                                />
                                <InputError :message="editForm.errors.year" />
                            </div>

                            <div>
                                <InputLabel for="edit_batch" value="Batch" />
                                <TextInput
                                    id="edit_batch"
                                    v-model="editForm.batch"
                                    type="text"
                                    class="w-full"
                                    placeholder="e.g. 2026-2029"
                                />
                                <InputError :message="editForm.errors.batch" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel
                                    for="edit_roll_number"
                                    value="Roll Number"
                                />
                                <TextInput
                                    id="edit_roll_number"
                                    v-model="editForm.roll_number"
                                    type="text"
                                    class="w-full"
                                />
                                <InputError
                                    :message="editForm.errors.roll_number"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Guardian tab -->
                    <div v-show="activeTab === 'guardian'" class="space-y-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Guardian Information
                            </h3>
                            <p class="text-xs text-gray-500">
                                Parent and guardian contact details.
                            </p>
                        </div>

                        <!-- Father Details -->
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="mb-4 flex items-center gap-2">
                                <Users class="h-4 w-4 text-blue-600" />

                                <div>
                                    <h4
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Father Details
                                    </h4>
                                    <p class="text-xs text-gray-400">
                                        Primary guardian information
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <InputLabel
                                        for="edit_father_name"
                                        value="Father Name"
                                    />

                                    <TextInput
                                        id="edit_father_name"
                                        v-model="editForm.father_name"
                                        type="text"
                                        class="w-full"
                                    />

                                    <InputError
                                        :message="editForm.errors.father_name"
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        for="edit_father_phone"
                                        value="Father Phone"
                                    />

                                    <TextInput
                                        id="edit_father_phone"
                                        v-model="editForm.father_phone"
                                        type="text"
                                        class="w-full"
                                    />

                                    <InputError
                                        :message="editForm.errors.father_phone"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel
                                        for="edit_father_email"
                                        value="Father Email"
                                    />

                                    <TextInput
                                        id="edit_father_email"
                                        v-model="editForm.father_email"
                                        type="email"
                                        class="w-full"
                                    />

                                    <InputError
                                        :message="editForm.errors.father_email"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Mother Details -->
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="mb-4 flex items-center gap-2">
                                <Users class="h-4 w-4 text-pink-600" />

                                <div>
                                    <h4
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Mother Details
                                    </h4>
                                    <p class="text-xs text-gray-400">
                                        Secondary guardian information
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <InputLabel
                                        for="edit_mother_name"
                                        value="Mother Name"
                                    />

                                    <TextInput
                                        id="edit_mother_name"
                                        v-model="editForm.mother_name"
                                        type="text"
                                        class="w-full"
                                    />

                                    <InputError
                                        :message="editForm.errors.mother_name"
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        for="edit_mother_phone"
                                        value="Mother Phone"
                                    />

                                    <TextInput
                                        id="edit_mother_phone"
                                        v-model="editForm.mother_phone"
                                        type="text"
                                        class="w-full"
                                    />

                                    <InputError
                                        :message="editForm.errors.mother_phone"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hostel tab -->
                    <div v-show="activeTab === 'hostel'" class="space-y-5">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Hostel Information
                            </h3>

                            <p class="text-xs text-gray-500">
                                Current room allotment and stay information.
                            </p>
                        </div>

                        <div v-if="viewing?.current_stay" class="space-y-5">
                            <div
                                class="rounded-xl border border-blue-100 bg-blue-50 p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <Building2
                                        class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                                    />

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-blue-900"
                                        >
                                            Room allotment is read-only here
                                        </p>

                                        <p class="mt-1 text-xs text-blue-700">
                                            Use the dedicated Check-In /
                                            Check-Out or Room Allotment action
                                            to change building, floor, room, or
                                            bed. This prevents stay and billing
                                            history from becoming inconsistent.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Current allotment -->
                            <div class="rounded-xl border border-gray-200 p-4">
                                <h4
                                    class="mb-4 text-sm font-semibold text-gray-900"
                                >
                                    Current Allotment
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2 lg:grid-cols-4"
                                >
                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Building
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            {{
                                                viewing.current_stay.building
                                                    ?.name || "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Floor
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            {{
                                                viewing.current_stay.floor
                                                    ?.name ||
                                                viewing.current_stay.floor
                                                    ?.floor_number ||
                                                "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Room
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            {{
                                                viewing.current_stay.room
                                                    ?.room_number || "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">Bed</p>

                                        <p class="font-medium text-gray-900">
                                            {{
                                                viewing.current_stay.bed
                                                    ?.bed_number || "—"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Stay details -->
                            <div class="rounded-xl border border-gray-200 p-4">
                                <h4
                                    class="mb-4 text-sm font-semibold text-gray-900"
                                >
                                    Stay Details
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2 lg:grid-cols-3"
                                >
                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Check-in Date
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            {{
                                                viewing.current_stay
                                                    .check_in_date || "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Expected Check-out
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            {{
                                                viewing.current_stay
                                                    .expected_check_out_date ||
                                                "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Actual Check-out
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            {{
                                                viewing.current_stay
                                                    .actual_check_out_date ||
                                                "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Monthly Rent
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            ₹{{
                                                Number(
                                                    viewing.current_stay
                                                        .rent_amount || 0,
                                                ).toLocaleString("en-IN", {
                                                    minimumFractionDigits: 2,
                                                })
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Deposit Amount
                                        </p>

                                        <p class="font-medium text-gray-900">
                                            ₹{{
                                                Number(
                                                    viewing.current_stay
                                                        .deposit_amount || 0,
                                                ).toLocaleString("en-IN", {
                                                    minimumFractionDigits: 2,
                                                })
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Bill Type
                                        </p>

                                        <p
                                            class="font-medium capitalize text-gray-900"
                                        >
                                            {{
                                                viewing.current_stay.bill_type?.replaceAll(
                                                    "_",
                                                    " ",
                                                ) || "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Stay Status
                                        </p>

                                        <p
                                            class="font-medium capitalize text-gray-900"
                                        >
                                            {{
                                                viewing.current_stay.status ||
                                                "—"
                                            }}
                                        </p>
                                    </div>

                                    <div class="md:col-span-2 lg:col-span-3">
                                        <p class="text-xs text-gray-400">
                                            Stay Notes
                                        </p>

                                        <p
                                            class="whitespace-pre-line font-medium text-gray-900"
                                        >
                                            {{
                                                viewing.current_stay.notes ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-amber-200 bg-amber-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <AlertTriangle
                                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                                />

                                <div>
                                    <p
                                        class="text-sm font-semibold text-amber-900"
                                    >
                                        No active stay found
                                    </p>

                                    <p class="mt-1 text-xs text-amber-700">
                                        This resident has not been assigned a
                                        room and bed. Use the Check-In /
                                        Check-Out action to create a stay.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div
                    class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <p class="text-xs text-gray-400">
                        Fields marked with * are required.
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                            :disabled="editForm.processing"
                            @click="editOpen = false"
                        >
                            Cancel
                        </button>

                        <PrimaryButton
                            type="submit"
                            :disabled="editForm.processing"
                        >
                            {{
                                editForm.processing
                                    ? "Saving..."
                                    : "Update Resident"
                            }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>

        <!-- Add Resident modal -->
        <Modal :show="createOpen" @close="createOpen = false" maxWidth="2xl">
            <form
                @submit.prevent="submitCreate"
                class="flex w-full flex-col overflow-hidden bg-white"
            >
                <!-- Fixed header -->
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Add New Resident
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Enter resident details and optionally assign a room.
                    </p>
                </div>

                <!-- Scrollable content -->
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="space-y-5">
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
                                                class="w-16 h-16 mb-3 text-gray-400 group-hover:text-gray-500 transition-colors"
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
                                            <p
                                                class="text-xs text-gray-400 mt-1"
                                            >
                                                PNG, JPG, GIF up to 2MB
                                            </p>
                                        </div>

                                        <!-- Image Preview -->
                                        <div
                                            v-else
                                            class="relative w-full h-full p-2"
                                        >
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
                                    A clear passport-size photo is required for
                                    ID and gate records.
                                </p>
                            </div>
                        </div>

                        <p
                            class="text-xs font-semibold text-gray-400 uppercase pt-1"
                        >
                            Personal Details
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="First Name *" /><TextInput
                                    v-model="createForm.first_name"
                                    required
                                /><InputError
                                    :message="createForm.errors.first_name"
                                />
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
                                /><InputError
                                    :message="createForm.errors.phone"
                                />
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

                        <p
                            class="text-xs font-semibold text-gray-400 uppercase pt-1"
                        >
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

                        <p
                            class="text-xs font-semibold text-gray-400 uppercase pt-1"
                        >
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

                        <p
                            class="text-xs font-semibold text-gray-400 uppercase pt-1"
                        >
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
                        <div
                            class="grid grid-cols-2 gap-4"
                            v-if="createForm.floor_id"
                        >
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
                                        :disabled="
                                            r.occupied_beds >= r.capacity
                                        "
                                    >
                                        {{ r.room_number }} ({{
                                            r.occupied_beds
                                        }}/{{ r.capacity }}
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
                        <div
                            v-if="createForm.bed_id"
                            class="space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
                        >
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Stay & Billing Details
                                </h3>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Choose monthly billing for regular residents
                                    or daily billing for short-term residents.
                                </p>
                            </div>

                            <div>
                                <InputLabel value="Billing Basis *" />

                                <div
                                    class="mt-2 grid grid-cols-1 gap-3 lg:grid-cols-2"
                                >
                                    <label
                                        class="flex min-h-[92px] cursor-pointer flex-col justify-center rounded-xl border p-4 transition"
                                        :class="
                                            createForm.billing_basis ===
                                            'monthly'
                                                ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500'
                                                : 'border-gray-200 bg-white hover:border-gray-300'
                                        "
                                    >
                                        <input
                                            v-model="createForm.billing_basis"
                                            type="radio"
                                            value="monthly"
                                            class="sr-only"
                                        />

                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            Monthly Billing
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Regular resident charged every
                                            month.
                                        </p>
                                    </label>

                                    <label
                                        class="flex min-h-[92px] cursor-pointer flex-col justify-center rounded-xl border p-4 transition"
                                        :class="
                                            createForm.billing_basis === 'daily'
                                                ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500'
                                                : 'border-gray-200 bg-white hover:border-gray-300'
                                        "
                                    >
                                        <input
                                            v-model="createForm.billing_basis"
                                            type="radio"
                                            value="daily"
                                            class="sr-only"
                                        />

                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            Daily Short Stay
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Resident charged according to
                                            occupied days.
                                        </p>
                                    </label>
                                </div>

                                <InputError
                                    :message="createForm.errors.billing_basis"
                                />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel
                                        for="create_check_in_date"
                                        value="Check-in Date *"
                                    />

                                    <TextInput
                                        id="create_check_in_date"
                                        v-model="createForm.check_in_date"
                                        type="date"
                                        required
                                        class="w-full"
                                    />

                                    <InputError
                                        :message="
                                            createForm.errors.check_in_date
                                        "
                                    />
                                </div>

                                <div
                                    v-if="
                                        createForm.billing_basis === 'monthly'
                                    "
                                >
                                    <InputLabel
                                        for="create_rent_amount"
                                        value="Monthly Rent (₹) *"
                                    />

                                    <TextInput
                                        id="create_rent_amount"
                                        v-model="createForm.rent_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="w-full"
                                        placeholder="Enter monthly rent"
                                    />

                                    <InputError
                                        :message="createForm.errors.rent_amount"
                                    />
                                </div>

                                <div v-else>
                                    <InputLabel
                                        for="create_daily_rate"
                                        value="Daily Rate (₹) *"
                                    />

                                    <TextInput
                                        id="create_daily_rate"
                                        v-model="createForm.daily_rate"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="w-full"
                                        placeholder="350"
                                    />

                                    <InputError
                                        :message="createForm.errors.daily_rate"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="
                                    createForm.bed_id &&
                                    createForm.billing_basis === 'daily'
                                "
                                class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-2"
                            >
                                <div class="min-w-0">
                                    <InputLabel
                                        for="create_expected_checkout"
                                        value="Expected Check-out Date *"
                                    />

                                    <TextInput
                                        id="create_expected_checkout"
                                        v-model="
                                            createForm.expected_check_out_date
                                        "
                                        type="date"
                                        :min="createForm.check_in_date"
                                        required
                                        class="w-full"
                                    />

                                    <InputError
                                        :message="
                                            createForm.errors
                                                .expected_check_out_date
                                        "
                                    />
                                </div>

                                <div
                                    class="flex min-h-[82px] min-w-0 flex-col justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                                >
                                    <p
                                        class="text-xs font-medium text-emerald-700"
                                    >
                                        Estimated stay amount
                                    </p>

                                    <p
                                        class="mt-1 break-words text-xl font-bold text-emerald-900"
                                    >
                                        ₹{{
                                            Number(
                                                estimatedDailyAmount || 0,
                                            ).toLocaleString("en-IN", {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })
                                        }}
                                    </p>

                                    <p
                                        class="mt-1 break-words text-xs text-emerald-700"
                                    >
                                        {{ estimatedStayDays }} day(s) × ₹{{
                                            Number(
                                                createForm.daily_rate || 0,
                                            ).toLocaleString("en-IN", {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    for="create_deposit_amount"
                                    value="Refundable Security Deposit (₹)"
                                />

                                <TextInput
                                    id="create_deposit_amount"
                                    v-model="createForm.deposit_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full"
                                />

                                <p class="mt-1 text-xs text-gray-400">
                                    One-time refundable deposit for this stay.
                                    It will not be included in monthly billing.
                                </p>

                                <InputError
                                    :message="createForm.errors.deposit_amount"
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
                    </div>
                </div>

                <!-- Fixed footer -->
                <div
                    class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <p class="hidden text-xs text-gray-400 sm:block">
                        Fields marked with * are required.
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                            :disabled="createForm.processing"
                            @click="createOpen = false"
                        >
                            Cancel
                        </button>

                        <PrimaryButton
                            type="submit"
                            :disabled="createForm.processing"
                        >
                            {{
                                createForm.processing
                                    ? "Saving..."
                                    : "Add Resident"
                            }}
                        </PrimaryButton>
                    </div>
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

        <Modal :show="stayDatesOpen" @close="closeStayDates" maxWidth="lg">
            <form
                v-if="stayDatesResident && stayDatesResident.current_stay"
                @submit.prevent="submitStayDates"
                class="flex max-h-[90vh] flex-col overflow-hidden"
            >
                <!-- Header -->
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                        >
                            <Calendar class="h-5 w-5" />
                        </div>

                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Edit Stay Dates
                            </h2>

                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ stayDatesResident.first_name }}
                                {{ stayDatesResident.last_name }}
                                ·
                                {{
                                    stayDatesResident.current_stay.room
                                        ?.room_number
                                        ? `Room ${stayDatesResident.current_stay.room.room_number}`
                                        : "Room not available"
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        class="rounded-xl border border-blue-100 bg-blue-50 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <Clock3
                                class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                            />

                            <div>
                                <p class="text-sm font-semibold text-blue-900">
                                    {{
                                        stayDatesResident.current_stay
                                            .check_in_status
                                            ? "Resident is already checked in"
                                            : "Physical check-in is pending"
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-blue-700">
                                    <template
                                        v-if="
                                            stayDatesResident.current_stay
                                                .billing_basis === 'daily'
                                        "
                                    >
                                        Changing these dates will update the
                                        short-stay amount
                                        <span
                                            v-if="
                                                stayDatesResident.current_stay
                                                    .check_in_status
                                            "
                                        >
                                            and recalculate the existing
                                            invoice.
                                        </span>

                                        <span v-else>
                                            when actual check-in is completed.
                                        </span>
                                    </template>

                                    <template v-else>
                                        Future monthly charges continue using
                                        the current room and rent settings.
                                    </template>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Current details -->
                    <div
                        class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 sm:grid-cols-2"
                    >
                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-400"
                            >
                                Current check-in
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900">
                                {{
                                    stayDatesResident.current_stay
                                        .check_in_date || "—"
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-400"
                            >
                                Current expected checkout
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900">
                                {{
                                    stayDatesResident.current_stay
                                        .expected_check_out_date || "Not set"
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-400"
                            >
                                Billing basis
                            </p>

                            <p
                                class="mt-1 text-sm font-semibold capitalize text-gray-900"
                            >
                                {{
                                    stayDatesResident.current_stay
                                        .billing_basis || "monthly"
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-400"
                            >
                                Stay status
                            </p>

                            <p
                                class="mt-1 text-sm font-semibold capitalize text-gray-900"
                            >
                                {{
                                    stayDatesResident.current_stay
                                        .check_in_status
                                        ? "Checked in"
                                        : "Check-in pending"
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Editable fields -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                for="edit_stay_check_in_date"
                                :value="
                                    stayDatesResident.current_stay
                                        .check_in_status
                                        ? 'Actual Check-in Date *'
                                        : 'Planned Check-in Date *'
                                "
                            />

                            <TextInput
                                id="edit_stay_check_in_date"
                                v-model="stayDatesForm.check_in_date"
                                type="date"
                                required
                                class="w-full"
                            />

                            <InputError
                                :message="stayDatesForm.errors.check_in_date"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="edit_stay_checkout_date"
                                :value="
                                    stayDatesResident.current_stay
                                        .billing_basis === 'daily'
                                        ? 'Expected Check-out Date *'
                                        : 'Expected Check-out Date'
                                "
                            />

                            <TextInput
                                id="edit_stay_checkout_date"
                                v-model="stayDatesForm.expected_check_out_date"
                                type="date"
                                :min="stayDatesForm.check_in_date"
                                :required="
                                    stayDatesResident.current_stay
                                        .billing_basis === 'daily'
                                "
                                class="w-full"
                            />

                            <InputError
                                :message="
                                    stayDatesForm.errors.expected_check_out_date
                                "
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            for="stay_date_change_reason"
                            value="Reason for Date Change"
                        />

                        <textarea
                            id="stay_date_change_reason"
                            v-model="stayDatesForm.reason"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="For example: arrival delayed, course extended, early departure requested"
                        ></textarea>

                        <InputError :message="stayDatesForm.errors.reason" />
                    </div>

                    <div
                        v-if="
                            stayDatesResident.current_stay.billing_basis ===
                            'daily'
                        "
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <p class="text-sm font-semibold text-amber-900">
                            Daily billing impact
                        </p>

                        <p class="mt-1 text-xs text-amber-700">
                            Extending the checkout date increases the
                            accommodation charge. Making it earlier reduces the
                            amount. If the recalculated amount becomes lower
                            than money already received, the update will be
                            stopped until the excess payment is adjusted or
                            refunded.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex shrink-0 justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        :disabled="stayDatesForm.processing"
                        @click="closeStayDates"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        type="submit"
                        :disabled="stayDatesForm.processing"
                    >
                        {{
                            stayDatesForm.processing
                                ? "Updating..."
                                : "Update Stay Dates"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
