<template>
  <q-page class="flex flex-col q-pa-md">
    <div class="container mx-auto flex flex-col h-screen max-w-3xl">
      <div class="text-center q-mb-md stardew-title">
        <div class="title-hangers">
          <div class="title-hanger left-hanger"></div>
          <div class="title-hanger right-hanger"></div>
        </div>
        <h1 class="text-2xl font-bold pixelated-heading">Weather Forecaster</h1>
        <p class="text-xl">Ask about weather in any location, any time!</p>
      </div>

      <!-- Chat messages container -->
      <div
        ref="chatContainer"
        class="chat-container flex-grow q-mb-md overflow-auto stardew-paper"
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
          />

          <!-- Typing indicator -->
          <typing-indicator v-if="isTyping" />
        </div>
      </div>

      <!-- Input form -->
      <div class="chat-input-container q-pa-sm stardew-input-container">
        <q-form @submit="sendMessage" class="row">
          <q-input
            v-model="userInput"
            placeholder="Type your weather question..."
            outlined
            class="col stardew-input"
            :disable="isTyping"
            @keydown.enter.prevent="sendMessage"
            bg-color="amber-1"
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
            icon="location_on"
          >
            <q-tooltip>Set Location</q-tooltip>
          </q-btn>
          <q-btn
            flat
            color="secondary"
            class="stardew-control-btn"
            padding="xs"
            icon="settings"
          >
            <q-tooltip>Settings</q-tooltip>
          </q-btn>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";
import MessageBubble from "../components/MessageBubble.vue";
import TypingIndicator from "../components/TypingIndicator.vue";
import api from "../services/api";
import axios from "axios";

// Import external CSS
import "../../css/components/chat-view.css";

// State
const userInput = ref("");
const messages = ref([]);
const isTyping = ref(false);
const chatContainer = ref(null);
const sessionId = ref(generateSessionId());
const userLocation = ref(null);

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
    messages.value.push({
      content: response.data.message,
      isUser: false,
      metadata: {
        timestamp: new Date(),
        location: response.data.metadata?.location || null,
        weather: response.data.metadata?.weather || null,
        date: response.data.metadata?.date || null,
      },
    });

    // For debugging purposes only - remove in production
    console.log(response);

    scrollToBottom();
  } catch (error) {
    console.error("Error sending message:", error);
    isTyping.value = false;

    // Show error message
    messages.value.push({
      content: "Sorry, I encountered an error. Please try again.",
      isUser: false,
      metadata: {
        timestamp: new Date(),
      },
    });

    scrollToBottom();
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
</style>
