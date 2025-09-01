<template>
  <div class="sharing-component">
    <q-dialog v-model="showDialog" persistent>
      <q-card
        class="sharing-card stardew-paper"
        style="width: 90%; max-width: 500px"
      >
        <q-card-section class="row items-center">
          <div class="text-h6 pixelated-heading">Share Weather</div>
          <q-space></q-space>
          <q-btn icon="close" flat round dense v-close-popup></q-btn>
        </q-card-section>

        <q-card-section>
          <p>Share the current weather information:</p>

          <div v-if="!weatherData" class="text-center q-pa-md">
            <p>No weather data available to share.</p>
            <p class="text-grey-7">
              Ask about the weather first to get information to share.
            </p>
          </div>

          <div v-else class="weather-share-preview q-pa-md q-my-md">
            <div class="text-subtitle1 text-weight-bold q-mb-sm">
              {{ weatherData.location }}
            </div>
            <div class="text-body1">{{ weatherData.description }}</div>
          </div>
        </q-card-section>

        <q-card-section v-if="weatherData">
          <q-separator class="q-mb-md"></q-separator>
          <div class="text-subtitle2">Share Options</div>

          <div class="row q-col-gutter-md q-mt-md">
            <!-- Copy to Clipboard -->
            <div class="col-4 text-center">
              <q-btn
                flat
                round
                color="primary"
                icon="content_copy"
                @click="copyToClipboard"
              />
              <div class="q-mt-sm">Copy</div>
            </div>

            <!-- Web Share API (if available) -->
            <div class="col-4 text-center">
              <q-btn
                flat
                round
                color="primary"
                icon="share"
                @click="shareWeather"
                :disable="!canUseWebShare"
              />
              <div class="q-mt-sm">Share</div>
            </div>

            <!-- Export as Image -->
            <div class="col-4 text-center">
              <q-btn
                flat
                round
                color="primary"
                icon="image"
                @click="exportAsImage"
              />
              <div class="q-mt-sm">Image</div>
            </div>
          </div>
        </q-card-section>

        <!-- Canvas for generating image (hidden) -->
        <canvas ref="shareCanvas" style="display: none"></canvas>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import html2canvas from "html2canvas";

const $q = useQuasar();
const showDialog = ref(false);
const weatherData = ref(null);
const shareCanvas = ref(null);

// Check if Web Share API is available
const canUseWebShare = computed(() => {
  return navigator.share !== undefined;
});

// Methods
function copyToClipboard() {
  if (!weatherData.value) return;

  const shareText = generateShareText();

  navigator.clipboard
    .writeText(shareText)
    .then(() => {
      $q.notify({
        type: "positive",
        message: "Weather information copied to clipboard!",
        position: "top",
        timeout: 2000,
      });
    })
    .catch((err) => {
      console.error("Failed to copy text: ", err);
      $q.notify({
        type: "negative",
        message: "Could not copy to clipboard",
        position: "top",
        timeout: 2000,
      });
    });
}

function shareWeather() {
  if (!weatherData.value || !canUseWebShare.value) return;

  const shareText = generateShareText();

  // Use Web Share API
  navigator
    .share({
      title: `Weather in ${weatherData.value.location}`,
      text: shareText,
      url: window.location.href,
    })
    .then(() => {
      $q.notify({
        type: "positive",
        message: "Shared successfully!",
        position: "top",
        timeout: 2000,
      });
    })
    .catch((error) => {
      console.error("Error sharing:", error);
      if (error.name !== "AbortError") {
        // Don't show error if user canceled
        $q.notify({
          type: "negative",
          message: "Could not share weather information",
          position: "top",
          timeout: 2000,
        });
      }
    });
}

function exportAsImage() {
  if (!weatherData.value) return;

  // Select the preview element
  const previewElement = document.querySelector(".weather-share-preview");

  if (!previewElement) {
    $q.notify({
      type: "negative",
      message: "Could not generate image",
      position: "top",
      timeout: 2000,
    });
    return;
  }

  html2canvas(previewElement, {
    backgroundColor: null,
    scale: 2, // Higher quality
  })
    .then((canvas) => {
      // Convert to data URL
      const dataUrl = canvas.toDataURL("image/png");

      // Create a temporary link and trigger download
      const link = document.createElement("a");
      link.href = dataUrl;
      link.download = `weather-${weatherData.value.location
        .replace(/\s+/g, "-")
        .toLowerCase()}.png`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      $q.notify({
        type: "positive",
        message: "Weather image saved!",
        position: "top",
        timeout: 2000,
      });
    })
    .catch((err) => {
      console.error("Error generating image:", err);
      $q.notify({
        type: "negative",
        message: "Could not generate image",
        position: "top",
        timeout: 2000,
      });
    });
}

function generateShareText() {
  if (!weatherData.value) return "";

  const { location, description, date } = weatherData.value;

  let shareText = `Weather in ${location}`;

  if (date) {
    shareText += ` for ${date}`;
  }

  shareText += `:\n${description}\n\nShared from Weather Forecaster`;

  return shareText;
}

// Set the weather data to share
function setWeatherData(data) {
  weatherData.value = data;
}

// Expose methods to parent component
defineExpose({
  showSharing: (data) => {
    // Set the weather data
    setWeatherData(data);
    // Show the dialog
    showDialog.value = true;
  },
});
</script>

<style scoped>
.sharing-card {
  border: 4px solid var(--border-color) !important;
  background-color: var(--paper-bg) !important;
  color: var(--text-color) !important;
}

.pixelated-heading {
  font-family: "VT323", monospace;
  letter-spacing: 1px;
  color: var(--text-color);
}

.weather-share-preview {
  background-color: var(--message-ai-bg);
  border: 2px solid var(--border-color);
  border-radius: 8px;
  box-shadow: 3px 3px 0 var(--shadow-color);
}
</style>
