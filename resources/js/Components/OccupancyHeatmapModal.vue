<script setup>
import Modal from "@/Components/Modal.vue";
import HeatmapGrid from "@/Components/HeatmapGrid.vue";
import { ref, watch } from "vue";
import { X } from "lucide-vue-next";

const props = defineProps({
    show: Boolean,
    buildingId: [Number, String],
    buildingName: String,
    roomType: String,
});
const emit = defineEmits(["close"]);

const loading = ref(false);
const floors = ref([]);

const load = async () => {
    if (!props.buildingId || !props.roomType) return;
    loading.value = true;
    try {
        const { data } = await window.axios.get(
            "/analytics/occupancy/heatmap",
            {
                params: {
                    building_id: props.buildingId,
                    room_type: props.roomType,
                },
            },
        );
        floors.value = data.floors;
    } finally {
        loading.value = false;
    }
};

watch(
    () => [props.show, props.buildingId, props.roomType],
    ([show]) => {
        if (show) load();
    },
);
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="lg">
        <div class="flex flex-col max-h-[80vh]">
            <div
                class="flex items-center justify-between px-5 py-4 bg-blue-600 text-white rounded-t-xl"
            >
                <h2 class="font-semibold">
                    Occupancy Details Of Unit: {{ roomType }} —
                    {{ buildingName }}
                </h2>
                <button @click="emit('close')"><X class="h-4 w-4" /></button>
            </div>

            <div class="px-5 py-4 overflow-y-auto">
                <HeatmapGrid :floors="floors" :loading="loading" />
            </div>
        </div>
    </Modal>
</template>