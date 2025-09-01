<template>
  <div class="voice-input">
    <q-btn
      round
      :color="isListening ? 'negative' : 'primary'"
      :icon="isListening ? 'mic' : 'mic_none'"
      @click="toggleListening"
      :class="{ 'pulse-animation': isListening }"
    >
      <q-tooltip>{{
        isListening ? "Stop Recording" : "Voice Input"
      }}</q-tooltip>
    </q-btn>

    <div v-if="isListening" class="transcript">
      <p>{{ transcript || "Listening..." }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
  placeholder: {
    type: String,
    default: "Speak to ask about the weather...",
  },
});

const emit = defineEmits(["input"]);

const isListening = ref(false);
const transcript = ref("");
const recognition = ref(null);

// Initialize speech recognition on component mount
onMounted(() => {
  // Check if browser supports SpeechRecognition
  if ("SpeechRecognition" in window || "webkitSpeechRecognition" in window) {
    const SpeechRecognition =
      window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition.value = new SpeechRecognition();

    // Configure speech recognition
    recognition.value.continuous = false;
    recognition.value.interimResults = true;
    recognition.value.lang = "en-US";

    // Set up event handlers
    recognition.value.onstart = () => {
      isListening.value = true;
      transcript.value = "";
    };

    recognition.value.onresult = (event) => {
      // Get the latest transcript
      const current = event.resultIndex;
      const result = event.results[current];
      const text = result[0].transcript;

      // Update the transcript
      transcript.value = text;

      // If this is a final result, stop listening and emit the input
      if (result.isFinal) {
        stopListening();
        emit("input", text);
      }
    };

    recognition.value.onerror = (event) => {
      console.error("Speech recognition error:", event.error);
      isListening.value = false;
    };

    recognition.value.onend = () => {
      isListening.value = false;
    };
  }
});

// Clean up on component unmount
onUnmounted(() => {
  if (recognition.value && isListening.value) {
    recognition.value.stop();
  }
});

// Methods
const toggleListening = () => {
  if (isListening.value) {
    stopListening();
  } else {
    startListening();
  }
};

const startListening = () => {
  if (recognition.value) {
    try {
      recognition.value.start();
    } catch (e) {
      console.error("Speech recognition error:", e);
    }
  } else {
    console.error("Speech recognition not supported in this browser");
    alert("Speech recognition is not supported in your browser.");
  }
};

const stopListening = () => {
  if (recognition.value) {
    recognition.value.stop();
  }
};
</script>

<style scoped>
.voice-input {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.transcript {
  margin-top: 8px;
  padding: 8px;
  background-color: var(--paper-bg);
  border-radius: 4px;
  max-width: 100%;
  text-align: center;
  border: 1px solid var(--border-color);
}

.pulse-animation {
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
