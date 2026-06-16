import '../css/app.css';
import '../css/bootstrap.min.css';
import '../css/demo.css';
import '../css/fonts.css';
import '../css/kaiadmin.css';
import '../css/kaiadmin.min.css';
import '../css/fonts.min.css';
import '../css/plugins.css';
import '../css/plugins.min.css';

import '../js/core/jquery-3.7.1.min.js';
import '../js/core/popper.min.js';
import '../js/core/bootstrap.min.js';

// import '../assets/fonts/fontawesome-free-6.5.2-web/js/v4-shims.min.js';
// import '../assets/fonts/simple-line-icons-2.5.5/js/simple-line-icons.min.js';
// import '../js/core/jquery.slimscroll.min.js'

//import '../js/plugin/jquery-scrollbar/jquery.scrollbar.min.js';
import '../js/kaiadmin.js';
import '../js/setting-demo.js';
//import '../js/demo.js';


import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

import 'bootstrap-icons/font/bootstrap-icons.css';

import './bootstrap';
import { installFetchCsrfInterceptor, syncCsrfToken } from './utils/csrf';

installFetchCsrfInterceptor();

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import InactivityWarning from './Components/InactivityWarning.vue';



const link = document.createElement('link')
link.rel = 'stylesheet'
link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
document.head.appendChild(link)

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

router.on('success', (event) => {
    syncCsrfToken(event.detail.page.props?.csrf_token);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const initialPageProps = props?.initialPage?.props ?? {};
        syncCsrfToken(initialPageProps?.csrf_token);
        const inactivityTimeoutMinutes = Number(
            initialPageProps?.session?.inactivity_timeout_minutes ?? 12,
        );
        const isAuthenticated = Boolean(initialPageProps?.auth?.user);

        return createApp({
            render: () =>
                h('div', [
                    h(InactivityWarning, {
                        isAuthenticated,
                        timeoutMinutes: inactivityTimeoutMinutes,
                    }),
                    h(App, props),
                ]),
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});


