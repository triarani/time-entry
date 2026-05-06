<template>
  <div class="flex flex-col space-y-4 p-4">
    <NewEntries />
    <History />
  </div>
</template>

<script setup>
import NewEntries from './components/NewEntries.vue';
import History from './components/History.vue';
</script>
