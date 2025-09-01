<template>
  <div class="location-favorites">
    <q-dialog v-model="showDialog" persistent>
      <q-card
        class="favorites-card stardew-paper"
        style="width: 90%; max-width: 500px"
      >
        <q-card-section class="row items-center">
          <div class="text-h6 pixelated-heading">Favorite Locations</div>
          <q-space></q-space>
          <q-btn icon="close" flat round dense v-close-popup></q-btn>
        </q-card-section>

        <q-card-section>
          <div v-if="favorites.length === 0" class="text-center q-pa-md">
            <p>No favorite locations saved yet.</p>
            <p class="text-grey-7">
              Add locations to quickly access weather information.
            </p>
          </div>

          <q-list v-else bordered separator>
            <q-item
              v-for="(favorite, index) in favorites"
              :key="index"
              clickable
              @click="selectLocation(favorite)"
            >
              <q-item-section avatar>
                <q-icon name="location_on" color="accent" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ favorite.name }}</q-item-label>
                <q-item-label caption>{{
                  formatCoordinates(favorite.latitude, favorite.longitude)
                }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn
                  flat
                  round
                  dense
                  icon="delete"
                  color="negative"
                  @click.stop="confirmDelete(index)"
                >
                  <q-tooltip>Remove favorite</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-section>
          <q-separator class="q-mb-md"></q-separator>
          <div class="text-subtitle2">Add new location</div>
          <q-form @submit="addLocation" class="q-mt-sm">
            <q-input
              v-model="newLocation"
              label="Location name"
              dense
              outlined
              class="stardew-input q-mb-sm"
              :rules="[(val) => !!val || 'Location name is required']"
            />
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="newLatitude"
                  label="Latitude"
                  type="number"
                  step="0.000001"
                  dense
                  outlined
                  class="stardew-input"
                  :rules="[
                    (val) => !!val || 'Latitude is required',
                    (val) => (val >= -90 && val <= 90) || 'Invalid latitude',
                  ]"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="newLongitude"
                  label="Longitude"
                  type="number"
                  step="0.000001"
                  dense
                  outlined
                  class="stardew-input"
                  :rules="[
                    (val) => !!val || 'Longitude is required',
                    (val) => (val >= -180 && val <= 180) || 'Invalid longitude',
                  ]"
                />
              </div>
            </div>

            <div class="row q-mt-md justify-between">
              <q-btn
                flat
                color="primary"
                label="Use Current Location"
                icon="my_location"
                @click="useCurrentLocation"
                :disable="!geolocationAvailable"
              />
              <q-btn
                type="submit"
                color="accent"
                label="Add Favorite"
                icon="add_location"
                :disable="!isValidLocation"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";

const $q = useQuasar();
const showDialog = ref(false);
const favorites = ref([]);
const newLocation = ref("");
const newLatitude = ref("");
const newLongitude = ref("");
const geolocationAvailable = ref(false);

const emit = defineEmits(["select-location"]);

// Computed properties
const isValidLocation = computed(() => {
  return (
    newLocation.value &&
    newLatitude.value &&
    newLongitude.value &&
    newLatitude.value >= -90 &&
    newLatitude.value <= 90 &&
    newLongitude.value >= -180 &&
    newLongitude.value <= 180
  );
});

// Load favorites from localStorage on mount
onMounted(() => {
  loadFavorites();

  // Check if geolocation is available
  geolocationAvailable.value = "geolocation" in navigator;
});

// Methods
function formatCoordinates(lat, lng) {
  return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`;
}

function loadFavorites() {
  try {
    const savedFavorites = localStorage.getItem("favoriteLocations");
    if (savedFavorites) {
      favorites.value = JSON.parse(savedFavorites);
    }
  } catch (error) {
    console.error("Error loading favorites:", error);
    favorites.value = [];
  }
}

function saveFavorites() {
  try {
    localStorage.setItem("favoriteLocations", JSON.stringify(favorites.value));
  } catch (error) {
    console.error("Error saving favorites:", error);
    $q.notify({
      type: "negative",
      message: "Could not save favorites",
      position: "top",
      timeout: 2000,
    });
  }
}

function addLocation() {
  if (!isValidLocation.value) return;

  // Check if location already exists
  const exists = favorites.value.some(
    (fav) =>
      fav.name.toLowerCase() === newLocation.value.toLowerCase() ||
      (parseFloat(fav.latitude) === parseFloat(newLatitude.value) &&
        parseFloat(fav.longitude) === parseFloat(newLongitude.value))
  );

  if (exists) {
    $q.notify({
      type: "warning",
      message: "This location already exists in your favorites",
      position: "top",
      timeout: 2000,
    });
    return;
  }

  // Add new favorite
  favorites.value.push({
    name: newLocation.value,
    latitude: parseFloat(newLatitude.value),
    longitude: parseFloat(newLongitude.value),
  });

  // Save to localStorage
  saveFavorites();

  // Reset form
  newLocation.value = "";
  newLatitude.value = "";
  newLongitude.value = "";

  $q.notify({
    type: "positive",
    message: "Location added to favorites",
    position: "top",
    timeout: 2000,
  });
}

function confirmDelete(index) {
  $q.dialog({
    title: "Remove Favorite",
    message: `Remove ${favorites.value[index].name} from favorites?`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    favorites.value.splice(index, 1);
    saveFavorites();

    $q.notify({
      type: "info",
      message: "Location removed from favorites",
      position: "top",
      timeout: 2000,
    });
  });
}

function selectLocation(favorite) {
  emit("select-location", favorite);
  showDialog.value = false;
}

function useCurrentLocation() {
  if (!geolocationAvailable.value) return;

  navigator.geolocation.getCurrentPosition(
    (position) => {
      newLatitude.value = position.coords.latitude;
      newLongitude.value = position.coords.longitude;

      // If no location name provided, generate a generic one
      if (!newLocation.value) {
        newLocation.value = `My Location (${new Date().toLocaleDateString()})`;
      }

      $q.notify({
        type: "positive",
        message: "Current location detected",
        position: "top",
        timeout: 2000,
      });
    },
    (error) => {
      console.error("Geolocation error:", error);
      $q.notify({
        type: "negative",
        message:
          "Could not detect location. Please check your browser permissions.",
        position: "top",
        timeout: 3000,
      });
    }
  );
}

// Expose methods to parent component
defineExpose({
  showFavorites: () => {
    showDialog.value = true;
  },
});
</script>

<style scoped>
.favorites-card {
  border: 4px solid var(--border-color) !important;
  background-color: var(--paper-bg) !important;
  color: var(--text-color) !important;
}

.pixelated-heading {
  font-family: "VT323", monospace;
  letter-spacing: 1px;
  color: var(--text-color);
}
</style>
