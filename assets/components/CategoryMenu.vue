<template>
    <!-- 
      Kategóriaválasztó UI:
      - Betöltéskor Skeleton
      - Később TreeSelect hierarchikus kategóriákkal
    -->
    <div class="w-full">
        <div class="text-lg font-semibold mb-2">Kategóriák</div>

        <Skeleton v-if="loading" height="3rem" class="mb-2" />

        <TreeSelect
            v-else
            v-model="value"
            :options="nodes"
            placeholder="Válassz kategóriát..."
            selectionMode="single"
            class="w-full"
            :showClear="true"
            @change="onChange"
            @node-expand="onNodeExpand"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import TreeSelect from "primevue/treeselect";
import Skeleton from "primevue/skeleton";

const emit = defineEmits(["select", "path-change"]);

const value = ref(null);
const nodes = ref([]);
const loading = ref(true);

/*--- Segédfüggvény: van-e gyerek ---
Input: kategória objektum
Output: boolean
Ellenőrzi többféle mező alapján (hasChildren, children_count, children) */
function hasKids(c) {
    if (typeof c.hasChildren === "boolean") return c.hasChildren;
    if (typeof c.children_count === "number") return c.children_count > 0;
    if (Array.isArray(c.children)) return c.children.length > 0;
    return false;
}

/* --- Kategória -> TreeSelect node átalakító ---
Input: kategória objektum, parentPath (string)
Output: TreeSelect kompatibilis node objektum
Hozzáadja a labelt, id-t, path-ot és jelzi ha leaf vagy bővíthető */
function toNode(c, parentPath = "") {
    const path = parentPath ? `${parentPath}>${c.name}` : c.name;
    const node = {
        key: String(c.id),
        label: c.name,
        data: { id: c.id, path },
    };

    if (hasKids(c)) {
        node.children = [];
        node.leaf = false;
    } else {
        node.children = null;
        node.leaf = true;
    }

    return node;
}

/* --- Gyökér kategóriák betöltése API-ból ---
Input: nincs
Output: feltölti a nodes.value-t gyerek kategóriákkal */
async function loadRoot() {
    loading.value = true;
    try {
        const res = await fetch("/api/categories/branch");
        const data = await res.json();
        nodes.value = (data.children ?? []).map((c) => toNode(c, ""));
    } finally {
        loading.value = false;
    }
}

/* --- Node kinyitásakor gyerekek betöltése API-ból ---
Input: event.node (TreeSelect node)
Output: node.children feltöltése */
async function onNodeExpand(event) {
    const node = event.node;
    if (node.children && node.children.length === 0 && !node.leaf) {
        const res = await fetch(
            `/api/categories/branch?path=${encodeURIComponent(node.data.path)}`
        );
        const data = await res.json();
        node.children = (data.children ?? []).map((c) =>
            toNode(c, node.data.path)
        );
    }
}

/* --- Kategóriaválasztás kezelése ---
Input: change event, e.value (kiválasztott node key)
Output: emit "select" (id), "path-change" (útvonal) */
function onChange(e) {
    const node = findNodeByKey(nodes.value, e.value);
    if (node) {
        emit("select", node.data.id);
        emit("path-change", node.data.path);
    }
}

/* --- Segédfüggvény: node keresése key alapján ---
Input: lista, key
Output: node objektum vagy null */
function findNodeByKey(list, key) {
    for (const n of list) {
        if (n.key === key) return n;
        if (n.children?.length) {
            const f = findNodeByKey(n.children, key);
            if (f) return f;
        }
    }
    return null;
}

onMounted(loadRoot);
</script>
