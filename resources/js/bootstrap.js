import axios from 'axios';
window.axios = axios;

// Set the base URL to ensure API requests go to the correct server
window.axios.defaults.baseURL = ''; // Use relative URL when in production/Docker
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
