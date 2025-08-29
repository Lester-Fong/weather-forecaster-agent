<template>
  <q-layout view="lHh Lpr lFf" class="stardew-theme">
    <q-header elevated class="bg-secondary text-white pixelated-border">
      <q-toolbar>
        <q-toolbar-title class="pixelated-text text-center">
          <span class="text-accent">☀️</span>
          Weather Forecaster
          <span class="text-accent">☔</span>
        </q-toolbar-title>
      </q-toolbar>
    </q-header>

    <q-page-container class="stardew-background" :style="backgroundStyle">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import "../css/app-theme.css";

// Generate a random number between 1 and 11 for the background
const backgroundNumber = ref(1);

// Select random background on page load
onMounted(() => {
  backgroundNumber.value = Math.floor(Math.random() * 11) + 1;

  // Log the selected background
  console.log(`Using background: ${backgroundNumber.value}.jpg`);
});

// Compute the background style based on the selected background number
const backgroundStyle = computed(() => {
  // Handle the special case of 6.png (all others are jpg)
  const extension = backgroundNumber.value === 6 ? "png" : "jpg";
  return {
    backgroundImage: `url('/assets/${backgroundNumber.value}.${extension}')`,
    backgroundSize: "cover",
    backgroundPosition: "center",
    backgroundRepeat: "no-repeat",
  };
});
</script>

<style>
/* Styles moved to external CSS file */
</style>
