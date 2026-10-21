import laravel from "laravel-vite-plugin";
import { defineConfig, loadEnv } from "vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    return {
        server: {
            host: "0.0.0.0",
            port: 5174,
            strictPort: true,
            cors: true,

            hmr: {
                host: env.VITE_HOST,
            },
        },

        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/js/app.js"],
                refresh: true,
            }),
        ],
    };
});
