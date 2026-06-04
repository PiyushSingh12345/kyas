<template>
  <div :class="colClass">
    <label :for="inputId" class="form-label" :class="labelClass">Amount In</label>
    <select
      :id="inputId"
      class="form-select"
      :class="selectClass"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-for="opt in AMOUNT_IN_OPTIONS" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
  </div>
</template>

<script setup>
import { AMOUNT_IN_OPTIONS } from '../../utils/amountFormat'

defineProps({
  modelValue: { type: String, default: 'Lakh' },
  colClass: { type: String, default: 'col-md-3' },
  selectClass: { type: String, default: '' },
  labelClass: { type: String, default: 'fw-bold' },
  inputId: { type: String, default: 'amountIn' },
})

defineEmits(['update:modelValue'])
</script>
