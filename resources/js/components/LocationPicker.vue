<template>
  <div class="location-picker">
    <q-input
      v-model="searchQuery"
      placeholder="Search for a location..."
      outlined
      rounded
      dense
      class="q-mb-sm"
      :loading="isLoading"
    >
      <template v-slot:append>
        <q-icon
          v-if="searchQuery.length > 0"
          name="close"
          @click="searchQuery = ''"
          class="cursor-pointer"
        />
        <q-btn
          flat
          round
          icon="my_location"
          @click="detectCurrentLocation"
          :loading="isDetecting"
          size="sm"
        />
      </template>
    </q-input>

    <div v-if="locations.length > 0" class="location-results q-pa-sm">
      <q-list bordered separator>
        <q-item
          v-for="(location, index) in locations"
          :key="index"
          clickable
          v-ripple
          @click="selectLocation(location)"
        >
          <q-item-section>
            <q-item-label>{{ location.name }}</q-item-label>
            <q-item-label caption>{{ location.country }}</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { useQuasar } from "quasar";
import "../../css/components/location-picker.css";

const $q = useQuasar();
const emit = defineEmits(["location-selected"]);

// State
const searchQuery = ref("");
const locations = ref([]);
const isLoading = ref(false);
const isDetecting = ref(false);

// Search for locations when query changes
watch(searchQuery, async (newQuery) => {
  if (newQuery.length < 2) {
    locations.value = [];
    return;
  }

  try {
    isLoading.value = true;
    // In a real app, this would call the API
    // const response = await api.searchLocations(newQuery);

    // For now, simulate results
    await new Promise((resolve) => setTimeout(resolve, 500));

    locations.value = [
      {
        name: "New York",
        country: "United States",
        latitude: 40.7128,
        longitude: -74.006,
      },
      {
        name: "New Delhi",
        country: "India",
        latitude: 28.6139,
        longitude: 77.209,
      },
      {
        name: "Newcastle",
        country: "United Kingdom",
        latitude: 54.9783,
        longitude: -1.6178,
      },
    ].filter((loc) => loc.name.toLowerCase().includes(newQuery.toLowerCase()));

    isLoading.value = false;
  } catch (error) {
    console.error("Error searching locations:", error);
    isLoading.value = false;
    $q.notify({
      color: "negative",
      message: "Failed to search for locations",
      icon: "report_problem",
    });
  }
});

// Detect current location
function detectCurrentLocation() {
  if (!navigator.geolocation) {
    $q.notify({
      color: "negative",
      message: "Geolocation is not supported by your browser",
      icon: "report_problem",
    });
    return;
  }

  isDetecting.value = true;

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      try {
        // In a real app, this would call the API
        // const response = await api.detectLocation({
        //   latitude: position.coords.latitude,
        //   longitude: position.coords.longitude
        // });

        // For now, simulate a result
        await new Promise((resolve) => setTimeout(resolve, 1000));

        const detectedLocation = {
          name: "Current Location",
          country: "Detected",
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
        };

        selectLocation(detectedLocation);
        isDetecting.value = false;
      } catch (error) {
        console.error("Error detecting location:", error);
        isDetecting.value = false;
        $q.notify({
          color: "negative",
          message: "Failed to detect your location",
          icon: "report_problem",
        });
      }
    },
    (error) => {
      console.error("Geolocation error:", error);
      isDetecting.value = false;

      let errorMessage = "Failed to detect your location";
      if (error.code === 1) {
        errorMessage = "Location permission denied";
      }

      $q.notify({
        color: "negative",
        message: errorMessage,
        icon: "report_problem",
      });
    }
  );
}

// Select a location
function selectLocation(location) {
  searchQuery.value = location.name;
  locations.value = [];
  emit("location-selected", location);
}
</script>

<style scoped>
/* Styles moved to external CSS file */
</style>
