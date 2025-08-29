<template>
  <div
    class="message-bubble"
    :class="{ 'user-message': isUser, 'ai-message': !isUser }"
  >
    <div class="message-content" v-html="formattedContent"></div>
    <div v-if="metadata && !isUser" class="message-metadata">
      <div v-if="metadata.location" class="location">
        {{ metadata.location }}
      </div>
      <div v-if="metadata.weather" class="weather-info">
        <span v-if="metadata.weather.condition" class="condition badge q-mr-xs">
          {{ metadata.weather.condition }}
        </span>
        <span v-if="metadata.weather.temperature" class="temperature badge">
          {{ metadata.weather.temperature }}°C
        </span>
      </div>
      <div v-if="metadata.date" class="date-info text-xs opacity-70">
        {{ metadata.date }}
      </div>
      <div v-if="metadata.timestamp" class="timestamp text-xs opacity-70">
        {{ formatTime(metadata.timestamp) }}
      </div>
    </div>
  </div>
</template>

<script setup>
import "../../css/components/message-bubble.css";
import { computed } from "vue";

const props = defineProps({
  content: {
    type: String,
    required: true,
  },
  isUser: {
    type: Boolean,
    default: false,
  },
  metadata: {
    type: Object,
    default: () => null,
  },
});

const formatTime = (timestamp) => {
  if (!timestamp) return "";
  const date = new Date(timestamp);
  return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
};

// Format content to preserve line breaks in HTML
const formattedContent = computed(() => {
  if (!props.content) return "";

  // Replace line breaks with HTML line breaks
  const formatted = props.content
    .replace(/\n\n/g, "</p><p>")
    .replace(/\n/g, "<br>");

  // Wrap the content in paragraph tags
  return `<p>${formatted}</p>`;
});
</script>

<style scoped>
/* Styles moved to external CSS file */
</style>
