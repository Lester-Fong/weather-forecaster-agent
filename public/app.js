// Basic app.js file
document.addEventListener('DOMContentLoaded', function() {
  console.log('Weather Forecaster Agent loaded');
  
  // Initialize app
  const app = document.getElementById('app');
  if (app) {
    app.innerHTML = '<div class="p-4 bg-gradient-to-r from-green-600 to-blue-400 text-white text-center"><h1 class="text-2xl">Weather Forecaster Agent</h1><p>Your AI-powered weather assistant</p></div>';
  }
});
