<script setup>
defineProps({ color: { type: String, default: 'gray' } });
const colorMap = {
    gray: 'bg-gray-100 text-gray-800',
    green: 'bg-green-100 text-green-800',
    red: 'bg-red-100 text-red-800',
    amber: 'bg-amber-100 text-amber-800',
    blue: 'bg-blue-100 text-blue-800',
    pink: 'bg-pink-100 text-pink-800',
    purple: 'bg-purple-100 text-purple-800',
};
</script>
<template>
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
        :class="colorMap[color] || colorMap.gray">
        <slot />
    </span>
</template>