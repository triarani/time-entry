import './bootstrap';
import { createApp, ref } from 'vue';
import NewEntries from './components/NewEntries.vue';
import History from './components/History.vue';

const AppRoot = {
  components: { NewEntries, History },
  setup() {
    const activeTab = ref('new');
    const setTab = (tab) => { activeTab.value = tab; };
    return { activeTab, setTab };
  },
  // Tailwind‑styled tab navigation and content area
  template: `
    <div class="flex space-x-2 mb-6">
      <button
        @click="setTab('new')"
        :class="[activeTab==='new' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200', 'px-4 py-2 rounded hover:bg-blue-500']"
      >New Entries</button>
      <button
        @click="setTab('history')"
        :class="[activeTab==='history' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200', 'px-4 py-2 rounded hover:bg-blue-500']"
      >History</button>
    </div>
    <component :is="activeTab === 'new' ? 'NewEntries' : 'History'" />
  `,
};

createApp(AppRoot).mount('#app');
