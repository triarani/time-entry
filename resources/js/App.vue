<template>
  <div class="flex flex-col space-y-4 p-4 w-full">
    <div class="flex items-center gap-4 bg-white dark:bg-gray-800 p-4 rounded shadow">
      <label class="font-medium text-gray-700 dark:text-gray-300">Company:</label>
      <select
        v-model="selectedCompany"
        class="px-3 py-2 border rounded bg-gray-50 dark:bg-gray-700"
      >
        <option :value="null">All</option>
        <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>
    <NewEntries :company-id="selectedCompany" @refresh="onRefresh" />
    <History :key="historyKey" :company-id="selectedCompany" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import NewEntries from './components/NewEntries.vue';
import History from './components/History.vue';

const selectedCompany = ref(null);
const companies = ref([]);
const historyKey = ref(0);

async function fetchCompanies() {
  const { data } = await axios.get('/api/options/companies');
  companies.value = data;
}

function onRefresh() {
  historyKey.value++;
}

onMounted(fetchCompanies);
</script>