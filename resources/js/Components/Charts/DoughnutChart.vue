<script setup>
import { computed } from "vue";
import { Doughnut } from "vue-chartjs";
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement } from "chart.js";

ChartJS.register(Title, Tooltip, Legend, ArcElement);

const props = defineProps({
    labels: { type: Array, required: true },
    values: { type: Array, required: true },
    colors: { type: Array, required: true },
    size: { type: Number, default: 128 },
    centerLabel: { type: String, default: "" },
    cutout: { type: String, default: "70%" },
});

const data = computed(() => ({
    labels: props.labels,
    datasets: [
        { data: props.values, backgroundColor: props.colors, borderWidth: 0 },
    ],
}));

const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: props.cutout,
    plugins: { legend: { display: false }, tooltip: { enabled: true } },
}));
</script>

<template>
    <div class="relative" :style="{ height: size + 'px', width: size + 'px' }">
        <Doughnut :data="data" :options="options" />
        <div
            v-if="centerLabel"
            class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900 pointer-events-none"
        >
            {{ centerLabel }}
        </div>
    </div>
</template>
