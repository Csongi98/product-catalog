import { ref, computed, watch } from "vue";

const KEY = "cart_v1";
const state = ref(load());

function load() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || "[]");
    } catch {
        return [];
    }
}
watch(state, (v) => localStorage.setItem(KEY, JSON.stringify(v)), {
    deep: true,
});

export function useCart() {
    const items = state;

    function add(p, qty = 1) {
        const i = items.value.findIndex((x) => x.id === p.id);
        if (i >= 0) items.value[i].qty += qty;
        else
            items.value.push({
                id: p.id,
                name: p.name,
                price: p.price / 100,
                imageUrl: p.imageUrl,
                qty: Math.max(1, qty | 0),
            });
    }
    function remove(id) {
        items.value = items.value.filter((x) => x.id !== id);
    }
    function setQty(id, qty) {
        const it = items.value.find((x) => x.id === id);
        if (!it) return;
        it.qty = Math.max(1, qty | 0);
    }
    function clear() {
        items.value = [];
    }

    const count = computed(() => items.value.reduce((s, x) => s + x.qty, 0));
    const total = computed(() =>
        items.value.reduce((s, x) => s + x.price * x.qty, 0)
    );

    return { items, add, remove, setQty, clear, count, total };
}
