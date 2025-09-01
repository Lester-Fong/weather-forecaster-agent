import './bootstrap';

import { createApp } from 'vue';
import { Quasar, Notify, Dialog } from 'quasar';
import router from './router';
import Vue3TouchEvents from 'vue3-touch-events';

// Import Quasar css
import 'quasar/dist/quasar.css';
// Import icon libraries
import '@quasar/extras/material-icons/material-icons.css';

// Import main app component
import App from './App.vue';

// Create the Vue application
const app = createApp(App);

// Use Quasar with Stardew Valley inspired color palette
app.use(Quasar, {
    plugins: {
        Notify,
        Dialog
    }, // import Quasar plugins and add here
    config: {
        brand: {
            // Stardew Valley color palette
            primary: '#5A8F45',     // Green fields
            secondary: '#8E5C34',   // Wooden brown
            accent: '#F4A734',      // Golden wheat
            dark: '#242421',        // Deep soil
            'dark-page': '#1A1914', // Deep cave
            positive: '#7BAE6F',    // Spring green
            negative: '#B94236',    // Red berry
            info: '#67AFCB',        // Lake water blue
            warning: '#FFCC58'      // Sun yellow
        }
    }
});

// Use Vue Touch Events for swipe gestures
app.use(Vue3TouchEvents);

// Use the router
app.use(router);

// Mount the application
app.mount('#app');
