<template>
  <!-- Centered container with max width 980px -->
  <div class="max-w-187.5 mx-auto p-4 bg-white dark:bg-gray-800 rounded shadow">
    <h2 class="text-lg font-medium mb-2">History</h2>
    <div v-if="loading" class="text-sm text-gray-600">Loading...</div>
    <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>
    <ul v-else class="space-y-2">
      <li v-for="entry in entries" :key="entry.id" class="border-b pb-2">
        <div class="flex justify-between items-center">
          <div>
            <span class="font-medium">{{ entry.date }}</span>
            – <span>{{ entry.hours }}h</span>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ entry.description }}</p>
          </div>
          <div class="flex space-x-2">
            <button @click="edit(entry)" class="text-xs text-blue-600 hover:underline">Edit</button>
            <button @click="remove(entry.id)" class="text-xs text-red-600 hover:underline">Delete</button>
          </div>
        </div>
      </li>
      <li v-if="entries.length === 0" class="text-sm text-gray-600">No entries yet.</li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Entry {
  id: number;
  date: string;
  hours: number;
  description: string | null;
}

const entries = ref<Entry[]>([]);
const loading = ref(false);
const error = ref('');

async function fetchEntries() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.get('/api/time-entries');
    entries.value = data;
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to load entries';
  } finally {
    loading.value = false;
  }
}

async function remove(id: number) {
  if (!confirm('Delete this entry?')) return;
  try {
    await axios.delete(`/api/time-entries/${id}`);
    entries.value = entries.value.filter(e => e.id !== id);
  } catch (e: any) {
    alert(e.response?.data?.message || 'Delete failed');
  }
}

function edit(entry: Entry) {
  // placeholder – in a real app you'd open a modal or populate NewEntries form
  alert('Edit not implemented in this demo');
}

onMounted(fetchEntries);
</script>
