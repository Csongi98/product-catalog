import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import symfonyPlugin from "vite-plugin-symfony";

export default defineConfig({
    plugins: [vue(), symfonyPlugin()],
    build: {
        outDir: "public/build",
        manifest: true,
        rollupOptions: {
            input: { app: "./assets/app.js" },
        },
    },
    server: {
        host: "127.0.0.1",
        port: 5173,
        strictPort: true,
        hmr: {
            host: "127.0.0.1",
            port: 5173,
        },
        allowedHosts: ["127.0.0.1", "localhost", "[::1]"],
    },
});
