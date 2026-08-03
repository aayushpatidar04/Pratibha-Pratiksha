<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: "—",
    },
    subtitle: {
        type: String,
        default: "",
    },
    tone: {
        type: String,
        default: "indigo",
    },
});

const toneClasses = {
    indigo: {
        background: "bg-indigo-50",
        icon: "bg-indigo-100 text-indigo-700",
    },
    emerald: {
        background: "bg-emerald-50",
        icon: "bg-emerald-100 text-emerald-700",
    },
    amber: {
        background: "bg-amber-50",
        icon: "bg-amber-100 text-amber-700",
    },
    blue: {
        background: "bg-blue-50",
        icon: "bg-blue-100 text-blue-700",
    },
    red: {
        background: "bg-red-50",
        icon: "bg-red-100 text-red-700",
    },
};
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    {{ title }}
                </p>

                <p class="mt-2 truncate text-2xl font-bold text-slate-900">
                    {{ value }}
                </p>

                <p
                    v-if="subtitle"
                    class="mt-1 truncate text-xs text-slate-500"
                >
                    {{ subtitle }}
                </p>
            </div>

            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                :class="
                    toneClasses[tone]?.icon ||
                    toneClasses.indigo.icon
                "
            >
                <slot name="icon" />
            </div>
        </div>
    </div>
</template>