<template>
  <div class="weather-alerts">
    <q-dialog v-model="showDialog" persistent>
      <q-card
        class="alerts-card stardew-paper"
        style="width: 90%; max-width: 500px"
      >
        <q-card-section class="row items-center">
          <div class="text-h6 pixelated-heading">Weather Alerts</div>
          <q-space></q-space>
          <q-btn icon="close" flat round dense v-close-popup></q-btn>
        </q-card-section>

        <q-card-section>
          <div v-if="alerts.length === 0" class="text-center q-pa-md">
            <p>No weather alerts configured.</p>
            <p class="text-grey-7">
              Set up alerts to be notified of specific weather conditions.
            </p>
          </div>

          <q-list v-else bordered separator>
            <q-item v-for="(alert, index) in alerts" :key="index">
              <q-item-section avatar>
                <q-icon
                  :name="getAlertIcon(alert.condition)"
                  color="negative"
                />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ alert.location }}</q-item-label>
                <q-item-label caption>
                  <span class="alert-condition">{{
                    formatCondition(alert.condition)
                  }}</span>
                  <span v-if="alert.threshold">
                    -
                    {{
                      formatThreshold(alert.condition, alert.threshold)
                    }}</span
                  >
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-toggle v-model="alert.active" color="positive" />
              </q-item-section>
              <q-item-section side>
                <q-btn
                  flat
                  round
                  dense
                  icon="delete"
                  color="negative"
                  @click="confirmDelete(index)"
                >
                  <q-tooltip>Remove alert</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-section>
          <q-separator class="q-mb-md"></q-separator>
          <div class="text-subtitle2">Add new alert</div>
          <q-form @submit="addAlert" class="q-mt-sm">
            <q-input
              v-model="newLocation"
              label="Location"
              dense
              outlined
              class="stardew-input q-mb-sm"
              :rules="[(val) => !!val || 'Location is required']"
            />

            <q-select
              v-model="newCondition"
              :options="conditionOptions"
              label="Weather Condition"
              dense
              outlined
              class="stardew-input q-mb-sm"
              emit-value
              map-options
              :rules="[(val) => !!val || 'Condition is required']"
            />

            <q-input
              v-if="needsThreshold(newCondition)"
              v-model="newThreshold"
              :label="getThresholdLabel(newCondition)"
              type="number"
              dense
              outlined
              class="stardew-input q-mb-sm"
              :rules="[
                (val) =>
                  needsThreshold(newCondition)
                    ? !!val || 'Threshold is required'
                    : true,
              ]"
            />

            <div class="row q-mt-md justify-end">
              <q-btn
                type="submit"
                color="accent"
                label="Add Alert"
                icon="add_alert"
                :disable="!isValidAlert"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";

const $q = useQuasar();
const showDialog = ref(false);
const alerts = ref([]);
const newLocation = ref("");
const newCondition = ref(null);
const newThreshold = ref("");

// Weather condition options
const conditionOptions = [
  { label: "Rain", value: "rain" },
  { label: "Snow", value: "snow" },
  { label: "High Temperature", value: "highTemp" },
  { label: "Low Temperature", value: "lowTemp" },
  { label: "Wind Speed", value: "wind" },
  { label: "Storm", value: "storm" },
  { label: "Extreme Weather", value: "extreme" },
];

// Computed properties
const isValidAlert = computed(() => {
  // Basic validation
  if (!newLocation.value || !newCondition.value) {
    return false;
  }

  // Check if threshold is needed and provided
  if (needsThreshold(newCondition.value)) {
    return !!newThreshold.value;
  }

  return true;
});

// Watch for changes in condition to reset threshold when changing conditions
watch(newCondition, (newVal) => {
  if (!needsThreshold(newVal)) {
    newThreshold.value = "";
  }
});

// Load alerts from localStorage on mount
onMounted(() => {
  loadAlerts();
});

// Methods
function formatCondition(condition) {
  const option = conditionOptions.find((opt) => opt.value === condition);
  return option ? option.label : condition;
}

function formatThreshold(condition, threshold) {
  switch (condition) {
    case "highTemp":
      return `Above ${threshold}°C`;
    case "lowTemp":
      return `Below ${threshold}°C`;
    case "wind":
      return `Above ${threshold} km/h`;
    default:
      return threshold;
  }
}

function getAlertIcon(condition) {
  switch (condition) {
    case "rain":
      return "water_drop";
    case "snow":
      return "ac_unit";
    case "highTemp":
      return "thermostat";
    case "lowTemp":
      return "ac_unit";
    case "wind":
      return "air";
    case "storm":
      return "thunderstorm";
    case "extreme":
      return "warning";
    default:
      return "notifications";
  }
}

function needsThreshold(condition) {
  return ["highTemp", "lowTemp", "wind"].includes(condition);
}

function getThresholdLabel(condition) {
  switch (condition) {
    case "highTemp":
      return "Temperature threshold (°C)";
    case "lowTemp":
      return "Temperature threshold (°C)";
    case "wind":
      return "Wind speed threshold (km/h)";
    default:
      return "Threshold";
  }
}

function loadAlerts() {
  try {
    const savedAlerts = localStorage.getItem("weatherAlerts");
    if (savedAlerts) {
      alerts.value = JSON.parse(savedAlerts);
    }
  } catch (error) {
    console.error("Error loading alerts:", error);
    alerts.value = [];
  }
}

function saveAlerts() {
  try {
    localStorage.setItem("weatherAlerts", JSON.stringify(alerts.value));
  } catch (error) {
    console.error("Error saving alerts:", error);
    $q.notify({
      type: "negative",
      message: "Could not save alerts",
      position: "top",
      timeout: 2000,
    });
  }
}

function addAlert() {
  if (!isValidAlert.value) return;

  // Add new alert
  alerts.value.push({
    location: newLocation.value,
    condition: newCondition.value,
    threshold: needsThreshold(newCondition.value)
      ? parseFloat(newThreshold.value)
      : null,
    active: true,
    createdAt: Date.now(),
  });

  // Save to localStorage
  saveAlerts();

  // Reset form
  newLocation.value = "";
  newCondition.value = null;
  newThreshold.value = "";

  $q.notify({
    type: "positive",
    message: "Weather alert added",
    position: "top",
    timeout: 2000,
  });
}

function confirmDelete(index) {
  $q.dialog({
    title: "Remove Alert",
    message: `Remove this weather alert for ${alerts.value[index].location}?`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    alerts.value.splice(index, 1);
    saveAlerts();

    $q.notify({
      type: "info",
      message: "Weather alert removed",
      position: "top",
      timeout: 2000,
    });
  });
}

// Expose methods to parent component
defineExpose({
  showAlerts: () => {
    showDialog.value = true;
  },
  checkAlerts: (weatherData) => {
    // This would be called when new weather data is received
    // to check if any alerts should be triggered
    if (!weatherData || !weatherData.location) return;

    const activeAlerts = alerts.value.filter((alert) => alert.active);

    for (const alert of activeAlerts) {
      if (alert.location.toLowerCase() === weatherData.location.toLowerCase()) {
        // Check if condition matches
        let shouldAlert = false;

        switch (alert.condition) {
          case "rain":
            shouldAlert = weatherData.precipitation > 0;
            break;
          case "snow":
            shouldAlert = weatherData.snowfall > 0;
            break;
          case "highTemp":
            shouldAlert = weatherData.temperature > alert.threshold;
            break;
          case "lowTemp":
            shouldAlert = weatherData.temperature < alert.threshold;
            break;
          case "wind":
            shouldAlert = weatherData.windSpeed > alert.threshold;
            break;
          case "storm":
            shouldAlert = weatherData.isStorm;
            break;
          case "extreme":
            shouldAlert = weatherData.isExtreme;
            break;
        }

        if (shouldAlert) {
          // Trigger alert notification
          $q.notify({
            type: "warning",
            message: `Weather Alert: ${formatCondition(alert.condition)} in ${
              alert.location
            }`,
            position: "top",
            timeout: 5000,
            actions: [{ label: "Dismiss", color: "white" }],
          });
        }
      }
    }
  },
});
</script>

<style scoped>
.alerts-card {
  border: 4px solid var(--border-color) !important;
  background-color: var(--paper-bg) !important;
  color: var(--text-color) !important;
}

.pixelated-heading {
  font-family: "VT323", monospace;
  letter-spacing: 1px;
  color: var(--text-color);
}

.alert-condition {
  font-weight: bold;
}
</style>
