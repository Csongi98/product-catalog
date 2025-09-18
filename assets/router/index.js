import { createRouter, createWebHistory } from "vue-router";
import HomeApp from "../components/HomeApp.vue";
import CatalogApp from "../components/CatalogApp.vue";
import ProductDetail from "../components/ProductDetail.vue";

const routes = [
    { path: "/", name: "home", component: HomeApp },
    { path: "/catalog", name: "catalog", component: CatalogApp },
    {
        path: "/product/:id(\\d+)",
        name: "product",
        component: ProductDetail,
        props: true,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

export default router;
