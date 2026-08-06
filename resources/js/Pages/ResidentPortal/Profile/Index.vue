<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import {
    AlertCircle,
    BadgeCheck,
    BedDouble,
    BookOpen,
    Building2,
    CalendarDays,
    Camera,
    CheckCircle2,
    Clock3,
    Contact,
    CreditCard,
    Eye,
    EyeOff,
    FileText,
    GraduationCap,
    HeartPulse,
    Home,
    KeyRound,
    LockKeyhole,
    Mail,
    MapPin,
    Phone,
    Save,
    ShieldCheck,
    Smartphone,
    UserRound,
    Users,
    X,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";

const props = defineProps({
    resident: {
        type: Object,
        required: true,
    },

    currentStay: {
        type: Object,
        default: null,
    },

    registrationApplication: {
        type: Object,
        default: null,
    },

    vehicles: {
        type: Array,
        default: () => [],
    },

    editableFields: {
        type: Array,
        default: () => [],
    },

    readOnlyFields: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref("personal");

const photoOpen = ref(false);
const passwordOpen = ref(false);
const photoPreview = ref(null);

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const tabs = [
    {
        key: "personal",
        label: "Personal",
        icon: UserRound,
    },
    {
        key: "contact",
        label: "Contact",
        icon: Contact,
    },
    {
        key: "academic",
        label: "Academic",
        icon: GraduationCap,
    },
    {
        key: "family",
        label: "Parents",
        icon: Users,
    },
    {
        key: "hostel",
        label: "Hostel",
        icon: Building2,
    },
    {
        key: "registration",
        label: "Registration",
        icon: FileText,
    },
    {
        key: "security",
        label: "Security",
        icon: ShieldCheck,
    },
];

const profileForm = useForm({
    email: props.resident.email || "",
    whatsapp_number: props.resident.whatsapp_number || "",

    address: props.resident.address || "",
    city: props.resident.city || "",
    state: props.resident.state || "",
    country: props.resident.country || "India",
    pincode: props.resident.pincode || "",

    institute: props.resident.institute || "",
    course: props.resident.course || "",
    year: props.resident.year || "",
    batch: props.resident.batch || "",
    roll_number: props.resident.roll_number || "",

    father_name: props.resident.father_name || "",
    father_phone: props.resident.father_phone || "",
    father_email: props.resident.father_email || "",

    mother_name: props.resident.mother_name || "",
    mother_phone: props.resident.mother_phone || "",

});

const photoForm = useForm({
    photo: null,
});

const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const profileCompletion = computed(() => {
    const values = [
        props.resident.first_name,
        props.resident.last_name,
        props.resident.email,
        props.resident.phone,
        props.resident.whatsapp_number,
        props.resident.date_of_birth,
        props.resident.gender,
        props.resident.blood_group,
        props.resident.address,
        props.resident.city,
        props.resident.state,
        props.resident.country,
        props.resident.pincode,
        props.resident.institute,
        props.resident.course,
        props.resident.year,
        props.resident.batch,
        props.resident.roll_number,
        props.resident.father_name,
        props.resident.father_phone,
        props.resident.mother_name,
        props.resident.mother_phone,
        props.resident.photo_url,
    ];

    const completed = values.filter(
        (value) =>
            value !== null &&
            value !== undefined &&
            String(value).trim() !== "",
    ).length;

    return Math.round((completed / values.length) * 100);
});

const profileCompletionStyle = computed(() => ({
    width: `${profileCompletion.value}%`,
}));

const statusClasses = computed(() => {
    return (
        {
            active: "border-emerald-200 bg-emerald-50 text-emerald-700",

            upcoming: "border-blue-200 bg-blue-50 text-blue-700",

            inactive: "border-slate-200 bg-slate-50 text-slate-700",

            suspended: "border-red-200 bg-red-50 text-red-700",

            left: "border-amber-200 bg-amber-50 text-amber-700",
        }[props.resident.status] ||
        "border-slate-200 bg-slate-50 text-slate-700"
    );
});

const stayStatusClasses = computed(() => {
    return (
        {
            active: "border-emerald-200 bg-emerald-50 text-emerald-700",

            upcoming: "border-blue-200 bg-blue-50 text-blue-700",

            ended: "border-slate-200 bg-slate-50 text-slate-700",

            transferred: "border-purple-200 bg-purple-50 text-purple-700",
        }[props.currentStay?.status] ||
        "border-slate-200 bg-slate-50 text-slate-700"
    );
});

const fullAddress = computed(() => {
    return [
        props.resident.address,
        props.resident.city,
        props.resident.state,
        props.resident.pincode,
        props.resident.country,
    ]
        .filter(Boolean)
        .join(", ");
});

const currentTab = computed(() =>
    tabs.find((tab) => tab.key === activeTab.value),
);

const profileInitials = computed(() => {
    const first = props.resident.first_name?.charAt(0) || "";

    const last = props.resident.last_name?.charAt(0) || "";

    return `${first}${last}`.toUpperCase();
});

const submitProfile = () => {
    profileForm.put(route("resident.profile.update"), {
        preserveScroll: true,
    });
};

const openPhotoModal = () => {
    photoForm.reset();
    photoForm.clearErrors();

    photoPreview.value = props.resident.photo_url || null;

    photoOpen.value = true;
};

const onPhotoChange = (event) => {
    const file = event.target.files?.[0] || null;

    photoForm.photo = file;

    if (file) {
        photoPreview.value = URL.createObjectURL(file);
    } else {
        photoPreview.value = props.resident.photo_url || null;
    }

    photoForm.clearErrors("photo");
};

const submitPhoto = () => {
    photoForm.post(route("resident.profile.photo.update"), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            photoOpen.value = false;
            photoForm.reset();
        },
    });
};

const openPasswordModal = () => {
    passwordForm.reset();
    passwordForm.clearErrors();

    showCurrentPassword.value = false;
    showPassword.value = false;
    showPasswordConfirmation.value = false;

    passwordOpen.value = true;
};

const submitPassword = () => {
    passwordForm.put(route("resident.profile.password.update"), {
        preserveScroll: true,

        onSuccess: () => {
            passwordOpen.value = false;
            passwordForm.reset();
        },
    });
};

const formatDate = (value) => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
};

const formatDateTime = (value) => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2,
    }).format(Number(value));
};

const humanize = (value) => {
    if (!value) {
        return "—";
    }

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
};

const maskedAadhaar = computed(() => {
    const value = String(props.resident.aadhar_number || "");

    if (value.length !== 12) {
        return value || "—";
    }

    return `XXXX XXXX ${value.slice(-4)}`;
});

const stayForm = useForm({
    expected_check_out_date:
        props.currentStay?.expected_check_out_date ?? "",
});

watch(
    () => props.currentStay.value?.expected_check_out_date,
    (value) => {
        stayForm.expected_check_out_date = value ?? "";
    },
    { immediate: true },
);

const updateExpectedCheckout = () => {
    if (!currentStay.value?.id) {
        return;
    }

    stayForm.patch(
        route(
            "resident.hostel.stay.expected-checkout.update",
            currentStay.value.id,
        ),
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="My Profile" />

    <ResidentLayout title="My Profile">
        <div class="space-y-6">
            <!-- Profile hero -->
            <section
                class="overflow-hidden rounded-3xl border border-indigo-200 bg-[linear-gradient(135deg,#1e1b4b_0%,#4338ca_52%,#6366f1_100%)] text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-center"
                    >
                        <div class="relative shrink-0">
                            <img
                                v-if="resident.photo_url"
                                :src="resident.photo_url"
                                :alt="resident.full_name"
                                class="h-28 w-28 rounded-3xl border-4 border-white/30 object-cover shadow-xl"
                            />

                            <div
                                v-else
                                class="flex h-28 w-28 items-center justify-center rounded-3xl border-4 border-white/30 bg-white/15 text-3xl font-black text-white shadow-xl"
                            >
                                {{ profileInitials }}
                            </div>

                            <button
                                type="button"
                                class="absolute -bottom-2 -right-2 flex h-10 w-10 items-center justify-center rounded-xl border-2 border-white bg-indigo-700 text-white shadow-lg hover:bg-indigo-800"
                                @click="openPhotoModal"
                            >
                                <Camera class="h-5 w-5" />
                            </button>
                        </div>

                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-[0.18em] text-white"
                            >
                                Resident Profile
                            </p>

                            <h1
                                class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                            >
                                {{ resident.full_name }}
                            </h1>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full border border-white/25 bg-black/10 px-3 py-1.5 text-xs font-semibold text-white"
                                >
                                    {{ resident.resident_code }}
                                </span>

                                <span
                                    class="rounded-full border border-white/25 bg-black/10 px-3 py-1.5 text-xs font-semibold capitalize text-white"
                                >
                                    {{ resident.status }}
                                </span>

                                <span
                                    v-if="currentStay?.building_name"
                                    class="rounded-full border border-white/25 bg-black/10 px-3 py-1.5 text-xs font-semibold text-white"
                                >
                                    {{ currentStay.building_name }}
                                    · Room
                                    {{ currentStay.room_number || "—" }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="min-w-64 rounded-2xl border border-white/20 bg-black/10 p-5"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-white"
                                >
                                    Profile Completion
                                </p>

                                <p class="mt-1 text-2xl font-black text-white">
                                    {{ profileCompletion }}%
                                </p>
                            </div>

                            <BadgeCheck class="h-9 w-9 text-white" />
                        </div>

                        <div
                            class="mt-4 h-2 overflow-hidden rounded-full bg-white/25"
                        >
                            <div
                                class="h-full rounded-full bg-white transition-all"
                                :style="profileCompletionStyle"
                            ></div>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-white">
                            Keep your editable information accurate and up to
                            date.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Change password warning -->
            <section
                v-if="resident.must_change_password"
                class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                    />

                    <div>
                        <p class="text-sm font-bold text-amber-900">
                            Change your temporary password
                        </p>

                        <p class="mt-1 text-xs leading-5 text-amber-700">
                            Your account is using a temporary password. Create a
                            private password to secure your resident portal.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700"
                    @click="openPasswordModal"
                >
                    <KeyRound class="h-4 w-4" />
                    Change Password
                </button>
            </section>

            <!-- Summary cards -->
            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                    >
                        <CreditCard class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400"
                    >
                        Resident Code
                    </p>

                    <p class="mt-1 text-base font-bold text-slate-900">
                        {{ resident.resident_code }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                    >
                        <Building2 class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400"
                    >
                        Current Room
                    </p>

                    <p class="mt-1 text-base font-bold text-slate-900">
                        <template v-if="currentStay">
                            {{ currentStay.building_name || "—" }}
                            ·
                            {{ currentStay.room_number || "—" }}
                        </template>

                        <template v-else> Not allotted </template>
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                    >
                        <GraduationCap class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400"
                    >
                        Course
                    </p>

                    <p class="mt-1 text-base font-bold text-slate-900">
                        {{ resident.course || "Not added" }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600"
                    >
                        <Clock3 class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400"
                    >
                        Last Login
                    </p>

                    <p class="mt-1 text-sm font-bold text-slate-900">
                        {{ formatDateTime(resident.last_login_at) }}
                    </p>
                </div>
            </section>

            <!-- Main profile -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <!-- Tabs -->
                <div class="flex overflow-x-auto border-b border-slate-100">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="inline-flex min-w-fit items-center gap-2 border-b-2 px-5 py-4 text-sm font-semibold transition"
                        :class="
                            activeTab === tab.key
                                ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700'
                                : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                        "
                        @click="activeTab = tab.key"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />

                        {{ tab.label }}
                    </button>
                </div>

                <!-- Personal -->
                <div v-if="activeTab === 'personal'" class="p-5 md:p-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Personal Information
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Identity fields are managed by hostel
                            administration.
                        </p>
                    </div>

                    <div
                        class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <div>
                            <InputLabel value="First Name" />

                            <input
                                :value="resident.first_name"
                                type="text"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />
                        </div>

                        <div>
                            <InputLabel value="Last Name" />

                            <input
                                :value="resident.last_name || ''"
                                type="text"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />
                        </div>

                        <div>
                            <InputLabel value="Resident Code" />

                            <input
                                :value="resident.resident_code"
                                type="text"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />
                        </div>

                        <div>
                            <InputLabel value="Date of Birth" />

                            <input
                                :value="resident.date_of_birth || ''"
                                type="date"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />
                        </div>

                        <div>
                            <InputLabel value="Gender" />

                            <input
                                :value="humanize(resident.gender)"
                                type="text"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />
                        </div>

                        <div>
                            <InputLabel value="Blood Group" />

                            <input
                                :value="resident.blood_group || ''"
                                type="text"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />
                        </div>

                        <div>
                            <InputLabel value="Aadhaar Number" />

                            <input
                                :value="maskedAadhaar"
                                type="text"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />
                        </div>

                        <div>
                            <InputLabel value="Resident Status" />

                            <div class="mt-2">
                                <span
                                    class="inline-flex rounded-full border px-3 py-1.5 text-xs font-bold capitalize"
                                    :class="statusClasses"
                                >
                                    {{ resident.status }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Portal Access" />

                            <div class="mt-2">
                                <span
                                    class="inline-flex rounded-full border px-3 py-1.5 text-xs font-bold"
                                    :class="
                                        resident.portal_enabled
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-red-200 bg-red-50 text-red-700'
                                    "
                                >
                                    {{
                                        resident.portal_enabled
                                            ? "Enabled"
                                            : "Disabled"
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4"
                    >
                        <AlertCircle
                            class="mt-0.5 h-5 w-5 shrink-0 text-blue-700"
                        />

                        <p class="text-xs leading-5 text-blue-700">
                            Contact hostel administration to correct your name,
                            date of birth, gender, Aadhaar number, blood group,
                            primary mobile number, or account status.
                        </p>
                    </div>
                </div>

                <!-- Contact -->
                <form
                    v-else-if="activeTab === 'contact'"
                    class="p-5 md:p-6"
                    @submit.prevent="submitProfile"
                >
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Contact & Address
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Keep your email, WhatsApp number and current address
                            updated.
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <InputLabel value="Primary Mobile" />

                            <input
                                :value="resident.phone"
                                type="text"
                                readonly
                                class="mt-1 w-full cursor-not-allowed rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-600"
                            />

                            <p class="mt-1 text-[10px] text-slate-400">
                                Contact admin to change your primary mobile
                                number.
                            </p>
                        </div>

                        <div>
                            <InputLabel value="WhatsApp Number" />

                            <div class="relative mt-1">
                                <Smartphone
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                />

                                <input
                                    v-model="profileForm.whatsapp_number"
                                    type="text"
                                    maxlength="20"
                                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.whatsapp_number"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel value="Email" />

                            <div class="relative mt-1">
                                <Mail
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                />

                                <input
                                    v-model="profileForm.email"
                                    type="email"
                                    maxlength="320"
                                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.email"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel value="Current Address" />

                            <textarea
                                v-model="profileForm.address"
                                rows="4"
                                maxlength="2000"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.address"
                            />
                        </div>

                        <div>
                            <InputLabel value="City" />

                            <input
                                v-model="profileForm.city"
                                type="text"
                                maxlength="100"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.city"
                            />
                        </div>

                        <div>
                            <InputLabel value="State" />

                            <input
                                v-model="profileForm.state"
                                type="text"
                                maxlength="100"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.state"
                            />
                        </div>

                        <div>
                            <InputLabel value="Country" />

                            <input
                                v-model="profileForm.country"
                                type="text"
                                maxlength="100"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.country"
                            />
                        </div>

                        <div>
                            <InputLabel value="Pincode" />

                            <input
                                v-model="profileForm.pincode"
                                type="text"
                                maxlength="10"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.pincode"
                            />
                        </div>
                    </div>

                    <div
                        class="mt-6 flex justify-end border-t border-slate-100 pt-5"
                    >
                        <PrimaryButton :disabled="profileForm.processing">
                            <Save class="mr-2 h-4 w-4" />

                            {{
                                profileForm.processing
                                    ? "Saving..."
                                    : "Save Contact Details"
                            }}
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Academic -->
                <form
                    v-else-if="activeTab === 'academic'"
                    class="p-5 md:p-6"
                    @submit.prevent="submitProfile"
                >
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Academic Information
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Update your institute and current academic
                            information.
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <InputLabel value="Institute / College" />

                            <div class="relative mt-1">
                                <Building2
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                />

                                <input
                                    v-model="profileForm.institute"
                                    type="text"
                                    maxlength="200"
                                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.institute"
                            />
                        </div>

                        <div>
                            <InputLabel value="Course" />

                            <input
                                v-model="profileForm.course"
                                type="text"
                                maxlength="100"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.course"
                            />
                        </div>

                        <div>
                            <InputLabel value="Academic Year" />

                            <input
                                v-model="profileForm.year"
                                type="number"
                                min="1"
                                max="20"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.year"
                            />
                        </div>

                        <div>
                            <InputLabel value="Batch" />

                            <input
                                v-model="profileForm.batch"
                                type="text"
                                maxlength="50"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.batch"
                            />
                        </div>

                        <div>
                            <InputLabel value="Roll Number" />

                            <input
                                v-model="profileForm.roll_number"
                                type="text"
                                maxlength="50"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="profileForm.errors.roll_number"
                            />
                        </div>
                    </div>

                    <div
                        v-if="
                            registrationApplication?.institution_address ||
                            registrationApplication?.course_duration
                        "
                        class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5"
                    >
                        <h3
                            class="flex items-center gap-2 text-sm font-bold text-blue-900"
                        >
                            <BookOpen class="h-4 w-4" />
                            Registration Information
                        </h3>

                        <div
                            class="mt-4 grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                        >
                            <div>
                                <p class="text-xs text-blue-600">
                                    Institution Address
                                </p>

                                <p class="mt-1 font-semibold text-blue-900">
                                    {{
                                        registrationApplication.institution_address ||
                                        "—"
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-blue-600">
                                    Course Duration
                                </p>

                                <p class="mt-1 font-semibold text-blue-900">
                                    {{
                                        registrationApplication.course_duration ||
                                        "—"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-6 flex justify-end border-t border-slate-100 pt-5"
                    >
                        <PrimaryButton :disabled="profileForm.processing">
                            <Save class="mr-2 h-4 w-4" />

                            {{
                                profileForm.processing
                                    ? "Saving..."
                                    : "Save Academic Details"
                            }}
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Family -->
                <form
                    v-else-if="activeTab === 'family'"
                    class="p-5 md:p-6"
                    @submit.prevent="submitProfile"
                >
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Parent Information
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Keep your father and mother details updated for
                            emergency and official communication.
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <!-- Father -->
                        <section
                            class="rounded-2xl border border-blue-200 bg-blue-50/50 p-5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700"
                                >
                                    <UserRound class="h-5 w-5" />
                                </div>

                                <div>
                                    <h3 class="text-sm font-bold text-blue-900">
                                        Father Details
                                    </h3>

                                    <p class="text-xs text-blue-600">
                                        Editable contact information
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <InputLabel value="Father Name" />

                                    <input
                                        v-model="profileForm.father_name"
                                        type="text"
                                        maxlength="100"
                                        class="mt-1 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />

                                    <InputError
                                        class="mt-1"
                                        :message="
                                            profileForm.errors.father_name
                                        "
                                    />
                                </div>

                                <div>
                                    <InputLabel value="Father Mobile" />

                                    <div class="relative mt-1">
                                        <Phone
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                        />

                                        <input
                                            v-model="profileForm.father_phone"
                                            type="text"
                                            maxlength="20"
                                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </div>

                                    <InputError
                                        class="mt-1"
                                        :message="
                                            profileForm.errors.father_phone
                                        "
                                    />
                                </div>

                                <div>
                                    <InputLabel value="Father Email" />

                                    <div class="relative mt-1">
                                        <Mail
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                        />

                                        <input
                                            v-model="profileForm.father_email"
                                            type="email"
                                            maxlength="320"
                                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </div>

                                    <InputError
                                        class="mt-1"
                                        :message="
                                            profileForm.errors.father_email
                                        "
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- Mother -->
                        <section
                            class="rounded-2xl border border-purple-200 bg-purple-50/50 p-5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700"
                                >
                                    <UserRound class="h-5 w-5" />
                                </div>

                                <div>
                                    <h3
                                        class="text-sm font-bold text-purple-900"
                                    >
                                        Mother Details
                                    </h3>

                                    <p class="text-xs text-purple-600">
                                        Editable contact information
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <InputLabel value="Mother Name" />

                                    <input
                                        v-model="profileForm.mother_name"
                                        type="text"
                                        maxlength="100"
                                        class="mt-1 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />

                                    <InputError
                                        class="mt-1"
                                        :message="
                                            profileForm.errors.mother_name
                                        "
                                    />
                                </div>

                                <div>
                                    <InputLabel value="Mother Mobile" />

                                    <div class="relative mt-1">
                                        <Phone
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                        />

                                        <input
                                            v-model="profileForm.mother_phone"
                                            type="text"
                                            maxlength="20"
                                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </div>

                                    <InputError
                                        class="mt-1"
                                        :message="
                                            profileForm.errors.mother_phone
                                        "
                                    />
                                </div>
                            </div>
                        </section>
                    </div>

                    <div
                        v-if="
                            registrationApplication?.guardian1_name ||
                            registrationApplication?.guardian2_name
                        "
                        class="mt-6"
                    >
                        <h3 class="text-sm font-bold text-slate-900">
                            Additional Guardians
                        </h3>

                        <div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div
                                v-if="registrationApplication?.guardian1_name"
                                class="rounded-xl border border-slate-200 p-4"
                            >
                                <p class="text-sm font-bold text-slate-900">
                                    {{ registrationApplication.guardian1_name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        registrationApplication.guardian1_mobile ||
                                        "No mobile"
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        registrationApplication.guardian1_occupation ||
                                        "Occupation not provided"
                                    }}
                                </p>

                                <p
                                    v-if="
                                        registrationApplication.guardian1_address
                                    "
                                    class="mt-2 text-xs leading-5 text-slate-600"
                                >
                                    {{
                                        registrationApplication.guardian1_address
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="registrationApplication?.guardian2_name"
                                class="rounded-xl border border-slate-200 p-4"
                            >
                                <p class="text-sm font-bold text-slate-900">
                                    {{ registrationApplication.guardian2_name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        registrationApplication.guardian2_mobile ||
                                        "No mobile"
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        registrationApplication.guardian2_occupation ||
                                        "Occupation not provided"
                                    }}
                                </p>

                                <p
                                    v-if="
                                        registrationApplication.guardian2_address
                                    "
                                    class="mt-2 text-xs leading-5 text-slate-600"
                                >
                                    {{
                                        registrationApplication.guardian2_address
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-6 flex justify-end border-t border-slate-100 pt-5"
                    >
                        <PrimaryButton :disabled="profileForm.processing">
                            <Save class="mr-2 h-4 w-4" />

                            {{
                                profileForm.processing
                                    ? "Saving..."
                                    : "Save Parent Details"
                            }}
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Hostel -->
                <form v-else-if="activeTab === 'hostel'" class="p-5 md:p-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Current Hostel Stay
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Current allocation and billing configuration are
                            managed by the hostel administration.
                        </p>
                    </div>

                    <template v-if="currentStay">
                        <div
                            class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                        >
                            <div
                                class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
                            >
                                <Building2 class="h-6 w-6 text-blue-700" />

                                <p
                                    class="mt-3 text-xs font-semibold text-blue-600"
                                >
                                    Building
                                </p>

                                <p class="mt-1 text-sm font-bold text-blue-900">
                                    {{ currentStay.building_name || "—" }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                            >
                                <Home class="h-6 w-6 text-indigo-700" />

                                <p
                                    class="mt-3 text-xs font-semibold text-indigo-600"
                                >
                                    Floor
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-indigo-900"
                                >
                                    {{
                                        currentStay.floor_name ||
                                        currentStay.floor_number ||
                                        "—"
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-purple-200 bg-purple-50 p-5"
                            >
                                <BedDouble class="h-6 w-6 text-purple-700" />

                                <p
                                    class="mt-3 text-xs font-semibold text-purple-600"
                                >
                                    Room
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-purple-900"
                                >
                                    {{ currentStay.room_number || "—" }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                            >
                                <BedDouble class="h-6 w-6 text-emerald-700" />

                                <p
                                    class="mt-3 text-xs font-semibold text-emerald-600"
                                >
                                    Bed
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-emerald-900"
                                >
                                    {{ currentStay.bed_number || "—" }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-6 grid grid-cols-1 gap-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 md:grid-cols-2 xl:grid-cols-3"
                        >
                            <div>
                                <p class="text-xs text-slate-500">
                                    Stay Status
                                </p>

                                <span
                                    class="mt-2 inline-flex rounded-full border px-3 py-1 text-xs font-bold capitalize"
                                    :class="stayStatusClasses"
                                >
                                    {{ currentStay.status }}
                                </span>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">
                                    Check-in Date
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{ formatDate(currentStay.check_in_date) }}
                                </p>
                            </div>

                            <div>
                                <InputLabel
                                    for="expected_check_out_date"
                                    value="Expected Checkout"
                                />

                                <input
                                    id="expected_check_out_date"
                                    v-model="stayForm.expected_check_out_date"
                                    type="date"
                                    class="mt-1 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />

                                <InputError
                                    class="mt-1"
                                    :message="stayForm.errors.expected_check_out_date"
                                />
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">
                                    Billing Basis
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold capitalize text-slate-900"
                                >
                                    {{ currentStay.billing_basis || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">
                                    Rent Amount
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{
                                        currentStay.billing_basis === "daily"
                                            ? formatCurrency(
                                                  currentStay.daily_rate,
                                              ) + " / day"
                                            : formatCurrency(
                                                  currentStay.rent_amount,
                                              )
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">
                                    Deposit Amount
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{
                                        formatCurrency(
                                            currentStay.deposit_amount,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                            <PrimaryButton :disabled="stayForm.processing">
                                <Save class="mr-2 h-4 w-4" />

                                {{
                                    stayForm.processing
                                        ? "Saving..."
                                        : "Update Checkout Date"
                                }}
                            </PrimaryButton>
                        </div>
                    </template>

                    <div
                        v-else
                        class="mt-6 rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center"
                    >
                        <Building2 class="mx-auto h-12 w-12 text-slate-300" />

                        <h3 class="mt-4 text-sm font-bold text-slate-700">
                            No current stay found
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Your active or upcoming hostel allocation is not
                            available.
                        </p>
                    </div>
                </form>

                <!-- Registration -->
                <div
                    v-else-if="activeTab === 'registration'"
                    class="p-5 md:p-6"
                >
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Registration Information
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Additional information submitted with your hostel
                            registration.
                        </p>
                    </div>

                    <template v-if="registrationApplication">
                        <div
                            class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3"
                        >
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-xs text-slate-500">
                                    Application Number
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{ registrationApplication.application_no }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-xs text-slate-500">
                                    Application Status
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold capitalize text-slate-900"
                                >
                                    {{
                                        humanize(registrationApplication.status)
                                    }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-xs text-slate-500">
                                    Preferred Room Type
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{
                                        registrationApplication.room_type || "—"
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
                            <section
                                class="rounded-2xl border border-red-200 bg-red-50/50 p-5"
                            >
                                <h3
                                    class="flex items-center gap-2 text-sm font-bold text-red-900"
                                >
                                    <HeartPulse class="h-5 w-5" />
                                    Health Information
                                </h3>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <p class="text-xs text-red-600">
                                            Disease History
                                        </p>

                                        <p
                                            class="mt-1 whitespace-pre-line text-sm leading-6 text-red-900"
                                        >
                                            {{
                                                registrationApplication.disease_history ||
                                                "No disease history provided."
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-red-600">
                                            Allergy Details
                                        </p>

                                        <p
                                            class="mt-1 whitespace-pre-line text-sm leading-6 text-red-900"
                                        >
                                            {{
                                                registrationApplication.allergy_details ||
                                                "No allergy details provided."
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <section
                                class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5"
                            >
                                <h3 class="text-sm font-bold text-amber-900">
                                    Special Achievements
                                </h3>

                                <p
                                    class="mt-4 whitespace-pre-line text-sm leading-6 text-amber-900"
                                >
                                    {{
                                        registrationApplication.special_achievements ||
                                        "No achievements provided."
                                    }}
                                </p>
                            </section>
                        </div>

                        <section
                            v-if="
                                registrationApplication.student_photo_url ||
                                registrationApplication.father_photo_url ||
                                registrationApplication.mother_photo_url ||
                                registrationApplication.family_photo1_url ||
                                registrationApplication.family_photo2_url ||
                                registrationApplication.guardian_photo_url
                            "
                            class="mt-6"
                        >
                            <h3 class="text-sm font-bold text-slate-900">
                                Registration Photos
                            </h3>

                            <div
                                class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6"
                            >
                                <div
                                    v-if="
                                        registrationApplication.student_photo_url
                                    "
                                >
                                    <img
                                        :src="
                                            registrationApplication.student_photo_url
                                        "
                                        class="aspect-square w-full rounded-xl border border-slate-200 object-cover"
                                    />

                                    <p
                                        class="mt-2 text-center text-xs font-semibold text-slate-600"
                                    >
                                        Student
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        registrationApplication.father_photo_url
                                    "
                                >
                                    <img
                                        :src="
                                            registrationApplication.father_photo_url
                                        "
                                        class="aspect-square w-full rounded-xl border border-slate-200 object-cover"
                                    />

                                    <p
                                        class="mt-2 text-center text-xs font-semibold text-slate-600"
                                    >
                                        Father
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        registrationApplication.mother_photo_url
                                    "
                                >
                                    <img
                                        :src="
                                            registrationApplication.mother_photo_url
                                        "
                                        class="aspect-square w-full rounded-xl border border-slate-200 object-cover"
                                    />

                                    <p
                                        class="mt-2 text-center text-xs font-semibold text-slate-600"
                                    >
                                        Mother
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        registrationApplication.family_photo1_url
                                    "
                                >
                                    <img
                                        :src="
                                            registrationApplication.family_photo1_url
                                        "
                                        class="aspect-square w-full rounded-xl border border-slate-200 object-cover"
                                    />

                                    <p
                                        class="mt-2 text-center text-xs font-semibold text-slate-600"
                                    >
                                        Family Photo 1
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        registrationApplication.family_photo2_url
                                    "
                                >
                                    <img
                                        :src="
                                            registrationApplication.family_photo2_url
                                        "
                                        class="aspect-square w-full rounded-xl border border-slate-200 object-cover"
                                    />

                                    <p
                                        class="mt-2 text-center text-xs font-semibold text-slate-600"
                                    >
                                        Family Photo 2
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        registrationApplication.guardian_photo_url
                                    "
                                >
                                    <img
                                        :src="
                                            registrationApplication.guardian_photo_url
                                        "
                                        class="aspect-square w-full rounded-xl border border-slate-200 object-cover"
                                    />

                                    <p
                                        class="mt-2 text-center text-xs font-semibold text-slate-600"
                                    >
                                        Guardian
                                    </p>
                                </div>
                            </div>
                        </section>

                        <div
                            v-if="registrationApplication.admin_remarks"
                            class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-blue-600"
                            >
                                Administration Remarks
                            </p>

                            <p
                                class="mt-2 whitespace-pre-line text-sm leading-6 text-blue-900"
                            >
                                {{ registrationApplication.admin_remarks }}
                            </p>
                        </div>
                    </template>

                    <div
                        v-else
                        class="mt-6 rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center"
                    >
                        <FileText class="mx-auto h-12 w-12 text-slate-300" />

                        <h3 class="mt-4 text-sm font-bold text-slate-700">
                            Registration application not linked
                        </h3>
                    </div>
                </div>

                <!-- Security -->
                <div v-else-if="activeTab === 'security'" class="p-5 md:p-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Account Security
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Review portal activity and maintain a secure
                            password.
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <section
                            class="rounded-2xl border border-slate-200 p-5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <LockKeyhole class="h-5 w-5" />
                                </div>

                                <div>
                                    <h3
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        Password
                                    </h3>

                                    <p class="text-xs text-slate-500">
                                        Last changed
                                        {{
                                            formatDateTime(
                                                resident.password_changed_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                                @click="openPasswordModal"
                            >
                                <KeyRound class="h-4 w-4" />
                                Change Password
                            </button>
                        </section>

                        <section
                            class="rounded-2xl border border-slate-200 p-5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                                >
                                    <CheckCircle2 class="h-5 w-5" />
                                </div>

                                <div>
                                    <h3
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        Portal Activity
                                    </h3>

                                    <p class="text-xs text-slate-500">
                                        Resident account access details
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <span class="text-xs text-slate-500">
                                        Last Login
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-slate-800"
                                    >
                                        {{
                                            formatDateTime(
                                                resident.last_login_at,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <span class="text-xs text-slate-500">
                                        Portal Status
                                    </span>

                                    <span
                                        class="text-xs font-semibold"
                                        :class="
                                            resident.portal_enabled
                                                ? 'text-emerald-700'
                                                : 'text-red-700'
                                        "
                                    >
                                        {{
                                            resident.portal_enabled
                                                ? "Enabled"
                                                : "Disabled"
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <span class="text-xs text-slate-500">
                                        Password Change Required
                                    </span>

                                    <span
                                        class="text-xs font-semibold"
                                        :class="
                                            resident.must_change_password
                                                ? 'text-amber-700'
                                                : 'text-emerald-700'
                                        "
                                    >
                                        {{
                                            resident.must_change_password
                                                ? "Yes"
                                                : "No"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </div>

        <!-- Photo modal -->
        <Modal :show="photoOpen" maxWidth="md" @close="photoOpen = false">
            <form class="p-6" @submit.prevent="submitPhoto">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Update Profile Photo
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Upload a clear photograph in JPG, PNG or WEBP
                            format.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="photoOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6 text-center">
                    <img
                        v-if="photoPreview"
                        :src="photoPreview"
                        class="mx-auto h-40 w-40 rounded-3xl border border-slate-200 object-cover"
                    />

                    <div
                        v-else
                        class="mx-auto flex h-40 w-40 items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-slate-50"
                    >
                        <Camera class="h-10 w-10 text-slate-300" />
                    </div>

                    <label
                        class="mt-5 inline-flex cursor-pointer items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                    >
                        <Camera class="h-4 w-4" />
                        Choose Photo

                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="hidden"
                            @change="onPhotoChange"
                        />
                    </label>

                    <p class="mt-2 text-xs text-slate-500">
                        Maximum file size: 4 MB
                    </p>

                    <InputError
                        class="mt-2"
                        :message="photoForm.errors.photo"
                    />
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="photoOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="photoForm.processing || !photoForm.photo"
                    >
                        {{
                            photoForm.processing
                                ? "Uploading..."
                                : "Update Photo"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Password modal -->
        <Modal :show="passwordOpen" maxWidth="md" @close="passwordOpen = false">
            <form class="p-6" @submit.prevent="submitPassword">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Change Password
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Use at least eight characters with letters and
                            numbers.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="passwordOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6 space-y-5">
                    <div>
                        <InputLabel value="Current Password *" />

                        <div class="relative mt-1">
                            <input
                                v-model="passwordForm.current_password"
                                :type="
                                    showCurrentPassword ? 'text' : 'password'
                                "
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border-slate-300 pr-11 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                                @click="
                                    showCurrentPassword = !showCurrentPassword
                                "
                            >
                                <EyeOff
                                    v-if="showCurrentPassword"
                                    class="h-4 w-4"
                                />

                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>

                        <InputError
                            class="mt-1"
                            :message="passwordForm.errors.current_password"
                        />
                    </div>

                    <div>
                        <InputLabel value="New Password *" />

                        <div class="relative mt-1">
                            <input
                                v-model="passwordForm.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border-slate-300 pr-11 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />

                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>

                        <InputError
                            class="mt-1"
                            :message="passwordForm.errors.password"
                        />
                    </div>

                    <div>
                        <InputLabel value="Confirm New Password *" />

                        <div class="relative mt-1">
                            <input
                                v-model="passwordForm.password_confirmation"
                                :type="
                                    showPasswordConfirmation
                                        ? 'text'
                                        : 'password'
                                "
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border-slate-300 pr-11 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                                @click="
                                    showPasswordConfirmation =
                                        !showPasswordConfirmation
                                "
                            >
                                <EyeOff
                                    v-if="showPasswordConfirmation"
                                    class="h-4 w-4"
                                />

                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="passwordOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="passwordForm.processing">
                        {{
                            passwordForm.processing
                                ? "Updating..."
                                : "Change Password"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>