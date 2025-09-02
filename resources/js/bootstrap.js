import axios from 'axios';
window.axios = axios;

// Set the base URL to ensure API requests go to the correct server
window.axios.defaults.baseURL = ''; // Use relative URL when in production/Docker
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}
