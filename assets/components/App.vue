<script setup>
import { ref } from "vue";
import CategoryMenu from "./CategoryMenu.vue";
import ProductGrid from "./ProductGrid.vue";
const currentCategoryId = ref(null);
</script>

<template>
    <div
        class="container mx-auto px-4 py-6 grid grid-cols-1 md:grid-cols-4 gap-6"
    >
        <aside class="md:col-span-1">
            <CategoryMenu @select="(id) => (currentCategoryId = id)" />
        </aside>
        <main class="md:col-span-3">
            <ProductGrid :category-id="currentCategoryId" />
        </main>
    </div>
</template>
