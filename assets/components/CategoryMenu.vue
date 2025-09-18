<script setup>
import { ref, onMounted } from "vue";
const emit = defineEmits(["select"]);

const path = "Autó, motor>Személygépkocsi abroncs";
const loading = ref(true);
const error = ref("");
const categories = ref([]);

async function load() {
    loading.value = true;
    try {
        const url = `/api/categories/branch?path=${encodeURIComponent(path)}`;
        const res = await fetch(url);
        const data = await res.json();
        categories.value = data.children ?? [];
    } catch (e) {
        error.value = "Nem sikerült betölteni a kategóriákat.";
    } finally {
        loading.value = false;
    }
}

function selectCategory(id) {
    emit("select", id);
}

onMounted(load);
</script>

<template>
    <div class="bg-white rounded-2xl border shadow-sm">
        <details open class="p-4">
            <summary class="cursor-pointer text-lg font-semibold">
                Autó, motor → Személygépkocsi abroncs
            </summary>

            <div class="mt-4">
                <div v-if="loading" class="text-sm text-gray-500">
                    Betöltés…
                </div>
                <div v-else-if="error" class="text-sm text-red-600">
                    {{ error }}
                </div>
                <ul v-else class="grid grid-cols-2 md:grid-cols-1 gap-2">
                    <li v-for="c in categories" :key="c.id">
                        <button
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 border"
                            @click="selectCategory(c.id)"
                        >
                            {{ c.name }}
                        </button>
                    </li>
                </ul>
            </div>
        </details>
    </div>
</template>
