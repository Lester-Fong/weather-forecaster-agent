import axios from 'axios';
window.axios = axios;

// Set the base URL to ensure API requests go to the correct server
window.axios.defaults.baseURL = 'http://127.0.0.1:8000';
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
