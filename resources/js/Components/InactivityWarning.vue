<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
    timeoutMinutes: {
        type: Number,
        default: 12,
    },
});

const secondsRemaining = ref(0);
const showWarning = ref(false);

const resolvedTimeoutMinutes = computed(() => {
    const value = Number(props.timeoutMinutes ?? 12);
    return Number.isFinite(value) && value > 0 ? value : 12;
});

const warningLeadSeconds = 60;
const isAuthenticated = computed(() => props.isAuthenticated);

let warningTimerId = null;
let logoutTimerId = null;
let countdownIntervalId = null;

const clearTimers = () => {
    if (warningTimerId) {
        window.clearTimeout(warningTimerId);
        warningTimerId = null;
    }
    if (logoutTimerId) {
        window.clearTimeout(logoutTimerId);
        logoutTimerId = null;
    }
    if (countdownIntervalId) {
        window.clearInterval(countdownIntervalId);
        countdownIntervalId = null;
    }
};

const hideWarning = () => {
    showWarning.value = false;
    secondsRemaining.value = 0;
    if (countdownIntervalId) {
        window.clearInterval(countdownIntervalId);
        countdownIntervalId = null;
    }
};

const startWarningCountdown = () => {
    showWarning.value = true;
    secondsRemaining.value = warningLeadSeconds;

    if (countdownIntervalId) {
        window.clearInterval(countdownIntervalId);
    }

    countdownIntervalId = window.setInterval(() => {
        if (secondsRemaining.value > 0) {
            secondsRemaining.value -= 1;
        }
    }, 1000);
};

const restartInactivityTimers = () => {
    if (!props.isAuthenticated) {
        clearTimers();
        hideWarning();
        return;
    }

    clearTimers();
    hideWarning();

    const timeoutSeconds = Math.max(1, resolvedTimeoutMinutes.value * 60);
    const warningDelayMs = Math.max(0, (timeoutSeconds - warningLeadSeconds) * 1000);
    const logoutDelayMs = timeoutSeconds * 1000;

    warningTimerId = window.setTimeout(startWarningCountdown, warningDelayMs);
    logoutTimerId = window.setTimeout(() => {
        // Let backend middleware own the final timeout decision and redirect/message.
        window.location.reload();
    }, logoutDelayMs);
};

const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'];
const handleUserActivity = () => restartInactivityTimers();

onMounted(() => {
    activityEvents.forEach((eventName) => {
        window.addEventListener(eventName, handleUserActivity, { passive: true });
    });
    restartInactivityTimers();
});

onBeforeUnmount(() => {
    activityEvents.forEach((eventName) => {
        window.removeEventListener(eventName, handleUserActivity);
    });
    clearTimers();
});

watch(
    () => [props.isAuthenticated, resolvedTimeoutMinutes.value],
    () => restartInactivityTimers(),
);
</script>

<template>
    <div
        v-if="showWarning && isAuthenticated"
        class="position-fixed top-0 start-50 translate-middle-x mt-3 alert alert-warning shadow-sm"
        style="z-index: 2000; min-width: 360px;"
        role="alert"
    >
        <strong>Inactivity warning:</strong>
        Your session will expire in {{ secondsRemaining }} seconds.
    </div>
</template>
