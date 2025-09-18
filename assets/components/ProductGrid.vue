<script setup>
import { ref, watch, onMounted } from "vue";

const props = defineProps({
    categoryId: { type: [Number, String], default: null },
});

const items = ref([]);
const page = ref(1);
const perPage = ref(20);
const total = ref(0);
const loading = ref(false);
const error = ref("");
const search = ref("");

function toFt(cents) {
    if (cents == null) return "";
    return (
        new Intl.NumberFormat("hu-HU").format(Math.round(cents / 100)) + " Ft"
    );
}

async function load() {
    loading.value = true;
    error.value = "";
    try {
        const params = new URLSearchParams({
            page: String(page.value),
            perPage: String(perPage.value),
        });
        if (props.categoryId)
            params.set("categoryId", String(props.categoryId));
        if (search.value.trim()) params.set("search", search.value.trim());

        const res = await fetch(`/api/products?${params.toString()}`);
        if (!res.ok) throw new Error(`API ${res.status}`);
        const data = await res.json();
        items.value = data.items;
        total.value = data.total;
    } catch (e) {
        console.error(e);
        error.value = "Nem sikerült betölteni a termékeket.";
    } finally {
        loading.value = false;
    }
}

watch([() => props.categoryId, page, perPage, search], load);
onMounted(load);

const lastPage = () => Math.max(1, Math.ceil(total.value / perPage.value));
</script>

<template>
    <div class="space-y-4">
        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>

        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Keresés név / leírás szerint…"
                class="w-full md:max-w-sm rounded-xl border px-3 py-2"
            />
            <div class="ml-auto flex items-center gap-2">
                <label class="text-sm text-gray-500">Oldalanként</label>
                <select
                    v-model.number="perPage"
                    class="rounded-xl border px-2 py-1"
                >
                    <option :value="12">12</option>
                    <option :value="20">20</option>
                    <option :value="36">36</option>
                    <option :value="48">48</option>
                </select>
            </div>
        </div>

        <div v-if="loading" class="text-gray-500">Betöltés…</div>
        <div
            v-else
            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5"
        >
            <a
                v-for="p in items"
                :key="p.id"
                :href="`/product/${p.id}`"
                class="block bg-white rounded-2xl border shadow-sm hover:shadow transition"
            >
                <div
                    class="aspect-[4/3] w-full overflow-hidden rounded-t-2xl bg-gray-100"
                >
                    <img
                        v-if="p.imageUrl"
                        :src="p.imageUrl"
                        :alt="p.name"
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="p-4">
                    <div class="font-semibold line-clamp-2 min-h-[3rem]">
                        {{ p.name }}
                    </div>
                    <div class="mt-2 text-gray-700 font-medium">
                        {{ toFt(p.price) }}
                    </div>
                    <p class="mt-2 text-sm text-gray-500 line-clamp-2">
                        {{ p.description }}
                    </p>
                </div>
            </a>
        </div>

        <div v-if="lastPage() > 1" class="flex flex-wrap gap-2 justify-center">
            <button
                class="px-3 py-1 rounded border"
                :disabled="page <= 1"
                @click="page = Math.max(1, page - 1)"
            >
                Előző
            </button>
            <button
                v-for="i in lastPage()"
                :key="i"
                class="px-3 py-1 rounded border"
                :class="
                    i === page
                        ? 'bg-gray-900 text-white'
                        : 'bg-white hover:bg-gray-100'
                "
                @click="page = i"
            >
                {{ i }}
            </button>
            <button
                class="px-3 py-1 rounded border"
                :disabled="page >= lastPage()"
                @click="page = Math.min(lastPage(), page + 1)"
            >
                Következő
            </button>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
