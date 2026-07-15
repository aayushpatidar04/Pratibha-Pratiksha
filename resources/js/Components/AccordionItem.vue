<script setup>
import { ChevronDown } from "lucide-vue-next";
import { ref, watch } from "vue";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:open"]);

const isOpen = ref(props.open);

watch(
    () => props.open,
    (value) => {
        isOpen.value = value;
    },
);

const toggle = () => {
    isOpen.value = !isOpen.value;
    emit("update:open", isOpen.value);
};
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-gray-200 bg-white transition-shadow"
        :class="{ 'shadow-sm': isOpen }"
    >
        <button
            type="button"
            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50"
            @click="toggle"
        >
            <div class="flex min-w-0 items-center gap-2">
                <slot name="icon" />

                <span class="truncate text-sm font-semibold text-gray-900">
                    {{ title }}
                </span>
            </div>

            <ChevronDown
                class="h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
            />
        </button>

        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[1000px] opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="max-h-[1000px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-show="isOpen" class="overflow-hidden">
                <div class="border-t border-gray-100 p-4">
                    <slot />
                </div>
            </div>
        </Transition>
    </div>
</template>