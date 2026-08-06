<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowRight,
    ArrowRightLeft,
    Building2,
    CalendarDays,
    ChevronDown,
    ChevronUp,
    CircleHelp,
    Clock3,
    CreditCard,
    FileText,
    FolderOpen,
    Headphones,
    HelpCircle,
    Mail,
    MapPin,
    Megaphone,
    MessageCircle,
    MessageSquareWarning,
    Phone,
    Plus,
    Send,
    ShieldAlert,
    Siren,
    UserRound,
    WalletCards,
    X,
} from "lucide-vue-next";
import { computed, ref } from "vue";

const props = defineProps({
    contact: {
        type: Object,
        required: true,
    },

    currentStay: {
        type: Object,
        default: null,
    },

    recentRequests: {
        type: Array,
        default: () => [],
    },

    faqs: {
        type: Array,
        default: () => [],
    },
});

const supportOpen = ref(false);
const expandedFaq = ref(null);

const form = useForm({
    subject: "",
    message: "",
    priority: "medium",
});

const quickLinks = [
    {
        label: "Billing",
        description: "Invoices and dues",
        route: "resident.billing.index",
        icon: CreditCard,
        classes: "border-sky-200 bg-sky-50 text-sky-700",
    },
    {
        label: "Payments",
        description: "Payments and receipts",
        route: "resident.payments.index",
        icon: WalletCards,
        classes: "border-emerald-200 bg-emerald-50 text-emerald-700",
    },
    {
        label: "Leaves",
        description: "Leave applications",
        route: "resident.leaves.index",
        icon: CalendarDays,
        classes: "border-blue-200 bg-blue-50 text-blue-700",
    },
    {
        label: "Complaints",
        description: "Report hostel issues",
        route: "resident.complaints.index",
        icon: MessageSquareWarning,
        classes: "border-orange-200 bg-orange-50 text-orange-700",
    },
    {
        label: "Room Change",
        description: "Room transfer request",
        route: "resident.room-change-requests.index",
        icon: ArrowRightLeft,
        classes: "border-violet-200 bg-violet-50 text-violet-700",
    },
    {
        label: "Documents",
        description: "KYC and records",
        route: "resident.documents.index",
        icon: FolderOpen,
        classes: "border-cyan-200 bg-cyan-50 text-cyan-700",
    },
    {
        label: "Notices",
        description: "Hostel announcements",
        route: "resident.notices.index",
        icon: Megaphone,
        classes: "border-indigo-200 bg-indigo-50 text-indigo-700",
    },
    {
        label: "My Profile",
        description: "Update profile details",
        route: "resident.profile.index",
        icon: UserRound,
        classes: "border-slate-200 bg-slate-50 text-slate-700",
    },
];

const statusClasses = {
    open: "border-blue-200 bg-blue-50 text-blue-700",

    in_progress: "border-amber-200 bg-amber-50 text-amber-700",

    resolved: "border-emerald-200 bg-emerald-50 text-emerald-700",

    escalated: "border-red-200 bg-red-50 text-red-700",

    rejected: "border-slate-200 bg-slate-50 text-slate-700",
};

const priorityClasses = {
    low: "border-slate-200 bg-slate-50 text-slate-600",

    medium: "border-blue-200 bg-blue-50 text-blue-700",

    high: "border-orange-200 bg-orange-50 text-orange-700",
};

const cleanPhone = (value) => {
    return String(value || "").replace(/[^+\d]/g, "");
};

const whatsappLink = (number) => {
    const clean = String(number || "").replace(/\D/g, "");

    const message = encodeURIComponent(
        `Hello, I am a resident of ${props.contact.hostel_name}. I need assistance.`,
    );

    return `https://wa.me/${clean}?text=${message}`;
};

const officePhoneLink = computed(() => {
    return `tel:${cleanPhone(props.contact.office_phone)}`;
});

const receptionPhoneLink = computed(() => {
    return `tel:${cleanPhone(props.contact.reception_phone)}`;
});

const wardenPhoneLink = computed(() => {
    return `tel:${cleanPhone(props.contact.warden_phone)}`;
});

const emergencyPhoneLink = computed(() => {
    return `tel:${cleanPhone(props.contact.emergency_phone)}`;
});

const officeEmailLink = computed(() => {
    return `mailto:${props.contact.office_email}`;
});

const toggleFaq = (index) => {
    expandedFaq.value = expandedFaq.value === index ? null : index;
};

const openSupportForm = () => {
    form.reset();
    form.clearErrors();
    form.priority = "medium";
    supportOpen.value = true;
};

const submitSupportRequest = () => {
    form.post(route("resident.support.store"), {
        preserveScroll: true,

        onSuccess: () => {
            supportOpen.value = false;
            form.reset();
        },
    });
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

const humanize = (value) => {
    return String(value || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
};
</script>

<template>
    <Head title="Help & Support" />

    <ResidentLayout title="Help & Support">
        <div class="space-y-6">
            <!-- Hero -->
            <section
                class="overflow-hidden rounded-3xl border border-teal-200 bg-[linear-gradient(135deg,#134e4a_0%,#0d9488_52%,#2dd4bf_100%)] text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-black/10"
                            >
                                <Headphones class="h-7 w-7" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.18em] text-white"
                                >
                                    Resident Assistance
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                                >
                                    Help & Support
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm font-medium leading-6 text-white"
                        >
                            Contact the hostel office, find answers to common
                            questions, or submit a general support request.
                        </p>

                        <div
                            v-if="currentStay"
                            class="mt-5 inline-flex flex-wrap items-center gap-2 rounded-xl border border-white/20 bg-black/10 px-4 py-3 text-xs font-semibold text-white"
                        >
                            <Building2 class="h-4 w-4" />

                            {{
                                currentStay.building_name || "Current Building"
                            }}

                            <span>·</span>

                            Room
                            {{ currentStay.room_number || "—" }}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-teal-700 shadow-lg transition hover:scale-105"
                        @click="openSupportForm"
                    >
                        <Plus class="h-4 w-4" />
                        Submit Support Request
                    </button>
                </div>
            </section>

            <!-- Emergency warning -->
            <section
                class="flex flex-col gap-4 rounded-2xl border border-red-300 bg-red-50 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <ShieldAlert class="mt-0.5 h-6 w-6 shrink-0 text-red-700" />

                    <div>
                        <p class="text-sm font-bold text-red-900">
                            Is this an emergency?
                        </p>

                        <p class="mt-1 text-xs leading-5 text-red-700">
                            Do not use a normal support request for urgent
                            medical, safety, fire, violence, or threat
                            situations.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        :href="emergencyPhoneLink"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-bold text-red-700"
                    >
                        <Phone class="h-4 w-4" />
                        Call Emergency
                    </a>

                    <Link
                        :href="route('resident.emergency.index')"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-700 px-4 py-2.5 text-sm font-bold text-white"
                    >
                        <Siren class="h-4 w-4" />
                        Raise SOS
                    </Link>
                </div>
            </section>

            <!-- Contact cards -->
            <section
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
            >
                <article
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"
                    >
                        <Building2 class="h-5 w-5" />
                    </div>

                    <h2 class="mt-4 text-sm font-bold text-indigo-900">
                        Hostel Office
                    </h2>

                    <p class="mt-1 text-xs text-indigo-600">
                        General administration assistance
                    </p>

                    <div class="mt-4 space-y-2">
                        <a
                            :href="officePhoneLink"
                            class="flex items-center gap-2 text-xs font-semibold text-indigo-800"
                        >
                            <Phone class="h-4 w-4" />
                            {{ contact.office_phone }}
                        </a>

                        <a
                            :href="officeEmailLink"
                            class="flex items-center gap-2 break-all text-xs font-semibold text-indigo-800"
                        >
                            <Mail class="h-4 w-4 shrink-0" />
                            {{ contact.office_email }}
                        </a>
                    </div>
                </article>

                <article
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"
                    >
                        <MessageCircle class="h-5 w-5" />
                    </div>

                    <h2 class="mt-4 text-sm font-bold text-emerald-900">
                        WhatsApp Support
                    </h2>

                    <p class="mt-1 text-xs text-emerald-600">
                        Message the hostel office
                    </p>

                    <a
                        :href="whatsappLink(contact.office_whatsapp)"
                        target="_blank"
                        rel="noopener"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Open WhatsApp
                    </a>
                </article>

                <article
                    class="rounded-2xl border border-violet-200 bg-violet-50 p-5"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700"
                    >
                        <UserRound class="h-5 w-5" />
                    </div>

                    <h2 class="mt-4 text-sm font-bold text-violet-900">
                        {{ contact.warden_name }}
                    </h2>

                    <p class="mt-1 text-xs text-violet-600">
                        Warden assistance
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a
                            :href="wardenPhoneLink"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 bg-white px-3 py-2 text-xs font-semibold text-violet-700"
                        >
                            <Phone class="h-3.5 w-3.5" />
                            Call
                        </a>

                        <a
                            :href="whatsappLink(contact.warden_whatsapp)"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white"
                        >
                            <MessageCircle class="h-3.5 w-3.5" />
                            WhatsApp
                        </a>
                    </div>
                </article>

                <article
                    class="rounded-2xl border border-orange-200 bg-orange-50 p-5"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-100 text-orange-700"
                    >
                        <Clock3 class="h-5 w-5" />
                    </div>

                    <h2 class="mt-4 text-sm font-bold text-orange-900">
                        Office Timings
                    </h2>

                    <p class="mt-2 text-xs leading-5 text-orange-700">
                        {{ contact.office_timings }}
                    </p>

                    <a
                        :href="receptionPhoneLink"
                        class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-orange-800"
                    >
                        <Phone class="h-4 w-4" />
                        Reception:
                        {{ contact.reception_phone }}
                    </a>
                </article>
            </section>

            <!-- Address -->
            <section
                class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-indigo-600" />

                <div>
                    <p class="text-sm font-bold text-slate-900">
                        Hostel Office Address
                    </p>

                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        {{ contact.office_address }}
                    </p>
                </div>
            </section>

            <!-- Quick links -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Quick Help
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Open the relevant resident service directly.
                    </p>
                </div>

                <div
                    class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <Link
                        v-for="item in quickLinks"
                        :key="item.label"
                        :href="route(item.route)"
                        class="group flex items-center gap-3 rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm"
                        :class="item.classes"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/70"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold">
                                {{ item.label }}
                            </p>

                            <p class="mt-0.5 truncate text-[11px] opacity-80">
                                {{ item.description }}
                            </p>
                        </div>

                        <ArrowRight
                            class="h-4 w-4 shrink-0 opacity-50 transition group-hover:translate-x-0.5"
                        />
                    </Link>
                </div>
            </section>

            <!-- FAQ -->
            <section
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-slate-900"
                    >
                        <CircleHelp class="h-5 w-5 text-indigo-600" />
                        Frequently Asked Questions
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Common resident portal and hostel questions.
                    </p>
                </div>

                <div class="divide-y divide-slate-100">
                    <article v-for="(faq, index) in faqs" :key="faq.question">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50"
                            @click="toggleFaq(index)"
                        >
                            <span class="text-sm font-semibold text-slate-800">
                                {{ faq.question }}
                            </span>

                            <ChevronUp
                                v-if="expandedFaq === index"
                                class="h-4 w-4 shrink-0 text-indigo-600"
                            />

                            <ChevronDown
                                v-else
                                class="h-4 w-4 shrink-0 text-slate-400"
                            />
                        </button>

                        <div v-if="expandedFaq === index" class="px-5 pb-5">
                            <p
                                class="rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-600"
                            >
                                {{ faq.answer }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Recent support requests -->
            <section
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Recent Support Requests
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            General support requests submitted through this
                            page.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                        @click="openSupportForm"
                    >
                        <Plus class="h-4 w-4" />
                        New Request
                    </button>
                </div>

                <div
                    v-if="recentRequests.length"
                    class="divide-y divide-slate-100"
                >
                    <article
                        v-for="request in recentRequests"
                        :key="request.id"
                        class="px-5 py-4"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">
                                    {{ request.title }}
                                </p>

                                <p
                                    class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500"
                                >
                                    {{ request.description }}
                                </p>

                                <p class="mt-2 text-[10px] text-slate-400">
                                    {{ formatDateTime(request.created_at) }}
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                    :class="priorityClasses[request.priority]"
                                >
                                    {{ humanize(request.priority) }}
                                </span>

                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                    :class="statusClasses[request.status]"
                                >
                                    {{ humanize(request.status) }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="request.resolution_notes"
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-wide text-emerald-700"
                            >
                                Administration Response
                            </p>

                            <p class="mt-1 text-xs leading-5 text-emerald-800">
                                {{ request.resolution_notes }}
                            </p>
                        </div>
                    </article>
                </div>

                <div v-else class="px-6 py-12 text-center">
                    <HelpCircle class="mx-auto h-10 w-10 text-slate-300" />

                    <p class="mt-3 text-sm font-bold text-slate-700">
                        No support requests yet
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Use the support form for questions that do not fit
                        another service.
                    </p>
                </div>
            </section>
        </div>

        <!-- Support request modal -->
        <Modal :show="supportOpen" maxWidth="lg" @close="supportOpen = false">
            <form class="p-6" @submit.prevent="submitSupportRequest">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Submit Support Request
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Use this for general assistance. Maintenance issues
                            should be submitted from Complaints.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="supportOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6 space-y-5">
                    <div>
                        <InputLabel value="Subject *" />

                        <input
                            v-model="form.subject"
                            type="text"
                            required
                            maxlength="200"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Briefly describe what you need help with"
                        />

                        <InputError
                            class="mt-1"
                            :message="form.errors.subject"
                        />
                    </div>

                    <div>
                        <InputLabel value="Priority *" />

                        <select
                            v-model="form.priority"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="low">Low</option>

                            <option value="medium">Medium</option>

                            <option value="high">High</option>
                        </select>

                        <InputError
                            class="mt-1"
                            :message="form.errors.priority"
                        />
                    </div>

                    <div>
                        <InputLabel value="Message *" />

                        <textarea
                            v-model="form.message"
                            rows="6"
                            required
                            maxlength="5000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Explain your question or support requirement"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="form.errors.message"
                        />
                    </div>

                    <div
                        class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <AlertTriangle
                            class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                        />

                        <p class="text-xs leading-5 text-amber-700">
                            For immediate safety or medical assistance, use
                            Emergency SOS instead of this form.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="supportOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="form.processing">
                        <Send class="mr-2 h-4 w-4" />

                        {{
                            form.processing ? "Submitting..." : "Submit Request"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>