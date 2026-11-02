import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    pages: './Pages',
    title: (title) => (title ? `${title} | توسيلا` : 'توسيلا'),
    progress: {
        delay: 120,
        color: '#d6b568',
        includeCSS: true,
        showSpinner: false,
    },
});
