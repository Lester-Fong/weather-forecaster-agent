import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

export default {
    // Chat endpoints
    sendMessage(message, sessionId, location) {
        return apiClient.post('/chat/message', { message, session_id: sessionId, location });
    },
    
    getChatHistory(sessionId) {
        return apiClient.get(`/chat/history/${sessionId}`);
    },
    
    clearChatHistory(sessionId) {
        return apiClient.delete(`/chat/clear/${sessionId}`);
    },
    
    // Location endpoints
    searchLocations(query) {
        return apiClient.get('/locations/search', { params: { query } });
    },
    
    detectLocation(coordinates) {
        return apiClient.post('/locations/detect', coordinates);
    },
    
    // Weather endpoints
    getCurrentWeather(location) {
        return apiClient.get('/weather/current', { params: { location } });
    },
    
    getWeatherForecast(location, days) {
        return apiClient.get('/weather/forecast', { params: { location, days } });
    }
};
