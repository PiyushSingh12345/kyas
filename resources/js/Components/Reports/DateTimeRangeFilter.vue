<template>
  <template v-if="layout === 'row'">
    <div :class="colClass">
      <label :for="`${idPrefix}DateFrom`" class="form-label" :class="labelClass">Date From</label>
      <input
        :id="`${idPrefix}DateFrom`"
        type="date"
        class="form-control"
        :class="inputClass"
        :value="dateFrom"
        @input="$emit('update:dateFrom', $event.target.value)"
      />
    </div>
    <div :class="colClass">
      <label :for="`${idPrefix}TimeFrom`" class="form-label" :class="labelClass">Time From</label>
      <input
        :id="`${idPrefix}TimeFrom`"
        type="time"
        class="form-control"
        :class="inputClass"
        :value="timeFrom"
        step="1"
        @input="$emit('update:timeFrom', $event.target.value)"
      />
    </div>
    <div :class="colClass">
      <label :for="`${idPrefix}DateTo`" class="form-label" :class="labelClass">Date To</label>
      <input
        :id="`${idPrefix}DateTo`"
        type="date"
        class="form-control"
        :class="inputClass"
        :value="dateTo"
        @input="$emit('update:dateTo', $event.target.value)"
      />
    </div>
    <div :class="colClass">
      <label :for="`${idPrefix}TimeTo`" class="form-label" :class="labelClass">Time To</label>
      <input
        :id="`${idPrefix}TimeTo`"
        type="time"
        class="form-control"
        :class="inputClass"
        :value="timeTo"
        step="1"
        @input="$emit('update:timeTo', $event.target.value)"
      />
    </div>
  </template>
</template>

<script setup>
defineProps({
  dateFrom: { type: String, default: '' },
  timeFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  timeTo: { type: String, default: '' },
  colClass: { type: String, default: 'col-md-3' },
  labelClass: { type: String, default: 'fw-bold' },
  inputClass: { type: String, default: '' },
  idPrefix: { type: String, default: 'dtRange' },
  layout: { type: String, default: 'row' },
})

defineEmits(['update:dateFrom', 'update:timeFrom', 'update:dateTo', 'update:timeTo'])
</script>
