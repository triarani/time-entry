<template>
  <div class="max-w-187.5 mx-auto p-4 bg-white dark:bg-gray-800 rounded shadow">
    <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">New Time Entry</h2>
    <TimeEntryTable :company-id="companyId" @refresh="$emit('refresh')" />
  </div>
</template>

<script setup lang="ts">
import TimeEntryTable from './TimeEntryTable.vue';

defineProps<{
  companyId: number | null;
}>();

defineEmits<{
  (e: 'refresh'): void;
}>();
</script>