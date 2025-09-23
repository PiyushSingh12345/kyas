<template>
  <div v-if="hasAccess">
    <slot />
  </div>
  <div v-else-if="showFallback" class="access-denied">
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-triangle me-2"></i>
      You do not have permission to access this content.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoleAccess } from '../Composables/useRoleAccess'

const props = defineProps({
  roles: {
    type: [Array, Number],
    required: true
  },
  requireAll: {
    type: Boolean,
    default: false
  },
  showFallback: {
    type: Boolean,
    default: false
  }
})

const { hasRole, hasAllRoles } = useRoleAccess()

const hasAccess = computed(() => {
  if (props.requireAll) {
    return hasAllRoles(props.roles)
  }
  return hasRole(props.roles)
})
</script>

<style scoped>
.access-denied {
  padding: 20px;
  text-align: center;
}

.alert {
  border-radius: 8px;
  padding: 15px;
  margin: 10px 0;
}
</style>
