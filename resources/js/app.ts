import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { route } from 'ziggy-js';
import { createPinia } from 'pinia';
import './bootstrap';
import { setupGlobalErrorHandler } from '@/lib/globalErrorHandler';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title: string) => `${title} - ${appName}`,
    resolve: (name: string) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<() => Promise<{ default: any }>>(`./Pages/**/*.vue`)
        ),
    setup({ el, App, props, plugin }: { el: HTMLElement; App: any; props: any; plugin: any }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)  // No config arg needed
            .use(createPinia())
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Setup global error handling after app initialization
setupGlobalErrorHandler();