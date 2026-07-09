<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    floors: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    // When true, each room badge also shows its room_type (used for "Full building
    // detail" where rooms of several bed types are mixed together on one floor).
    showRoomType: { type: Boolean, default: false },
});

const activeFilter = ref("all");

const statusMeta = {
    occupied: { label: "Occupied", color: "bg-red-500", text: "text-white" },
    partially_filled: {
        label: "Partially Filled",
        color: "bg-amber-400",
        text: "text-white",
    },
    vacant: { label: "Vacant", color: "bg-green-500", text: "text-white" },
    no_capacity: {
        label: "No Capacity",
        color: "bg-gray-200",
        text: "text-gray-500",
    },
};

const filterOptions = [
    { key: "all", label: "All" },
    { key: "occupied", label: "Occupied" },
    { key: "partially_filled", label: "Partially Filled" },
    { key: "vacant", label: "Vacant" },
    { key: "no_capacity", label: "No Capacity" },
];

const counts = computed(() => {
    const all = props.floors.flatMap((f) => f.rooms);
    return {
        all: all.length,
        occupied: all.filter((r) => r.status === "occupied").length,
        partially_filled: all.filter((r) => r.status === "partially_filled")
            .length,
        vacant: all.filter((r) => r.status === "vacant").length,
        no_capacity: all.filter((r) => r.status === "no_capacity").length,
    };
});

const visibleFloors = computed(() => {
    if (activeFilter.value === "all") return props.floors;
    return props.floors
        .map((f) => ({
            ...f,
            rooms: f.rooms.filter((r) => r.status === activeFilter.value),
        }))
        .filter((f) => f.rooms.length);
});

defineExpose({ statusMeta });
</script>

<template>
    <div class="space-y-4">
        <div class="flex gap-2 flex-wrap">
            <button
                v-for="f in filterOptions"
                :key="f.key"
                @click="activeFilter = f.key"
                class="px-3 py-1.5 text-xs rounded-lg border flex items-center gap-1.5"
                :class="
                    activeFilter === f.key
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'border-gray-200 text-gray-600'
                "
            >
                <span
                    v-if="f.key !== 'all'"
                    class="h-2 w-2 rounded-full"
                    :class="statusMeta[f.key].color"
                />
                {{ f.label }} ({{ counts[f.key] }})
            </button>
        </div>

        <p v-if="loading" class="text-sm text-gray-400 text-center py-8">
            Loading...
        </p>
        <template v-else>
            <div v-for="floor in visibleFloors" :key="floor.floor_number">
                <p class="text-xs font-semibold text-gray-500 mb-2">
                    Floor {{ floor.floor_number }}:
                </p>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="room in floor.rooms"
                        :key="room.id"
                        class="px-3 py-1.5 rounded-full text-xs font-medium flex flex-col items-center leading-tight min-w-[64px]"
                        :class="[
                            statusMeta[room.status].color,
                            statusMeta[room.status].text,
                        ]"
                    >
                        {{ room.room_number }}
                        <span
                            v-if="showRoomType"
                            class="text-[9px] opacity-80"
                            >{{ room.room_type }}</span
                        >
                        <span class="text-[10px] opacity-90"
                            >{{ room.occupied_beds }}/{{ room.capacity }}</span
                        >
                    </span>
                </div>
            </div>
            <p
                v-if="!visibleFloors.length"
                class="text-sm text-gray-400 text-center py-8"
            >
                No rooms match this filter
            </p>
        </template>
    </div>
</template>
