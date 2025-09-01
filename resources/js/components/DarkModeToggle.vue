<template>
  <q-btn
    flat
    round
    :icon="isDarkMode ? 'light_mode' : 'dark_mode'"
    :color="isDarkMode ? 'warning' : ''"
    @click="toggleDarkMode"
    class="dark-mode-toggle"
  >
    <q-tooltip>{{
      isDarkMode ? "Switch to Light Mode" : "Switch to Dark Mode"
    }}</q-tooltip>
  </q-btn>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useQuasar } from "quasar";

const $q = useQuasar();
const isDarkMode = ref(false);

// Toggle dark mode
const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;

  // Save preference to localStorage
  localStorage.setItem("darkMode", isDarkMode.value ? "true" : "false");

  // Apply dark mode to the Quasar framework
  $q.dark.set(isDarkMode.value);

  // Add/remove dark class to the document body
  if (isDarkMode.value) {
    document.body.classList.add("dark-mode");
  } else {
    document.body.classList.remove("dark-mode");
  }
};

// Initialize dark mode based on user preference
onMounted(() => {
  // Check for saved preference in localStorage
  const savedDarkMode = localStorage.getItem("darkMode") === "true";

  // Set initial state
  isDarkMode.value = savedDarkMode;

  // Apply saved preference
  if (savedDarkMode) {
    $q.dark.set(true);
    document.body.classList.add("dark-mode");
  } else {
    $q.dark.set(false);
    document.body.classList.remove("dark-mode");
  }

  // Listen for system preference changes
  const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");

  // Only apply system preference if no user preference is saved
  if (localStorage.getItem("darkMode") === null) {
    isDarkMode.value = mediaQuery.matches;
    $q.dark.set(mediaQuery.matches);
    if (mediaQuery.matches) {
      document.body.classList.add("dark-mode");
    }
  }

  // Listen for changes to system preference
  mediaQuery.addEventListener("change", (e) => {
    // Only apply system preference if no user preference is saved
    if (localStorage.getItem("darkMode") === null) {
      isDarkMode.value = e.matches;
      $q.dark.set(e.matches);
      if (e.matches) {
        document.body.classList.add("dark-mode");
      } else {
        document.body.classList.remove("dark-mode");
      }
    }
  });
});
</script>

<style scoped>
.dark-mode-toggle {
  transition: all 0.3s ease;
}
</style>
