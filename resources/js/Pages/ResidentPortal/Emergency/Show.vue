<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowLeft,
    Brain,
    CheckCircle2,
    CircleAlert,
    Clock3,
    Flame,
    HeartPulse,
    HelpCircle,
    MapPin,
    ShieldAlert,
    Siren,
    UserRoundCheck,
    UserRoundX,
    Utensils,
    Waves,
} from "lucide-vue-next";
import { computed } from "vue";

const props = defineProps({
    alert: {
        type: Object,
        required: true,
    },
});

const categories = [
    {
        value: "medical",
        label: "Medical Emergency",
        icon: HeartPulse,
    },
    {
        value: "fire",
        label: "Fire",
        icon: Flame,
    },
    {
        value: "theft",
        label: "Theft",
        icon: ShieldAlert,
    },
    {
        value: "stuck_in_lift",
        label: "Stuck in Lift",
        icon: AlertTriangle,
    },
    {
        value: "need_food",
        label: "Need Food",
        icon: Utensils,
    },
    {
        value: "disaster",
        label: "Disaster",
        icon: Waves,
    },
    {
        value: "domestic_violence",
        label: "Domestic Violence",
        icon: UserRoundX,
    },
    {
        value: "threat",
        label: "Threat",
        icon: ShieldAlert,
    },
    {
        value: "violence",
        label: "Violence",
        icon: AlertTriangle,
    },
    {
        value: "suicidal",
        label: "Self-Harm Emergency",
        icon: Brain,
    },
    {
        value: "mental_depression",
        label: "Mental Health Emergency",
        icon: Brain,
    },
    {
        value: "others",
        label: "Other Emergency",
        icon: HelpCircle,
    },
];

const statusClasses = {
    active: "border-red-200 bg-red-50 text-red-700",
    escalated: "border-amber-200 bg-amber-50 text-amber-700",
    resolved: "border-emerald-200 bg-emerald-50 text-emerald-700",
};

const categoryIcon = computed(() => {
    return (
        categories.find((category) => category.value === props.alert.category)
            ?.icon || Siren
    );
});

const statusIcon = computed(() => {
    if (props.alert.status === "resolved") {
        return CheckCircle2;
    }

    if (props.alert.status === "escalated") {
        return AlertTriangle;
    }

    return Siren;
});

const labelize = (value) => {
    if (!value) {
        return "Submitted";
    }

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const updateIcon = (status) => {
    if (status === "resolved") {
        return CheckCircle2;
    }

    if (status === "escalated") {
        return AlertTriangle;
    }

    return Siren;
};

const updateIconClass = (status) => {
    if (status === "resolved") {
        return "bg-emerald-100 text-emerald-700";
    }

    if (status === "escalated") {
        return "bg-amber-100 text-amber-700";
    }

    return "bg-red-100 text-red-700";
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

const locationLabel = computed(() => {
    if (props.alert.location) {
        return props.alert.location;
    }

    const parts = [];

    if (props.alert.building?.name) {
        parts.push(props.alert.building.name);
    }

    if (props.alert.room?.room_number) {
        parts.push(`Room ${props.alert.room.room_number}`);
    }

    return parts.length ? parts.join(" · ") : "Location not provided";
});
</script>

<template>
    <Head :title="`Emergency Alert #${alert.id}`" />

    <ResidentLayout title="Emergency Alert">
        <div class="space-y-6">
            <!-- Back -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <Link
                    :href="route('resident.emergency.index')"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Emergency Alerts
                </Link>
            </section>

            <!-- Main alert card -->
            <section
                class="overflow-hidden rounded-3xl border shadow-sm"
                :class="
                    alert.status === 'active'
                        ? 'border-red-300'
                        : alert.status === 'escalated'
                          ? 'border-amber-300'
                          : 'border-emerald-300'
                "
            >
                <div
                    class="px-6 py-7 text-white"
                    :class="
                        alert.status === 'active'
                            ? 'bg-gradient-to-r from-red-700 to-rose-600'
                            : alert.status === 'escalated'
                              ? 'bg-gradient-to-r from-amber-700 to-orange-600'
                              : 'bg-gradient-to-r from-emerald-700 to-green-600'
                    "
                >
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15"
                                >
                                    <component
                                        :is="categoryIcon"
                                        class="h-6 w-6"
                                    />
                                </div>

                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-[0.18em] text-white/75"
                                    >
                                        Emergency Alert
                                    </p>

                                    <h1 class="mt-1 text-2xl font-bold">
                                        {{ alert.category_label }}
                                    </h1>
                                </div>
                            </div>

                            <p class="mt-5 text-sm text-white/80">
                                Alert #{{ alert.id }} · Raised
                                {{ formatDateTime(alert.created_at) }}
                            </p>
                        </div>

                        <span
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-white/20 bg-white/15 px-4 py-2 text-xs font-bold"
                        >
                            <component :is="statusIcon" class="h-4 w-4" />

                            {{ alert.status_label }}
                        </span>
                    </div>
                </div>

                <!-- Summary -->
                <div
                    class="grid grid-cols-1 gap-4 border-b border-slate-100 bg-white p-6 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Location
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ locationLabel }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Assigned To
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ alert.assigned_to || "Not assigned yet" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Acknowledged By
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{
                                alert.acknowledged_by || "Not acknowledged yet"
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
                            {{ formatDateTime(alert.updated_at) }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 bg-white p-6 lg:grid-cols-3">
                    <!-- Details -->
                    <div class="space-y-6 lg:col-span-2">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Emergency Description
                            </h2>

                            <div
                                class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                            >
                                <p
                                    class="whitespace-pre-line text-sm leading-7 text-slate-700"
                                >
                                    {{
                                        alert.description ||
                                        "No additional description was provided."
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Active -->
                        <div
                            v-if="alert.status === 'active'"
                            class="rounded-2xl border border-red-200 bg-red-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <Siren
                                    class="mt-0.5 h-5 w-5 shrink-0 text-red-700"
                                />

                                <div>
                                    <h2 class="text-sm font-bold text-red-900">
                                        Assistance is being arranged
                                    </h2>

                                    <p
                                        class="mt-1 text-xs leading-5 text-red-700"
                                    >
                                        Your emergency alert is active. Keep
                                        your phone available and remain at the
                                        location if it is safe to do so.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Escalated -->
                        <div
                            v-else-if="alert.status === 'escalated'"
                            class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <AlertTriangle
                                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                                />

                                <div>
                                    <h2
                                        class="text-sm font-bold text-amber-900"
                                    >
                                        Alert Escalated
                                    </h2>

                                    <p
                                        class="mt-1 text-xs leading-5 text-amber-700"
                                    >
                                        This emergency has been escalated for
                                        additional or higher-level assistance.
                                    </p>

                                    <p
                                        v-if="alert.escalation_notes"
                                        class="mt-3 whitespace-pre-line text-sm leading-6 text-amber-800"
                                    >
                                        {{ alert.escalation_notes }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Resolved -->
                        <div
                            v-else
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <CheckCircle2
                                    class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"
                                />

                                <div>
                                    <h2
                                        class="text-sm font-bold text-emerald-900"
                                    >
                                        Emergency Resolved
                                    </h2>

                                    <p
                                        class="mt-1 text-xs leading-5 text-emerald-700"
                                    >
                                        This emergency alert has been marked as
                                        resolved.
                                    </p>

                                    <p
                                        v-if="alert.resolution_notes"
                                        class="mt-3 whitespace-pre-line text-sm leading-6 text-emerald-800"
                                    >
                                        {{ alert.resolution_notes }}
                                    </p>

                                    <p
                                        v-if="alert.resolved_at"
                                        class="mt-3 text-xs text-emerald-600"
                                    >
                                        Resolved on
                                        {{ formatDateTime(alert.resolved_at) }}
                                        <template v-if="alert.resolved_by">
                                            by
                                            {{ alert.resolved_by }}
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Emergency Timeline
                            </h2>

                            <p class="mt-1 text-xs text-slate-400">
                                Complete history of staff actions and status
                                changes.
                            </p>

                            <div
                                v-if="alert.updates?.length"
                                class="mt-5 space-y-5 rounded-2xl border border-slate-200 p-5"
                            >
                                <div
                                    v-for="(update, index) in alert.updates"
                                    :key="update.id"
                                    class="relative flex items-start gap-3"
                                >
                                    <div
                                        v-if="
                                            index !== alert.updates.length - 1
                                        "
                                        class="absolute left-[17px] top-9 h-[calc(100%+8px)] w-px bg-slate-200"
                                    ></div>

                                    <div
                                        class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            updateIconClass(update.new_status)
                                        "
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
                                                Emergency Alert Raised
                                            </template>

                                            <template v-else>
                                                {{
                                                    labelize(update.old_status)
                                                }}
                                                →
                                                {{
                                                    labelize(update.new_status)
                                                }}
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
                                                {{ update.updated_by }}
                                            </span>

                                            <span
                                                v-else-if="
                                                    update.updated_by_resident
                                                "
                                            >
                                                · Resident submission
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="mt-4 rounded-2xl border border-dashed border-slate-300 p-6 text-center text-xs text-slate-400"
                            >
                                No timeline updates available.
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h2 class="text-sm font-bold text-slate-900">
                                Response Details
                            </h2>

                            <div class="mt-5 space-y-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700"
                                    >
                                        <Siren class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Alert Raised
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{
                                                formatDateTime(alert.created_at)
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            alert.acknowledged_at
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        <UserRoundCheck class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Acknowledgement
                                        </p>

                                        <template v-if="alert.acknowledged_at">
                                            <p
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                {{
                                                    alert.acknowledged_by ||
                                                    "Hostel staff"
                                                }}
                                            </p>

                                            <p
                                                class="mt-1 text-[10px] text-slate-400"
                                            >
                                                {{
                                                    formatDateTime(
                                                        alert.acknowledged_at,
                                                    )
                                                }}
                                            </p>
                                        </template>

                                        <p
                                            v-else
                                            class="mt-1 text-xs text-slate-400"
                                        >
                                            Waiting for staff acknowledgement
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            alert.status === 'resolved'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : alert.status === 'escalated'
                                                  ? 'bg-amber-100 text-amber-700'
                                                  : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        <component
                                            :is="statusIcon"
                                            class="h-4 w-4"
                                        />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Current Status
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ alert.status_label }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h2 class="text-sm font-bold text-slate-900">
                                Location Details
                            </h2>

                            <div
                                class="mt-4 flex items-start gap-3 rounded-xl bg-slate-50 p-4"
                            >
                                <MapPin
                                    class="mt-0.5 h-5 w-5 shrink-0 text-slate-500"
                                />

                                <div>
                                    <p
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ locationLabel }}
                                    </p>

                                    <p
                                        v-if="alert.building || alert.room"
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        Saved hostel location:
                                        {{
                                            [
                                                alert.building?.name,
                                                alert.room?.room_number
                                                    ? `Room ${alert.room.room_number}`
                                                    : null,
                                            ]
                                                .filter(Boolean)
                                                .join(" · ")
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="alert.assigned_to"
                            class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
                        >
                            <h2 class="text-sm font-bold text-blue-900">
                                Assigned Responder
                            </h2>

                            <p class="mt-2 text-sm font-semibold text-blue-800">
                                {{ alert.assigned_to }}
                            </p>

                            <p class="mt-1 text-xs text-blue-600">
                                This person has been assigned to respond to the
                                emergency.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>

            <!-- Important note -->
            <section
                v-if="alert.is_active"
                class="rounded-2xl border border-red-200 bg-red-50 p-5"
            >
                <div class="flex items-start gap-3">
                    <CircleAlert class="mt-0.5 h-5 w-5 shrink-0 text-red-700" />

                    <div>
                        <h3 class="text-sm font-bold text-red-900">
                            Keep communication available
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-red-700">
                            Keep your phone reachable and follow any
                            instructions provided by hostel staff. Move away
                            from danger when it is safe to do so.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </ResidentLayout>
</template>
