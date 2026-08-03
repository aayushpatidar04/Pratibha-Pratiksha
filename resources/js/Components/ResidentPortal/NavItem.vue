<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        required: true,
    },
    badge: {
        type: [String, Number],
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Link
        v-if="!disabled"
        :href="href"
        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
        :class="
            active
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
        "
    >
        <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition"
            :class="
                active
                    ? 'bg-white/15 text-white'
                    : 'bg-slate-100 text-slate-500 group-hover:bg-white'
            "
        >
            <slot name="icon" />
        </span>

        <span class="min-w-0 flex-1 truncate">
            {{ label }}
        </span>

        <span
            v-if="badge !== null && Number(badge) > 0"
            class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
            :class="
                active
                    ? 'bg-white text-indigo-700'
                    : 'bg-red-100 text-red-700'
            "
        >
            {{ badge }}
        </span>
    </Link>

    <div
        v-else
        class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300"
    >
        <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50"
        >
            <slot name="icon" />
        </span>

        <span class="min-w-0 flex-1 truncate">
            {{ label }}
        </span>

        <span class="text-[9px] uppercase tracking-wide">
            Soon
        </span>
    </div>
</template>