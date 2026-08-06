<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    href: {
        type: String,
        default: "#",
    },

    label: {
        type: String,
        required: true,
    },

    active: {
        type: Boolean,
        default: false,
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    badge: {
        type: [Number, String],
        default: null,
    },

    badgeTone: {
        type: String,
        default: "indigo",
    },
});

const badgeClasses = {
    indigo: "bg-indigo-100 text-indigo-700",

    blue: "bg-blue-100 text-blue-700",

    amber: "bg-amber-100 text-amber-700",

    red: "bg-red-100 text-red-700",

    green: "bg-emerald-100 text-emerald-700",

    purple: "bg-purple-100 text-purple-700",
};
</script>

<template>
    <component
        :is="disabled ? 'div' : Link"
        :href="disabled ? undefined : href"
        class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
        :class="[
            active
                ? 'bg-indigo-50 text-indigo-700'
                : disabled
                  ? 'cursor-not-allowed text-slate-300'
                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
        ]"
    >
        <span
            class="shrink-0 transition"
            :class="
                active
                    ? 'text-indigo-600'
                    : 'text-slate-400 group-hover:text-slate-600'
            "
        >
            <slot name="icon" />
        </span>

        <span class="min-w-0 flex-1 truncate">
            {{ label }}
        </span>

        <span
            v-if="badge !== null && badge !== undefined && Number(badge) > 0"
            class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
            :class="badgeClasses[badgeTone] || badgeClasses.indigo"
        >
            {{ Number(badge) > 99 ? "99+" : badge }}
        </span>

        <span v-else-if="badge === 'active'" class="relative flex h-3 w-3">
            <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"
            ></span>

            <span
                class="relative inline-flex h-3 w-3 rounded-full bg-red-500"
            ></span>
        </span>
    </component>
</template>