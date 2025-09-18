<script setup>
import { ref, watch, onMounted } from "vue";
import Card from "primevue/card";
import DataView from "primevue/dataview";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Rating from "primevue/rating";
import Skeleton from "primevue/skeleton";
import { RouterLink } from "vue-router";

const props = defineProps({
    categoryId: { type: [Number, String], default: null },
});

const items = ref([]);
const total = ref(0);
const loading = ref(false);
const error = ref("");

const rows = ref(20);
const page = ref(0);
const q = ref("");
const qDebounced = ref("");
const season = ref("");
const diameter = ref(null);

const seasonOptions = [
    { label: "Összes évszak", value: "" },
    { label: "Nyári", value: "nyári" },
    { label: "Téli", value: "téli" },
    { label: "4 évszakos", value: "4 évszakos" },
];

const diameterOptions = [
    { label: "Összes átmérő", value: "" },
    { label: '13"', value: 13 },
    { label: '14"', value: 14 },
    { label: '15"', value: 15 },
    { label: '16"', value: 16 },
    { label: '17"', value: 17 },
    { label: '18"', value: 18 },
    { label: '19"', value: 19 },
    { label: '20"', value: 20 },
];

const sortKey = ref("relevance");
function buildSortParams(key) {
    switch (key) {
        case "name_asc":
            return { sort: "name", dir: "asc" };
        case "name_desc":
            return { sort: "name", dir: "desc" };
        case "price_asc":
            return { sort: "price", dir: "asc" };
        case "price_desc":
            return { sort: "price", dir: "desc" };
        default:
            return null;
    }
}

let t = null;
watch(q, (v) => {
    clearTimeout(t);
    t = setTimeout(() => {
        qDebounced.value = v.trim();
        page.value = 0;
        load();
    }, 350);
});

function toFt(cents) {
    if (cents == null) return "";
    return (
        new Intl.NumberFormat("hu-HU").format(Math.round(cents / 100)) + " Ft"
    );
}

function applyClientSort(arr) {
    const k = sortKey.value;
    const a = [...arr];
    switch (k) {
        case "name_asc":
            return a.sort((x, y) =>
                (x.name ?? "").localeCompare(y.name ?? "", "hu")
            );
        case "name_desc":
            return a.sort((x, y) =>
                (y.name ?? "").localeCompare(x.name ?? "", "hu")
            );
        case "price_asc":
            return a.sort((x, y) => (x.price ?? 0) - (y.price ?? 0));
        case "price_desc":
            return a.sort((x, y) => (y.price ?? 0) - (x.price ?? 0));
        default:
            return a;
    }
}

async function load() {
    loading.value = true;
    error.value = "";
    try {
        const params = new URLSearchParams({
            page: String(page.value + 1),
            perPage: String(rows.value),
        });
        if (props.categoryId)
            params.set("categoryId", String(props.categoryId));
        if (qDebounced.value) params.set("search", qDebounced.value);

        if (season.value) params.set("season", season.value);
        if (diameter.value) params.set("diameter", String(diameter.value));

        const s = buildSortParams(sortKey.value);
        if (s) {
            params.set("sort", s.sort);
            params.set("dir", s.dir);
        }

        const res = await fetch(`/api/products?${params.toString()}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        let pageItems = data.items ?? [];
        total.value = data.total ?? 0;

        if (s) pageItems = applyClientSort(pageItems);

        items.value = pageItems;
    } catch (e) {
        console.error(e);
        error.value = "Nem sikerült betölteni a termékeket.";
    } finally {
        loading.value = false;
    }
}

watch([() => props.categoryId, rows, page, sortKey, season, diameter], load, {
    immediate: true,
});
watch(rows, () => {
    page.value = 0;
});
onMounted(load);

const sortOptions = [
    { label: "Relevancia", value: "relevance" },
    { label: "Név (A→Z)", value: "name_asc" },
    { label: "Név (Z→A)", value: "name_desc" },
    { label: "Ár (növekvő)", value: "price_asc" },
    { label: "Ár (csökkenő)", value: "price_desc" },
];

const paginatorTemplate =
    "FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink RowsPerPageDropdown";
const rowsPerPageOptions = [12, 20, 36, 48];
</script>

<template>
    <Card class="rounded-2xl overflow-hidden">
        <template #title>
            <div class="flex flex-wrap items-center gap-3 w-full">
                <span class="p-input-icon-left">
                    <i class="pi pi-search pr-2" />
                    <InputText
                        v-model="q"
                        placeholder="Keresés a termékekben…"
                        class="w-64"
                    />
                </span>

                <Dropdown
                    v-model="season"
                    :options="seasonOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Évszak"
                    class="w-48"
                />

                <Dropdown
                    v-model="diameter"
                    :options="diameterOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Átmérő"
                    class="w-40"
                />

                <!-- rendezés -->
                <Dropdown
                    v-model="sortKey"
                    :options="sortOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-56"
                />

                <div class="ml-auto flex items-center gap-2">
                    <span class="text-sm text-slate-500">Oldalanként</span>
                    <Dropdown
                        v-model="rows"
                        :options="rowsPerPageOptions"
                        class="w-24"
                    />
                    <Button icon="pi pi-refresh" text @click="load" />
                </div>
            </div>
        </template>

        <template #content>
            <div
                v-if="error"
                class="text-sm text-red-600 flex items-center gap-3"
            >
                {{ error }}
                <Button label="Próbáld újra" text @click="load" />
            </div>

            <!-- Skeleton -->
            <div v-else-if="loading" class="flex flex-col gap-3">
                <div
                    v-for="i in 6"
                    :key="i"
                    class="bg-white rounded-2xl border p-3"
                >
                    <div class="flex gap-3">
                        <Skeleton
                            width="10rem"
                            height="7.5rem"
                            class="rounded-xl"
                        />
                        <div class="flex-1">
                            <Skeleton width="60%" class="mb-2" />
                            <Skeleton width="30%" class="mb-2" />
                            <Skeleton width="95%" height="1rem" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Üres -->
            <div v-else-if="!items.length" class="text-sm text-slate-500">
                Nincs találat.
            </div>

            <!-- LISTA NÉZET -->
            <DataView
                v-else
                :value="items"
                layout="list"
                :paginator="true"
                :lazy="true"
                :rows="rows"
                :first="page * rows"
                :totalRecords="total"
                :paginatorTemplate="paginatorTemplate"
                :rowsPerPageOptions="rowsPerPageOptions"
                currentPageReportTemplate="Oldal {currentPage}/{totalPages} — Összesen {totalRecords}"
                @page="
                    (e) => {
                        page = Math.floor(e.first / e.rows);
                        rows = e.rows;
                    }
                "
            >
                <template #list="slotProps">
                    <div class="flex flex-col gap-3 w-full">
                        <div
                            v-for="p in slotProps.items"
                            :key="p.id"
                            class="bg-white rounded-2xl border p-3 hover:shadow-sm transition"
                        >
                            <RouterLink
                                :to="{ name: 'product', params: { id: p.id } }"
                                class="flex gap-3"
                            >
                                <div
                                    class="w-40 aspect-[4/3] rounded-xl overflow-hidden bg-gray-100 shrink-0"
                                >
                                    <img
                                        v-if="p.imageUrl"
                                        :src="p.imageUrl"
                                        :alt="p.name"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold">
                                        {{ p.name }}
                                    </div>
                                    <p
                                        class="text-sm text-gray-500 line-clamp-2"
                                    >
                                        {{ p.description }}
                                    </p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <Rating
                                            :modelValue="p.rating ?? 0"
                                            :cancel="false"
                                            readonly
                                        />
                                        <Tag
                                            v-if="p.stock === 0"
                                            value="Elfogyott"
                                            severity="danger"
                                        />
                                        <Tag
                                            v-else-if="p.stock < 5"
                                            value="Kevés"
                                            severity="warn"
                                        />
                                    </div>
                                </div>
                                <div
                                    class="text-right font-semibold text-gray-800 self-center"
                                >
                                    {{ toFt(p.price) }}
                                </div>
                            </RouterLink>
                        </div>
                    </div>
                </template>
            </DataView>
        </template>
    </Card>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
