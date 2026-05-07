<template>
  <div class="flex flex-col space-y-4 p-4 w-full">
    <div class="flex items-center gap-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
      <label class="font-semibold text-gray-700 dark:text-gray-200">Company:</label>
      <select
        v-model="selectedCompany"
        class="px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 shadow-sm min-w-48"
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