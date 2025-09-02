<template>
  <q-page class="flex q-pa-md">
    <div class="container mx-auto flex h-screen max-w-3xl">
      <div class="text-center q-mb-md stardew-title">
        <div class="title-hangers">
          <div class="title-hanger left-hanger"></div>
          <div class="title-hanger right-hanger"></div>
        </div>
        <p class="text-3xl md:text-5xl font-bold pixelated-heading">
          Weather Forecaster
        </p>
        <p class="pixelated-heading text-lg md:text-xl">
          Ask about weather in any location, any time!
        </p>
      </div>

      <!-- Chat messages container -->
      <div
        ref="chatContainer"
        class="chat-container flex-grow q-mb-md overflow-auto stardew-paper"
        v-touch:swipe.right="showSettings"
      >
        <div class="flex flex-col q-pa-md">
          <!-- System welcome message -->
          <message-bubble
            content="Hello farmer! I'm your friendly weather helper. Ask me about the weather for any location or date, like 'What's the weather tomorrow in Stardew Valley?' or 'Will it rain this weekend?'"
            :is-user="false"
          />

          <!-- Message bubbles -->
          <message-bubble
            v-for="(message, index) in messages"
            :key="index"
            :content="message.content"
            :is-user="message.isUser"
            :metadata="message.metadata"
            v-touch:swipe.left="
              message.isUser ? () => deleteMessage(index) : () => {}
            "
          />

          <!-- Typing indicator -->
          <typing-indicator v-if="isTyping" />
        </div>
      </div>

      <!-- Input form -->
      <div class="chat-input-container q-pa-sm stardew-input-container">
        <q-form @submit="sendMessage" class="row items-center no-wrap">
          <q-input
            v-model="userInput"
            placeholder="Type your weather question..."
            outlined
            class="col stardew-input"
            :disable="isTyping"
            @keydown.enter.prevent="sendMessage"
            :bg-color="$q.dark.isActive ? 'grey-9' : 'amber-1'"
            color="primary"
          />
          <q-btn
            type="submit"
            icon="send"
            color="accent"
            class="q-ml-sm stardew-button"
            :disable="!userInput.trim() || isTyping"
            padding="sm"
          >
            <q-tooltip>Send Message</q-tooltip>
          </q-btn>
          <voice-input class="q-ml-sm" @input="handleVoiceInput" />
        </q-form>

        <div class="stardew-controls q-mt-sm flex justify-center">
          <q-btn
            flat
            color="secondary"
            class="stardew-control-btn"
            padding="xs"
            icon="delete"
            @click="clearChat"
          >
            <q-tooltip>Clear Chat</q-tooltip>
          </q-btn>

          <q-btn
            flat
            color="secondary"
            class="stardew-control-btn q-mx-md"
            padding="xs"
            icon="notifications"
            @click="showAlerts"
          >
            <q-tooltip>Weather Alerts</q-tooltip>
          </q-btn>

          <q-btn
            flat
            color="secondary"
            class="stardew-control-btn"
            padding="xs"
            icon="settings"
            @click="showSettings"
          >
            <q-tooltip>Settings</q-tooltip>
          </q-btn>
        </div>
      </div>

      <!-- Weather Alerts Component -->
      <weather-alerts ref="weatherAlertsRef" />
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from "vue";
import MessageBubble from "../components/MessageBubble.vue";
import TypingIndicator from "../components/TypingIndicator.vue";
import VoiceInput from "../components/VoiceInput.vue";
import WeatherAlerts from "../components/WeatherAlerts.vue";
import api from "../services/api";
import axios from "axios";
import { useQuasar } from "quasar";

// Import external CSS
import "../../css/components/chat-view.css";

// State
const userInput = ref("");
const messages = ref([]);
const isTyping = ref(false);
const chatContainer = ref(null);
const sessionId = ref(generateSessionId());
const userLocation = ref(null);
const $q = useQuasar();
const isOffline = ref(false);
const weatherAlertsRef = ref(null);

// Check network status
window.addEventListener("online", updateOnlineStatus);
window.addEventListener("offline", updateOnlineStatus);

function updateOnlineStatus() {
  isOffline.value = !navigator.onLine;
  if (isOffline.value) {
    $q.notify({
      type: "warning",
      message: "You are offline. Limited functionality available.",
      position: "top",
      timeout: 3000,
    });
  } else {
    $q.notify({
      type: "positive",
      message: "You are back online!",
      position: "top",
      timeout: 2000,
    });
    // Fetch any updates if needed
  }
}

// Methods
function generateSessionId() {
  return (
    "session_" + Date.now() + "_" + Math.random().toString(36).substring(2, 9)
  );
}

async function sendMessage() {
  if (!userInput.value.trim() || isTyping.value) return;

  // Add user message to chat
  messages.value.push({
    content: userInput.value,
    isUser: true,
    timestamp: new Date(),
  });

  // Clear input and scroll to bottom
  const userMessage = userInput.value;
  userInput.value = "";
  scrollToBottom();

  // Show typing indicator
  isTyping.value = true;

  try {
    // Call the API with the correct endpoint and parameter names
    const response = await axios.post("/api/weather/query", {
      query: userMessage,
      session_id: sessionId.value,
    });

    // Handle successful API response
    isTyping.value = false;

    // Add AI response from the server
    const aiResponse = {
      content: response.data.message,
      isUser: false,
      metadata: {
        timestamp: new Date(),
        location: response.data.metadata?.location || null,
        weather: response.data.metadata?.weather || null,
        date: response.data.metadata?.date || null,
      },
    };

    messages.value.push(aiResponse);

    // Cache recent weather data for offline access
    cacheWeatherData(response.data);

    // Check for weather alerts
    checkWeatherAlerts(response.data);

    // For debugging purposes only - remove in production
    console.log(response);

    scrollToBottom();
  } catch (error) {
    console.error("Error sending message:", error);
    isTyping.value = false;

    // Check if offline
    if (!navigator.onLine) {
      messages.value.push({
        content:
          "You appear to be offline. I'll try to provide information from cached data if available.",
        isUser: false,
        metadata: {
          timestamp: new Date(),
        },
      });

      // Try to use cached data
      tryUseCachedData(userMessage);
    } else {
      // Online but other error
      messages.value.push({
        content: "Sorry, I encountered an error. Please try again.",
        isUser: false,
        metadata: {
          timestamp: new Date(),
        },
      });
    }

    scrollToBottom();
  }
}

function cacheWeatherData(responseData) {
  try {
    // Get existing cache or create new array
    const existingCache = localStorage.getItem("recentWeatherData");
    let weatherCache = existingCache ? JSON.parse(existingCache) : [];

    // Add new weather data if it contains weather information
    if (responseData.metadata && responseData.metadata.weather) {
      const newCacheItem = {
        location: responseData.metadata.location || "Unknown location",
        description: responseData.message,
        weather: responseData.metadata.weather,
        date: responseData.metadata.date,
        timestamp: Date.now(),
      };

      // Add to beginning of array
      weatherCache.unshift(newCacheItem);

      // Limit cache size to 5 items
      weatherCache = weatherCache.slice(0, 5);

      // Save to localStorage
      localStorage.setItem("recentWeatherData", JSON.stringify(weatherCache));
    }
  } catch (error) {
    console.error("Error caching weather data:", error);
  }
}

function tryUseCachedData(userMessage) {
  try {
    const cachedData = localStorage.getItem("recentWeatherData");
    if (cachedData) {
      const weatherCache = JSON.parse(cachedData);
      if (weatherCache.length > 0) {
        // Simple keyword matching for offline mode
        const userMessageLower = userMessage.toLowerCase();
        const matchedItem = weatherCache.find((item) =>
          userMessageLower.includes(item.location.toLowerCase())
        );

        if (matchedItem) {
          messages.value.push({
            content: `Here's the cached weather information for ${
              matchedItem.location
            } from ${new Date(matchedItem.timestamp).toLocaleString()}:\n\n${
              matchedItem.description
            }\n\n(This is cached data from your previous search)`,
            isUser: false,
            metadata: {
              timestamp: new Date(),
              location: matchedItem.location,
              weather: matchedItem.weather,
              date: matchedItem.date,
              isCached: true,
            },
          });
          return;
        }
      }
    }

    // No matching cached data
    messages.value.push({
      content:
        "I don't have any cached weather data for that location. Please reconnect to the internet to get the latest weather information.",
      isUser: false,
      metadata: {
        timestamp: new Date(),
      },
    });
  } catch (error) {
    console.error("Error retrieving cached data:", error);
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
  });
}

function clearChat() {
  // Clear messages array
  messages.value = [];

  // Generate a new session ID
  sessionId.value = generateSessionId();
}

function deleteMessage(index) {
  if (messages.value[index].isUser) {
    $q.dialog({
      title: "Delete Message",
      message: "Delete this message?",
      cancel: true,
      persistent: true,
    }).onOk(() => {
      messages.value.splice(index, 1);
    });
  }
}

function showSettings() {
  $q.dialog({
    title: "Settings",
    message: "Swipe detected! Opening settings...",
    persistent: true,
  });
  // In a real implementation, this would open the settings component
}

function handleVoiceInput(text) {
  if (text && text.trim()) {
    userInput.value = text;
    // Optional: automatically send the message
    sendMessage();

    // Notify user that voice input was received
    $q.notify({
      type: "positive",
      message: "Voice input received!",
      position: "top",
      timeout: 1500,
    });
  }
}

function showAlerts() {
  if (weatherAlertsRef.value) {
    weatherAlertsRef.value.showAlerts();
  }
}

// Check for weather alerts with received data
function checkWeatherAlerts(responseData) {
  if (
    weatherAlertsRef.value &&
    responseData &&
    responseData.metadata &&
    responseData.metadata.weather
  ) {
    // Transform the data into the format expected by the alerts component
    const weatherData = {
      location: responseData.metadata.location || "",
      temperature: responseData.metadata.weather.temperature || 0,
      precipitation: responseData.metadata.weather.precipitation || 0,
      snowfall: responseData.metadata.weather.snowfall || 0,
      windSpeed: responseData.metadata.weather.windSpeed || 0,
      isStorm: responseData.metadata.weather.isStorm || false,
      isExtreme: responseData.metadata.weather.isExtreme || false,
    };

    // Check if any alerts should be triggered
    weatherAlertsRef.value.checkAlerts(weatherData);
  }
}

// Get user's geolocation
function getUserLocation() {
  if (navigator.geolocation) {
    isTyping.value = true;
    messages.value.push({
      content:
        "I'm detecting your location to provide more relevant weather information...",
      isUser: false,
      metadata: {
        timestamp: new Date(),
      },
    });

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          // Call the backend to detect location from coordinates
          const response = await axios.post("/api/weather/detect-location", {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            session_id: sessionId.value,
          });

          // Check if we have a valid location in the response
          if (response.data && response.data.location) {
            userLocation.value = response.data.location;

            // Send a welcome message with the detected location
            messages.value.push({
              content: `I've detected that you're near ${response.data.location.name}, ${response.data.location.country}. Here's the current weather for your location:`,
              isUser: false,
              metadata: {
                timestamp: new Date(),
                location:
                  response.data.location.name +
                  ", " +
                  response.data.location.country,
              },
            });

            // Now fetch current weather for the location
            isTyping.value = true;
            const weatherResponse = await axios.post("/api/weather/query", {
              query: `What's the weather like in ${response.data.location.name}, ${response.data.location.country} right now?`,
              session_id: sessionId.value,
              location_id: response.data.location.id, // Pass the location ID to ensure we use the same location
            });

            isTyping.value = false;

            // Add AI response with weather info
            messages.value.push({
              content: weatherResponse.data.message,
              isUser: false,
              metadata: {
                timestamp: new Date(),
                location: weatherResponse.data.metadata?.location || null,
                weather: weatherResponse.data.metadata?.weather || null,
                date: weatherResponse.data.metadata?.date || null,
              },
            });
          } else {
            // Location not found in response
            messages.value.push({
              content:
                "I wasn't able to determine your precise location. Feel free to ask about weather in any specific city by name.",
              isUser: false,
              metadata: {
                timestamp: new Date(),
              },
            });
          }

          scrollToBottom();
        } catch (error) {
          console.error("Error detecting location:", error);
          isTyping.value = false;

          // Show a more informative error message
          messages.value.push({
            content:
              "I wasn't able to detect your location. Feel free to ask about weather in any location by mentioning the city name in your question.",
            isUser: false,
            metadata: {
              timestamp: new Date(),
            },
          });
        }
      },
      (error) => {
        console.error("Geolocation error:", error);
        isTyping.value = false;

        // Update the waiting message with a friendly prompt
        messages.value[messages.value.length - 1].content =
          "Hello! Feel free to ask about weather in any location.";
      }
    );
  }
}

// Lifecycle hooks
onMounted(() => {
  scrollToBottom();
  // Get user's location when the app loads
  getUserLocation();

  // Check network status on load
  updateOnlineStatus();

  // Register service worker for offline support
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker
      .register("/service-worker.js")
      .then((registration) => {
        console.log(
          "Service Worker registered with scope:",
          registration.scope
        );
      })
      .catch((error) => {
        console.error("Service Worker registration failed:", error);
      });
  }
});
</script>

<style scoped>
/* Custom styles for the hanging title sign */
.title-hangers {
  position: relative;
  width: 100%;
  height: 0;
}

.title-hanger {
  position: absolute;
  top: -12px;
  width: 8px;
  height: 8px;
  background-color: #8e5c34;
  box-shadow: 2px 2px 0 rgba(0, 0, 0, 0.2);
  z-index: 5;
  border-radius: 50%;
}

.left-hanger {
  left: 28%;
}

.right-hanger {
  right: 28%;
}

/* Additional styles specific to this component */
.stardew-title {
  margin-top: 0px;
  padding: 1rem;
  background-color: rgba(249, 243, 227, 0.95);
  border: 4px solid #8e5c34;
  border-radius: 10px;
  box-shadow: 0 6px 0 rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(4px);
  position: relative;
  z-index: 5;
  max-width: 80%;
  margin-left: auto;
  margin-right: auto;
}

:deep(.dark) .stardew-title {
  background-color: rgba(40, 34, 24, 0.95);
  border-color: #c2a97d;
  box-shadow: 0 6px 0 rgba(0, 0, 0, 0.5);
}
</style>
