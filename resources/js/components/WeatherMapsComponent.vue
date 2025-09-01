<template>
  <div class="weather-maps">
    <q-dialog v-model="showDialog" persistent>
      <q-card
        class="maps-card stardew-paper"
        style="width: 90%; max-width: 800px; max-height: 80vh"
      >
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6 pixelated-heading">Weather Maps</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-md">
          <div class="row q-col-gutter-md">
            <!-- Map Type Selection -->
            <div class="col-12 col-md-4">
              <q-select
                v-model="selectedMapType"
                :options="mapTypes"
                label="Map Type"
                outlined
                dense
                emit-value
                map-options
                class="stardew-input"
              />

              <div class="q-mt-md">
                <q-input
                  v-model="location"
                  label="Location"
                  outlined
                  dense
                  class="stardew-input"
                />
              </div>

              <div class="q-mt-md">
                <q-btn
                  color="primary"
                  icon="search"
                  label="Search"
                  class="full-width stardew-button"
                  @click="loadMapForLocation"
                  :loading="isLoading"
                />
              </div>

              <div class="q-mt-md">
                <q-btn
                  color="secondary"
                  icon="my_location"
                  label="Use My Location"
                  class="full-width stardew-button"
                  @click="useCurrentLocation"
                  :loading="isLoading"
                />
              </div>

              <div class="q-mt-md map-legend">
                <div class="text-subtitle2 q-mb-sm">Legend</div>
                <div
                  v-if="selectedMapType.value === 'temperature'"
                  class="legend-item"
                >
                  <div class="legend-gradient temperature-gradient"></div>
                  <div class="legend-labels">
                    <span>Cold</span>
                    <span>Hot</span>
                  </div>
                </div>
                <div
                  v-else-if="selectedMapType.value === 'precipitation'"
                  class="legend-item"
                >
                  <div class="legend-gradient precipitation-gradient"></div>
                  <div class="legend-labels">
                    <span>None</span>
                    <span>Heavy</span>
                  </div>
                </div>
                <div
                  v-else-if="selectedMapType.value === 'wind'"
                  class="legend-item"
                >
                  <div class="legend-gradient wind-gradient"></div>
                  <div class="legend-labels">
                    <span>Calm</span>
                    <span>Strong</span>
                  </div>
                </div>
                <div
                  v-else-if="selectedMapType.value === 'cloud'"
                  class="legend-item"
                >
                  <div class="legend-gradient cloud-gradient"></div>
                  <div class="legend-labels">
                    <span>Clear</span>
                    <span>Overcast</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Map Display -->
            <div class="col-12 col-md-8">
              <div class="map-container" ref="mapContainer">
                <div v-if="isLoading" class="map-loading flex flex-center">
                  <q-spinner color="primary" size="3em" />
                  <div class="q-mt-sm">Loading map data...</div>
                </div>

                <div
                  v-else-if="!mapUrl"
                  class="map-placeholder flex flex-center"
                >
                  <div class="text-center">
                    <q-icon name="map" size="3em" color="grey-7" />
                    <div class="q-mt-sm text-grey-7">
                      Select a location and map type to view weather data
                    </div>
                  </div>
                </div>

                <div v-else class="map-display">
                  <img
                    :src="mapUrl"
                    class="weather-map-image"
                    :alt="'Weather map showing ' + selectedMapType.label"
                  />
                </div>

                <div
                  v-if="mapError"
                  class="map-error q-mt-sm text-negative text-center"
                >
                  {{ mapError }}
                </div>
              </div>

              <div
                class="map-controls q-mt-md row q-col-gutter-sm justify-between"
              >
                <div class="col-auto">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="zoom_in"
                    @click="zoomIn"
                    :disable="!mapUrl"
                  >
                    <q-tooltip>Zoom In</q-tooltip>
                  </q-btn>
                </div>
                <div class="col-auto">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="zoom_out"
                    @click="zoomOut"
                    :disable="!mapUrl"
                  >
                    <q-tooltip>Zoom Out</q-tooltip>
                  </q-btn>
                </div>
                <div class="col-auto">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="refresh"
                    @click="refreshMap"
                    :disable="!mapUrl"
                  >
                    <q-tooltip>Refresh Map</q-tooltip>
                  </q-btn>
                </div>
                <div class="col-auto">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="fullscreen"
                    @click="toggleFullscreen"
                    :disable="!mapUrl"
                  >
                    <q-tooltip>Toggle Fullscreen</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useQuasar } from "quasar";
import axios from "axios";

const $q = useQuasar();
const showDialog = ref(false);
const location = ref("");
const isLoading = ref(false);
const mapUrl = ref("");
const mapError = ref("");
const mapContainer = ref(null);
const zoomLevel = ref(10);
const isFullscreen = ref(false);

// Map types available
const mapTypes = [
  { label: "Temperature", value: "temperature" },
  { label: "Precipitation", value: "precipitation" },
  { label: "Wind Speed", value: "wind" },
  { label: "Cloud Cover", value: "cloud" },
];

const selectedMapType = ref(mapTypes[0]);

// Watch for changes in map type and reload map if we have a location
watch(selectedMapType, (newVal) => {
  if (location.value) {
    loadMapForLocation();
  }
});

// Methods
async function loadMapForLocation() {
  if (!location.value) {
    mapError.value = "Please enter a location";
    return;
  }

  isLoading.value = true;
  mapError.value = "";
  console.log("Loading map for location:", location.value);

  try {
    // Generate the map URL - in a real implementation, this would call an API
    // Here we're simulating the API call with a delay
    await new Promise((resolve) => setTimeout(resolve, 1000));

    // For demonstration, we'll use a static map URL from OpenWeatherMap as a placeholder
    // In a real implementation, this would be a call to your backend which would return the actual map URL
    const type = selectedMapType.value.value;
    console.log("Selected map type:", type);
    const zoom = zoomLevel.value;
    const locationEncoded = encodeURIComponent(location.value);

    // This would typically be your API endpoint that generates the map
    // mapUrl.value = `https://tile.openweathermap.org/map/${getOpenWeatherMapLayer(
    //   type
    // )}/10/${zoom}/${zoom}/1.png?appid=YOUR_API_KEY`;

    // For the demo, we'll use a placeholder image from a weather API
    const url = getPlaceholderMapUrl(type);
    console.log("Setting map URL to:", url);
    mapUrl.value = url;
  } catch (error) {
    console.error("Error loading map:", error);
    mapError.value = "Could not load weather map. Please try again.";
  } finally {
    isLoading.value = false;
  }
}

function getOpenWeatherMapLayer(type) {
  switch (type) {
    case "temperature":
      return "temp_new";
    case "precipitation":
      return "precipitation_new";
    case "wind":
      return "wind_new";
    case "cloud":
      return "clouds_new";
    default:
      return "temp_new";
  }
}

function getPlaceholderMapUrl(type) {
  // These are placeholder URLs for demo purposes
  // In a real application, you would generate these dynamically
  const baseUrl = "/assets/weather-maps/";
  console.log("Getting placeholder map URL for type:", type);

  let url = "";
  switch (type) {
    case "temperature":
      url = `${baseUrl}temperature-map.png`;
      break;
    case "precipitation":
      url = `${baseUrl}precipitation-map.png`;
      break;
    case "wind":
      url = `${baseUrl}wind-map.png`;
      break;
    case "cloud":
      url = `${baseUrl}cloud-map.png`;
      break;
    default:
      url = `${baseUrl}temperature-map.png`;
  }
  
  console.log("Returning URL:", url);
  return url;
}
}
}

function useCurrentLocation() {
  if (!navigator.geolocation) {
    mapError.value = "Geolocation is not supported by your browser";
    return;
  }

  isLoading.value = true;
  mapError.value = "";

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      try {
        // Get city name from coordinates using reverse geocoding
        // For demo purposes, we'll just use a placeholder
        location.value = "Current Location";

        // Load map with the current location
        await loadMapForLocation();
      } catch (error) {
        console.error("Error getting location:", error);
        mapError.value = "Could not determine your location";
      } finally {
        isLoading.value = false;
      }
    },
    (error) => {
      isLoading.value = false;

      switch (error.code) {
        case error.PERMISSION_DENIED:
          mapError.value = "Location permission denied";
          break;
        case error.POSITION_UNAVAILABLE:
          mapError.value = "Location information unavailable";
          break;
        case error.TIMEOUT:
          mapError.value = "Location request timed out";
          break;
        default:
          mapError.value = "An unknown error occurred";
      }
    }
  );
}

function zoomIn() {
  if (zoomLevel.value < 15) {
    zoomLevel.value++;
    refreshMap();
  }
}

function zoomOut() {
  if (zoomLevel.value > 5) {
    zoomLevel.value--;
    refreshMap();
  }
}

function refreshMap() {
  if (location.value) {
    loadMapForLocation();
  }
}

function toggleFullscreen() {
  if (!mapContainer.value) return;

  isFullscreen.value = !isFullscreen.value;

  if (isFullscreen.value) {
    if (mapContainer.value.requestFullscreen) {
      mapContainer.value.requestFullscreen();
    } else if (mapContainer.value.mozRequestFullScreen) {
      mapContainer.value.mozRequestFullScreen();
    } else if (mapContainer.value.webkitRequestFullscreen) {
      mapContainer.value.webkitRequestFullscreen();
    } else if (mapContainer.value.msRequestFullscreen) {
      mapContainer.value.msRequestFullscreen();
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
      document.msExitFullscreen();
    }
  }
}

// Expose methods to parent component
defineExpose({
  showMaps: () => {
    showDialog.value = true;
  },
  setLocation: (loc) => {
    console.log("Setting location to:", loc);
    location.value = loc;
    // We need to wait for the component to be mounted before calling loadMapForLocation
    setTimeout(() => {
      loadMapForLocation();
    }, 100);
  },
});
</script>

<style scoped>
.maps-card {
  border: 4px solid var(--border-color) !important;
  background-color: var(--paper-bg) !important;
  color: var(--text-color) !important;
}

.pixelated-heading {
  font-family: "VT323", monospace;
  letter-spacing: 1px;
  color: var(--text-color);
}

.map-container {
  height: 400px;
  border: 2px solid var(--border-color);
  border-radius: 4px;
  overflow: hidden;
  position: relative;
}

.map-placeholder,
.map-loading {
  height: 100%;
  width: 100%;
  background-color: var(--paper-bg);
}

.map-display {
  height: 100%;
  width: 100%;
  position: relative;
  border: 2px solid red; /* Debugging border */
}

.weather-map-image {
  width: 100%;
  height: 100%;
  object-fit: contain; /* Changed from cover to ensure the whole image is visible */
  border: 2px solid blue; /* Debugging border */
}

.map-legend {
  border: 1px solid var(--border-color);
  border-radius: 4px;
  padding: 8px;
  background-color: var(--paper-bg);
}

.legend-item {
  margin-top: 8px;
}

.legend-gradient {
  height: 16px;
  border-radius: 2px;
  margin-bottom: 4px;
}

.temperature-gradient {
  background: linear-gradient(
    to right,
    #0000ff,
    #00ffff,
    #00ff00,
    #ffff00,
    #ff0000
  );
}

.precipitation-gradient {
  background: linear-gradient(
    to right,
    #ffffff,
    #a5f2f3,
    #00b4f0,
    #0000ff,
    #4b0082
  );
}

.wind-gradient {
  background: linear-gradient(to right, #ffffff, #77dd77, #ffb347, #ff6961);
}

.cloud-gradient {
  background: linear-gradient(to right, #ffffff, #dddddd, #aaaaaa, #444444);
}

.legend-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: var(--text-color);
}
</style>
