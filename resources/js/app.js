import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    pages: './Pages',
    title: (title) => (title ? `${title} | توسيلا` : 'توسيلا'),
});
