<script setup>
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    CircleCheck,
    Clock3,
    Download,
    FileSpreadsheet,
    FileText,
    FileType2,
    Image,
    Info,
    Megaphone,
    Paperclip,
    Pin,
    ShieldCheck,
    UserRound,
    X,
} from "lucide-vue-next";
import { computed, ref } from "vue";

const props = defineProps({
    notice: {
        type: Object,
        required: true,
    },
});

const acknowledgeOpen = ref(false);

const acknowledgementForm = useForm({});

const priorityClasses = {
    normal: "border-slate-200 bg-slate-50 text-slate-700",
    important: "border-amber-200 bg-amber-50 text-amber-700",
    urgent: "border-red-200 bg-red-50 text-red-700",
};

const priorityHeaderClasses = {
    normal: "bg-[linear-gradient(135deg,#312e81_0%,#4f46e5_55%,#6366f1_100%)]",
    important:
        "bg-[linear-gradient(135deg,#92400e_0%,#d97706_55%,#f59e0b_100%)]",
    urgent:
        "bg-[linear-gradient(135deg,#991b1b_0%,#dc2626_55%,#e11d48_100%)]",
};

const categoryClasses = {
    general: "bg-slate-100 text-slate-700",
    academic: "bg-blue-100 text-blue-700",
    hostel: "bg-indigo-100 text-indigo-700",
    mess: "bg-orange-100 text-orange-700",
    maintenance: "bg-yellow-100 text-yellow-700",
    event: "bg-purple-100 text-purple-700",
    payment: "bg-emerald-100 text-emerald-700",
    emergency: "bg-red-100 text-red-700",
    policy: "bg-cyan-100 text-cyan-700",
    other: "bg-gray-100 text-gray-700",
};

const publishedDate = computed(() => {
    return (
        props.notice.published_at ||
        props.notice.publish_at ||
        props.notice.created_at
    );
});

const acknowledgementPending = computed(() => {
    return (
        props.notice.requires_acknowledgement &&
        !props.notice.is_acknowledged
    );
});

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

const fileIcon = (attachment) => {
    const type = String(attachment.file_type || "").toLowerCase();
    const name = String(attachment.original_name || "").toLowerCase();

    if (
        type.includes("image") ||
        [".jpg", ".jpeg", ".png"].some((extension) =>
            name.endsWith(extension),
        )
    ) {
        return Image;
    }

    if (
        type.includes("spreadsheet") ||
        type.includes("excel") ||
        [".xls", ".xlsx"].some((extension) =>
            name.endsWith(extension),
        )
    ) {
        return FileSpreadsheet;
    }

    if (
        type.includes("word") ||
        type.includes("document") ||
        [".doc", ".docx"].some((extension) =>
            name.endsWith(extension),
        )
    ) {
        return FileType2;
    }

    return FileText;
};

const submitAcknowledgement = () => {
    acknowledgementForm.post(
        route("resident.notices.acknowledge", {
            notice: props.notice.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                acknowledgeOpen.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="notice.title" />

    <ResidentLayout title="Notice Details">
        <div class="space-y-6">
            <!-- Back -->
            <section
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <Link
                    :href="route('resident.notices.index')"
                    class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Notices
                </Link>

                <div
                    v-if="notice.is_acknowledged"
                    class="inline-flex w-fit items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700"
                >
                    <CircleCheck class="h-4 w-4" />
                    Acknowledged
                </div>

                <button
                    v-else-if="acknowledgementPending"
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700"
                    @click="acknowledgeOpen = true"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    Acknowledge Notice
                </button>
            </section>

            <!-- Main notice -->
            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="px-6 py-7 text-white md:px-8"
                    :class="priorityHeaderClasses[notice.priority]"
                >
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-black/10"
                                >
                                    <Megaphone class="h-6 w-6" />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <Pin
                                            v-if="notice.is_pinned"
                                            class="h-4 w-4 shrink-0"
                                        />

                                        <p
                                            class="text-xs font-bold uppercase tracking-[0.18em] text-white"
                                        >
                                            {{ notice.category_label }}
                                        </p>
                                    </div>

                                    <h1
                                        class="mt-1 break-words text-2xl font-extrabold text-white md:text-3xl"
                                    >
                                        {{ notice.title }}
                                    </h1>
                                </div>
                            </div>

                            <p
                                class="mt-5 text-sm font-medium text-white"
                            >
                                Published
                                {{ formatDateTime(publishedDate) }}
                            </p>
                        </div>

                        <div
                            class="flex flex-col items-start gap-2 sm:items-end"
                        >
                            <span
                                class="inline-flex rounded-full border border-white/25 bg-black/10 px-3 py-1.5 text-xs font-bold text-white"
                            >
                                {{ notice.priority_label }} Priority
                            </span>

                            <span
                                v-if="notice.requires_acknowledgement"
                                class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-black/10 px-3 py-1.5 text-xs font-bold text-white"
                            >
                                <ShieldCheck class="h-4 w-4" />
                                Acknowledgement Required
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Metadata -->
                <div
                    class="grid grid-cols-1 gap-4 border-b border-slate-100 p-6 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Category
                        </p>

                        <span
                            class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="categoryClasses[notice.category]"
                        >
                            {{ notice.category_label }}
                        </span>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Published On
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ formatDateTime(publishedDate) }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Valid Until
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{
                                notice.expires_at
                                    ? formatDateTime(notice.expires_at)
                                    : "No expiry"
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Published By
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ notice.created_by || "Hostel Administration" }}
                        </p>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3 lg:p-8"
                >
                    <!-- Content -->
                    <div class="space-y-6 lg:col-span-2">
                        <div
                            v-if="notice.summary"
                            class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <Info
                                    class="mt-0.5 h-5 w-5 shrink-0 text-indigo-700"
                                />

                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-wide text-indigo-600"
                                    >
                                        Summary
                                    </p>

                                    <p
                                        class="mt-2 text-sm font-medium leading-7 text-indigo-900"
                                    >
                                        {{ notice.summary }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Notice Details
                            </h2>

                            <div
                                class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-5 md:p-6"
                            >
                                <p
                                    class="whitespace-pre-line break-words text-sm leading-7 text-slate-700"
                                >
                                    {{ notice.content }}
                                </p>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div v-if="notice.attachments?.length">
                            <div class="flex items-center gap-2">
                                <Paperclip class="h-5 w-5 text-slate-500" />

                                <h2 class="text-sm font-bold text-slate-900">
                                    Attachments
                                </h2>
                            </div>

                            <div
                                class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2"
                            >
                                <a
                                    v-for="attachment in notice.attachments"
                                    :key="attachment.id"
                                    :href="
                                        route(
                                            'resident.notices.attachments.download',
                                            {
                                                notice: notice.id,
                                                attachment: attachment.id,
                                            },
                                        )
                                    "
                                    class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-3"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-white"
                                        >
                                            <component
                                                :is="fileIcon(attachment)"
                                                class="h-5 w-5"
                                            />
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-semibold text-slate-800"
                                            >
                                                {{
                                                    attachment.original_name
                                                }}
                                            </p>

                                            <p
                                                class="mt-1 text-[10px] text-slate-400"
                                            >
                                                {{
                                                    attachment.formatted_size ||
                                                    "File attachment"
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <Download
                                        class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-indigo-600"
                                    />
                                </a>
                            </div>
                        </div>

                        <!-- Acknowledgement state -->
                        <div
                            v-if="notice.requires_acknowledgement"
                            class="rounded-2xl border p-5"
                            :class="
                                notice.is_acknowledged
                                    ? 'border-emerald-200 bg-emerald-50'
                                    : 'border-amber-200 bg-amber-50'
                            "
                        >
                            <div class="flex items-start gap-3">
                                <CheckCircle2
                                    class="mt-0.5 h-5 w-5 shrink-0"
                                    :class="
                                        notice.is_acknowledged
                                            ? 'text-emerald-700'
                                            : 'text-amber-700'
                                    "
                                />

                                <div class="flex-1">
                                    <h3
                                        class="text-sm font-bold"
                                        :class="
                                            notice.is_acknowledged
                                                ? 'text-emerald-900'
                                                : 'text-amber-900'
                                        "
                                    >
                                        {{
                                            notice.is_acknowledged
                                                ? "Notice Acknowledged"
                                                : "Acknowledgement Required"
                                        }}
                                    </h3>

                                    <p
                                        class="mt-1 text-xs leading-5"
                                        :class="
                                            notice.is_acknowledged
                                                ? 'text-emerald-700'
                                                : 'text-amber-700'
                                        "
                                    >
                                        <template
                                            v-if="notice.is_acknowledged"
                                        >
                                            You confirmed reading this notice
                                            on
                                            {{
                                                formatDateTime(
                                                    notice.acknowledged_at,
                                                )
                                            }}.
                                        </template>

                                        <template v-else>
                                            Please confirm that you have read
                                            and understood this notice.
                                        </template>
                                    </p>

                                    <button
                                        v-if="!notice.is_acknowledged"
                                        type="button"
                                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700"
                                        @click="acknowledgeOpen = true"
                                    >
                                        <CheckCircle2 class="h-4 w-4" />
                                        Acknowledge Notice
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <aside class="space-y-4">
                        <div
                            class="rounded-2xl border border-slate-200 p-5"
                        >
                            <h2 class="text-sm font-bold text-slate-900">
                                Reading Status
                            </h2>

                            <div class="mt-5 space-y-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700"
                                    >
                                        <Megaphone class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Notice Published
                                        </p>

                                        <p
                                            class="mt-1 text-xs text-slate-400"
                                        >
                                            {{
                                                formatDateTime(publishedDate)
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700"
                                    >
                                        <FileText class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            First Read
                                        </p>

                                        <p
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            {{
                                                formatDateTime(
                                                    notice.first_read_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-700"
                                    >
                                        <Clock3 class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Last Read
                                        </p>

                                        <p
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            {{
                                                formatDateTime(
                                                    notice.last_read_at,
                                                )
                                            }}
                                        </p>

                                        <p
                                            class="mt-1 text-[10px] text-slate-400"
                                        >
                                            Opened
                                            {{ notice.read_count || 1 }}
                                            time{{
                                                Number(
                                                    notice.read_count || 1,
                                                ) === 1
                                                    ? ""
                                                    : "s"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        notice.requires_acknowledgement
                                    "
                                    class="flex items-start gap-3"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            notice.is_acknowledged
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-amber-100 text-amber-700'
                                        "
                                    >
                                        <CircleCheck class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Acknowledgement
                                        </p>

                                        <p
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            {{
                                                notice.is_acknowledged
                                                    ? formatDateTime(
                                                          notice.acknowledged_at,
                                                      )
                                                    : "Pending"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 p-5"
                        >
                            <h2 class="text-sm font-bold text-slate-900">
                                Notice Information
                            </h2>

                            <div class="mt-4 space-y-4">
                                <div
                                    class="flex items-start gap-3 rounded-xl bg-slate-50 p-4"
                                >
                                    <CalendarDays
                                        class="mt-0.5 h-5 w-5 shrink-0 text-slate-500"
                                    />

                                    <div>
                                        <p
                                            class="text-xs font-semibold text-slate-500"
                                        >
                                            Validity
                                        </p>

                                        <p
                                            class="mt-1 text-sm font-semibold text-slate-900"
                                        >
                                            {{
                                                notice.expires_at
                                                    ? `Until ${formatDate(
                                                          notice.expires_at,
                                                      )}`
                                                    : "No expiry date"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="flex items-start gap-3 rounded-xl bg-slate-50 p-4"
                                >
                                    <UserRound
                                        class="mt-0.5 h-5 w-5 shrink-0 text-slate-500"
                                    />

                                    <div>
                                        <p
                                            class="text-xs font-semibold text-slate-500"
                                        >
                                            Issued By
                                        </p>

                                        <p
                                            class="mt-1 text-sm font-semibold text-slate-900"
                                        >
                                            {{
                                                notice.created_by ||
                                                "Hostel Administration"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="notice.priority === 'urgent'"
                            class="rounded-2xl border border-red-200 bg-red-50 p-5"
                        >
                            <AlertTriangle
                                class="h-6 w-6 text-red-700"
                            />

                            <h3
                                class="mt-3 text-sm font-bold text-red-900"
                            >
                                Urgent Notice
                            </h3>

                            <p
                                class="mt-1 text-xs leading-5 text-red-700"
                            >
                                Please read the full notice carefully and
                                complete any required action immediately.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>
        </div>

        <!-- Acknowledgement confirmation -->
        <Modal
            :show="acknowledgeOpen"
            maxWidth="md"
            @close="acknowledgeOpen = false"
        >
            <form class="p-6" @submit.prevent="submitAcknowledgement">
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Acknowledge Notice
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Confirm that you have read and understood this
                            notice.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="acknowledgeOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-amber-600"
                    >
                        Notice
                    </p>

                    <p
                        class="mt-2 text-base font-bold text-amber-900"
                    >
                        {{ notice.title }}
                    </p>

                    <p
                        v-if="notice.summary"
                        class="mt-2 text-sm leading-6 text-amber-800"
                    >
                        {{ notice.summary }}
                    </p>
                </div>

                <label
                    class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4"
                >
                    <input
                        type="checkbox"
                        required
                        class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />

                    <span class="text-sm leading-6 text-slate-700">
                        I confirm that I have read and understood the notice
                        and any instructions included in it.
                    </span>
                </label>

                <p
                    class="mt-3 text-xs leading-5 text-slate-500"
                >
                    Your acknowledgement time and network address will be
                    recorded for administrative tracking.
                </p>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="acknowledgeOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="acknowledgementForm.processing"
                    >
                        {{
                            acknowledgementForm.processing
                                ? "Confirming..."
                                : "Confirm Acknowledgement"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>