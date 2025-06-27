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

//import '../js/plugin/jquery-scrollbar/jquery.scrollbar.min.js';
import '../js/kaiadmin.min.js';
import '../js/setting-demo.js';
//import '../js/demo.js';


import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

import 'bootstrap-icons/font/bootstrap-icons.css';


import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';



const link = document.createElement('link')
link.rel = 'stylesheet'
link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
document.head.appendChild(link)

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});


