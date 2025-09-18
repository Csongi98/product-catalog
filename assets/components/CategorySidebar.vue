<script setup>
import { ref, onMounted } from "vue";
import PanelMenu from "primevue/panelmenu";
import Skeleton from "primevue/skeleton";
import Button from "primevue/button";

const props = defineProps({
    path: { type: String, default: "" },
});

const emit = defineEmits(["select", "path-change"]);

const model = ref([]);
const loading = ref(true);
const error = ref("");

const fileIcon = "pi pi-file";
const folderIcon = "pi pi-folder";

async function load() {
    loading.value = true;
    error.value = "";
    try {
        const url = props.path
            ? `/api/categories/branch?path=${encodeURIComponent(props.path)}`
            : `/api/categories/branch`;
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        const branch =
            typeof data.branchName === "string"
                ? data.branchName
                : props.path || "";

        const segs = branch
            .split(">")
            .map((s) => s.trim())
            .filter(Boolean);
        const lvl1 = segs[0] || "Kategóriák";
        const lvl2 = segs[1] || "Alkategória";

        const leaves = Array.isArray(data.children) ? data.children : [];

        model.value = [
            {
                key: `lvl1:${lvl1}`,
                label: lvl1,
                icon: folderIcon,
                items: [
                    {
                        key: `lvl2:${lvl1}>${lvl2}`,
                        label: lvl2,
                        icon: folderIcon,
                        // levél elemek a 3. szinten
                        items: leaves.map((c) => ({
                            key: `leaf:${c.id}`,
                            label: c.name,
                            icon: fileIcon,
                            command: () => {
                                emit("select", c.id);
                                emit(
                                    "path-change",
                                    `${lvl1}>${lvl2}>${c.name}`
                                );
                            },
                        })),
                    },
                ],
            },
        ];
    } catch (e) {
        console.error(e);
        error.value = "Nem sikerült betölteni a kategóriákat.";
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div class="rounded-2xl border shadow-sm bg-white p-3">
        <div class="text-lg font-semibold mb-3">Kategóriák</div>

        <div v-if="error" class="text-sm text-red-600 flex items-center gap-3">
            {{ error }}
            <Button label="Próbáld újra" text @click="load" />
        </div>

        <div v-else-if="loading" class="space-y-2">
            <Skeleton height="2rem" />
            <Skeleton height="2rem" />
            <Skeleton height="2rem" />
        </div>

        <PanelMenu v-else :model="model" multiple class="w-full" />
    </div>
</template>
