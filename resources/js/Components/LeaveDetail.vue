<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    full: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="rounded-xl border border-gray-100 bg-gray-50 p-3"
        :class="full ? 'sm:col-span-2' : ''"
    >
        <p
            class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
        >
            {{ label }}
        </p>

        <div class="mt-1 text-sm text-gray-900 whitespace-pre-wrap break-words">
            <slot />
        </div>
    </div>
</template>
