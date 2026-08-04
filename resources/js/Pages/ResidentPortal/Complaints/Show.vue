<script setup>
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    CircleAlert,
    Clock3,
    Copy,
    MapPin,
    MessageSquareWarning,
    ShieldCheck,
    Star,
    Trash2,
    UserRound,
    X,
    XCircle,
} from "lucide-vue-next";
import { computed, ref } from "vue";

const props = defineProps({
    complaint: {
        type: Object,
        required: true,
    },
});

const ratingOpen = ref(false);
const copied = ref(false);
const hoveredStar = ref(0);

const ratingForm = useForm({
    rating: props.complaint.rating || 0,
});

const statusClasses = {
    open: "border-amber-200 bg-amber-50 text-amber-700",
    in_progress: "border-blue-200 bg-blue-50 text-blue-700",
    resolved: "border-emerald-200 bg-emerald-50 text-emerald-700",
    escalated: "border-red-200 bg-red-50 text-red-700",
    rejected: "border-slate-200 bg-slate-100 text-slate-600",
};

const priorityClasses = {
    low: "border-slate-200 bg-slate-50 text-slate-700",
    medium: "border-blue-200 bg-blue-50 text-blue-700",
    high: "border-amber-200 bg-amber-50 text-amber-700",
    urgent: "border-red-200 bg-red-50 text-red-700",
};

const labelize = (value) => {
    if (!value) return "Submitted";

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const updateIcon = (status) => {
    if (status === "resolved") {
        return CheckCircle2;
    }

    if (status === "rejected") {
        return XCircle;
    }

    if (status === "escalated") {
        return AlertTriangle;
    }

    if (status === "in_progress") {
        return Clock3;
    }

    return CircleAlert;
};

const updateIconClass = (status) => {
    if (status === "resolved") {
        return "bg-emerald-100 text-emerald-700";
    }

    if (status === "rejected") {
        return "bg-red-100 text-red-700";
    }

    if (status === "escalated") {
        return "bg-orange-100 text-orange-700";
    }

    if (status === "in_progress") {
        return "bg-blue-100 text-blue-700";
    }

    return "bg-amber-100 text-amber-700";
};

const statusIcon = computed(() => {
    if (props.complaint.status === "resolved") {
        return CheckCircle2;
    }

    if (props.complaint.status === "rejected") {
        return XCircle;
    }

    if (props.complaint.status === "escalated") {
        return AlertTriangle;
    }

    if (props.complaint.status === "in_progress") {
        return Clock3;
    }

    return CircleAlert;
});

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

const complaintLocation = computed(() => {
    const parts = [];

    if (props.complaint.building?.name) {
        parts.push(props.complaint.building.name);
    }

    if (props.complaint.room?.room_number) {
        parts.push(`Room ${props.complaint.room.room_number}`);
    }

    return parts.length ? parts.join(" · ") : "No room linked";
});

const submitRating = () => {
    ratingForm.post(
        route("resident.complaints.rate", {
            complaint: props.complaint.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                ratingOpen.value = false;
            },
        },
    );
};

const destroyComplaint = () => {
    if (!confirm("Delete this complaint? This action cannot be reversed.")) {
        return;
    }

    router.delete(
        route("resident.complaints.destroy", {
            complaint: props.complaint.id,
        }),
    );
};

const copyComplaintId = async () => {
    await navigator.clipboard.writeText(
        `CMP-${String(props.complaint.id).padStart(6, "0")}`,
    );

    copied.value = true;

    setTimeout(() => {
        copied.value = false;
    }, 1500);
};
</script>

<template>
    <Head :title="complaint.title" />

    <ResidentLayout title="Complaint Details">
        <div class="space-y-6">
            <!-- Back and actions -->
            <section
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <Link
                    :href="route('resident.complaints.index')"
                    class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Complaints
                </Link>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="complaint.can_rate"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600"
                        @click="
                            ratingForm.rating = 0;
                            ratingOpen = true;
                        "
                    >
                        <Star class="h-4 w-4" />
                        Rate Resolution
                    </button>

                    <button
                        v-if="complaint.can_delete"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                        @click="destroyComplaint"
                    >
                        <Trash2 class="h-4 w-4" />
                        Delete Complaint
                    </button>
                </div>
            </section>

            <!-- Complaint header -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="bg-gradient-to-r from-indigo-700 to-indigo-500 px-6 py-7 text-white"
                >
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15"
                                >
                                    <MessageSquareWarning class="h-6 w-6" />
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100"
                                    >
                                        Complaint
                                    </p>

                                    <h1
                                        class="mt-1 break-words text-2xl font-bold"
                                    >
                                        {{ complaint.title }}
                                    </h1>
                                </div>
                            </div>

                            <div
                                class="mt-5 flex flex-wrap items-center gap-3 text-xs text-indigo-100"
                            >
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5"
                                    @click="copyComplaintId"
                                >
                                    <Copy class="h-3.5 w-3.5" />

                                    {{
                                        copied
                                            ? "Copied"
                                            : `CMP-${String(
                                                  complaint.id,
                                              ).padStart(6, "0")}`
                                    }}
                                </button>

                                <span>
                                    Submitted
                                    {{ formatDateTime(complaint.created_at) }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="flex flex-col items-start gap-2 sm:items-end"
                        >
                            <span
                                class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-4 py-2 text-xs font-bold"
                            >
                                <component :is="statusIcon" class="h-4 w-4" />

                                {{ complaint.status_label }}
                            </span>

                            <span
                                class="inline-flex rounded-full border px-3 py-1.5 text-xs font-bold"
                                :class="priorityClasses[complaint.priority]"
                            >
                                {{ complaint.priority_label }}
                                Priority
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Summary information -->
                <div
                    class="grid grid-cols-1 gap-4 border-b border-slate-100 p-6 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Category
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ complaint.category_label }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Location
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ complaintLocation }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Assigned To
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{
                                complaint.assigned_to?.name ||
                                "Not assigned yet"
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Last Updated
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ formatDateTime(complaint.updated_at) }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">
                    <!-- Main details -->
                    <div class="space-y-6 lg:col-span-2">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Complaint Description
                            </h2>

                            <div
                                class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                            >
                                <p
                                    class="whitespace-pre-line text-sm leading-7 text-slate-700"
                                >
                                    {{ complaint.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Resolution -->
                        <div
                            v-if="
                                complaint.resolution_notes ||
                                complaint.status === 'resolved' ||
                                complaint.status === 'rejected'
                            "
                        >
                            <h2 class="text-sm font-bold text-slate-900">
                                {{
                                    complaint.status === "rejected"
                                        ? "Rejection Details"
                                        : "Resolution Details"
                                }}
                            </h2>

                            <div
                                class="mt-3 rounded-2xl border p-5"
                                :class="
                                    complaint.status === 'rejected'
                                        ? 'border-red-200 bg-red-50'
                                        : 'border-emerald-200 bg-emerald-50'
                                "
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white"
                                        :class="
                                            complaint.status === 'rejected'
                                                ? 'text-red-600'
                                                : 'text-emerald-600'
                                        "
                                    >
                                        <XCircle
                                            v-if="
                                                complaint.status === 'rejected'
                                            "
                                            class="h-5 w-5"
                                        />

                                        <CheckCircle2 v-else class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p
                                            class="whitespace-pre-line text-sm leading-6"
                                            :class="
                                                complaint.status === 'rejected'
                                                    ? 'text-red-800'
                                                    : 'text-emerald-800'
                                            "
                                        >
                                            {{
                                                complaint.resolution_notes ||
                                                (complaint.status === "resolved"
                                                    ? "The complaint has been marked as resolved."
                                                    : "The complaint was rejected.")
                                            }}
                                        </p>

                                        <p
                                            v-if="complaint.resolved_at"
                                            class="mt-3 text-xs"
                                            :class="
                                                complaint.status === 'rejected'
                                                    ? 'text-red-600'
                                                    : 'text-emerald-600'
                                            "
                                        >
                                            Resolved on
                                            {{
                                                formatDateTime(
                                                    complaint.resolved_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rating -->
                        <div v-if="complaint.status === 'resolved'">
                            <h2 class="text-sm font-bold text-slate-900">
                                Resolution Rating
                            </h2>

                            <div
                                class="mt-3 rounded-2xl border border-slate-200 p-5"
                            >
                                <template v-if="complaint.rating">
                                    <div class="flex items-center gap-2">
                                        <Star
                                            v-for="star in 5"
                                            :key="star"
                                            class="h-6 w-6"
                                            :class="
                                                star <= complaint.rating
                                                    ? 'fill-amber-400 text-amber-400'
                                                    : 'text-slate-300'
                                            "
                                        />
                                    </div>

                                    <p class="mt-3 text-sm text-slate-600">
                                        You rated this resolution
                                        {{ complaint.rating }}/5.
                                    </p>
                                </template>

                                <template v-else>
                                    <p class="text-sm text-slate-600">
                                        Share your feedback about how well the
                                        complaint was resolved.
                                    </p>

                                    <button
                                        v-if="complaint.can_rate"
                                        type="button"
                                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600"
                                        @click="
                                            ratingForm.rating = 0;
                                            ratingOpen = true;
                                        "
                                    >
                                        <Star class="h-4 w-4" />
                                        Rate Resolution
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <aside class="rounded-2xl border border-slate-200 p-5">
                        <h2 class="text-sm font-bold text-slate-900">
                            Complaint Timeline
                        </h2>

                        <p class="mt-1 text-xs text-slate-400">
                            Complete history of updates and remarks.
                        </p>

                        <div
                            v-if="complaint.updates?.length"
                            class="mt-5 space-y-5"
                        >
                            <div
                                v-for="(update, index) in complaint.updates"
                                :key="update.id"
                                class="relative flex items-start gap-3"
                            >
                                <div
                                    v-if="
                                        index !== complaint.updates.length - 1
                                    "
                                    class="absolute left-[17px] top-9 h-[calc(100%+8px)] w-px bg-slate-200"
                                ></div>

                                <div
                                    class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                    :class="updateIconClass(update.new_status)"
                                >
                                    <component
                                        :is="updateIcon(update.new_status)"
                                        class="h-4 w-4"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        <template v-if="!update.old_status">
                                            Complaint Submitted
                                        </template>

                                        <template v-else>
                                            {{ labelize(update.old_status) }}
                                            →
                                            {{ labelize(update.new_status) }}
                                        </template>
                                    </p>

                                    <p
                                        v-if="update.remarks"
                                        class="mt-1 whitespace-pre-line text-xs leading-5 text-slate-600"
                                    >
                                        {{ update.remarks }}
                                    </p>

                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-2 text-[10px] text-slate-400"
                                    >
                                        <span>
                                            {{
                                                formatDateTime(
                                                    update.created_at,
                                                )
                                            }}
                                        </span>

                                        <span v-if="update.updated_by">
                                            · Updated by
                                            {{ update.updated_by.name }}
                                        </span>

                                        <span v-else>
                                            · Resident submission
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-5 rounded-xl bg-slate-50 p-4 text-center text-xs text-slate-400"
                        >
                            No complaint updates available.
                        </div>
                    </aside>
                </div>
            </section>

            <!-- Status explanation -->
            <section
                class="rounded-2xl border p-5"
                :class="statusClasses[complaint.status]"
            >
                <div class="flex items-start gap-3">
                    <component
                        :is="statusIcon"
                        class="mt-0.5 h-5 w-5 shrink-0"
                    />

                    <div>
                        <h3 class="text-sm font-bold">
                            {{ complaint.status_label }}
                        </h3>

                        <p class="mt-1 text-xs leading-5">
                            <template v-if="complaint.status === 'open'">
                                Your complaint has been received and is waiting
                                for assignment.
                            </template>

                            <template
                                v-else-if="complaint.status === 'in_progress'"
                            >
                                The hostel team is currently working on this
                                issue.
                            </template>

                            <template
                                v-else-if="complaint.status === 'resolved'"
                            >
                                The complaint has been completed. Please verify
                                the resolution and provide a rating.
                            </template>

                            <template
                                v-else-if="complaint.status === 'escalated'"
                            >
                                The complaint has been escalated for urgent or
                                higher-level attention.
                            </template>

                            <template v-else>
                                The complaint was rejected. Review the
                                resolution notes for details.
                            </template>
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Rating modal -->
        <Modal :show="ratingOpen" maxWidth="md" @close="ratingOpen = false">
            <form class="p-6" @submit.prevent="submitRating">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Rate Resolution
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            How satisfied are you with the resolution of this
                            complaint?
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="ratingOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="mt-6 flex items-center justify-center gap-2"
                    @mouseleave="hoveredStar = 0"
                >
                    <button
                        v-for="star in 5"
                        :key="star"
                        type="button"
                        class="rounded-lg p-1 transition hover:scale-110"
                        @mouseenter="hoveredStar = star"
                        @click="ratingForm.rating = star"
                    >
                        <Star
                            class="h-10 w-10"
                            :class="
                                star <= (hoveredStar || ratingForm.rating)
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-slate-300'
                            "
                        />
                    </button>
                </div>

                <p
                    class="mt-3 text-center text-sm font-semibold text-slate-700"
                >
                    <template v-if="ratingForm.rating === 1">
                        Very dissatisfied
                    </template>

                    <template v-else-if="ratingForm.rating === 2">
                        Dissatisfied
                    </template>

                    <template v-else-if="ratingForm.rating === 3">
                        Satisfied
                    </template>

                    <template v-else-if="ratingForm.rating === 4">
                        Very satisfied
                    </template>

                    <template v-else-if="ratingForm.rating === 5">
                        Excellent
                    </template>

                    <template v-else> Select a rating </template>
                </p>

                <InputError
                    class="mt-3 text-center"
                    :message="ratingForm.errors.rating"
                />

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="ratingOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="ratingForm.processing || !ratingForm.rating"
                    >
                        {{
                            ratingForm.processing
                                ? "Submitting..."
                                : "Submit Rating"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>
