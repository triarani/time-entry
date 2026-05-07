<template>
  <div class="w-full">
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2 border">Company</th>
          <th class="p-2 border">Date</th>
          <th class="p-2 border">Employee</th>
          <th class="p-2 border">Project</th>
          <th class="p-2 border">Task</th>
          <th class="p-2 border">Hours</th>
          <th class="p-2 border">Notes</th>
          <th class="p-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, i) in rows" :key="i" class="border-t">
          <td class="p-2 border">
            <select
              v-model.number="row.company_id"
              class="w-full border rounded"
              @keydown.enter.prevent="addRow"
              @keydown.tab.prevent="focusNext($event)"
              @change="onCompanyChange(i)"
            >
              <option :value="null" disabled>Select company</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </td>
          <td class="p-2 border">
            <input type="date" v-model="row.date" class="w-full border rounded" @keydown.enter.prevent="addRow" @keydown.tab.prevent="focusNext($event)" />
          </td>
          <td class="p-2 border">
            <select
              v-model.number="row.employee_id"
              class="w-full border rounded"
              @keydown.enter.prevent="addRow"
              @keydown.tab.prevent="focusNext($event)"
              :disabled="!row.company_id"
            >
              <option :value="null" disabled>Select employee</option>
              <option v-for="e in getEmployeesForRow(i)" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option>
            </select>
          </td>
          <td class="p-2 border">
            <select
              v-model.number="row.project_id"
              class="w-full border rounded"
              @keydown.enter.prevent="addRow"
              @keydown.tab.prevent="focusNext($event)"
              :disabled="!row.company_id"
            >
              <option :value="null" disabled>Select project</option>
              <option v-for="p in getProjectsForRow(i)" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </td>
          <td class="p-2 border">
            <select
              v-model.number="row.task_id"
              class="w-full border rounded"
              @keydown.enter.prevent="addRow"
              @keydown.tab.prevent="focusNext($event)"
              :disabled="!row.company_id"
            >
              <option :value="null" disabled>Select task</option>
              <option v-for="t in getTasksForRow(i)" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </td>
          <td class="p-2 border">
            <input type="number" step="0.25" min="0" v-model="row.hours" class="w-full border rounded" @keydown.enter.prevent="addRow" @keydown.tab.prevent="focusNext($event)" />
          </td>
          <td class="p-2 border">
            <textarea v-model="row.description" class="w-full border rounded" rows="1" @keydown.tab.prevent="focusNext($event)"></textarea>
          </td>
          <td class="p-2 border text-center space-x-1">
            <button type="button" @click="duplicateRow(i)" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300" title="Duplicate row">
              ⧉
            </button>
            <button v-if="i > 0" type="button" @click="deleteRow(i)" class="px-2 py-1 bg-red-200 rounded hover:bg-red-300" title="Delete row">
              ✕
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-if="errorCount > 0" class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
      <div class="font-medium text-red-700 mb-2">Errors ({{ Object.keys(errors).length }}):</div>
      <ul class="text-sm text-red-600 space-y-1">
        <li v-for="(msgs, key) in errors" :key="key">
          <span v-if="key.includes('._')" class="font-medium">Row {{ parseInt(key.split('.')[1]) + 1 }}:</span>
          <span v-else class="font-medium">Row {{ parseInt(key.split('.')[1]) + 1 }} - {{ key.split('.')[2] }}:</span>
          {{ msgs[0] }}
        </li>
      </ul>
    </div>
    <div class="mt-4 flex items-center justify-end gap-4">
      <div v-if="loading" class="text-sm text-gray-600">
        Saving...
      </div>
      <div v-else-if="savedCount > 0" class="text-sm text-green-600">
        Saved {{ savedCount }} {{ savedCount === 1 ? 'entry' : 'entries' }}!
      </div>
      <button @click="submitAll" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        {{ loading ? 'Saving...' : 'Save All' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';

const errorCount = computed(() => Object.keys(errors.value).length);
import axios from 'axios';

const props = defineProps<{
  companyId: number | null;
}>();

const emit = defineEmits<{
  (e: 'refresh'): void;
}>();

const companies = ref<Array<any>>([]);
const allEmployees = ref<Array<any>>([]);
const allProjects = ref<Array<any>>([]);
const allTasks = ref<Array<any>>([]);

function getCached(key: string) {
  const raw = localStorage.getItem(key);
  if (!raw) return null;
  try {
    const { data, ts } = JSON.parse(raw);
    if (Date.now() - ts < 60 * 60 * 1000) return data;
  } catch (_) {}
  return null;
}

function setCached(key: string, data: any) {
  const payload = { data, ts: Date.now() };
  localStorage.setItem(key, JSON.stringify(payload));
}

async function fetchOptions() {
  const [cRes, eRes, pRes, tRes] = await Promise.all([
    axios.get('/api/options/companies'),
    axios.get('/api/options/employees'),
    axios.get('/api/options/projects'),
    axios.get('/api/options/tasks'),
  ]);
  companies.value = cRes.data;
  allEmployees.value = eRes.data;
  allProjects.value = pRes.data;
  allTasks.value = tRes.data;

  setCached('options:companies', companies.value);
  setCached('options:employees', allEmployees.value);
  setCached('options:projects', allProjects.value);
  setCached('options:tasks', allTasks.value);
}

function loadFromCache() {
  const cachedCompanies = getCached('options:companies');
  const cachedEmployees = getCached('options:employees');
  const cachedProjects = getCached('options:projects');
  const cachedTasks = getCached('options:tasks');

  if (cachedCompanies) companies.value = cachedCompanies;
  if (cachedEmployees) allEmployees.value = cachedEmployees;
  if (cachedProjects) allProjects.value = cachedProjects;
  if (cachedTasks) allTasks.value = cachedTasks;
}

function getEmployeesForRow(index: number): Array<any> {
  return allEmployees.value;
}

function getProjectsForRow(index: number): Array<any> {
  const row = rows.value[index];
  const companyId = row.company_id ?? props.companyId;
  if (!companyId) return [];
  return allProjects.value.filter(p => p.company_id === companyId);
}

function getTasksForRow(index: number): Array<any> {
  const row = rows.value[index];
  const companyId = row.company_id ?? props.companyId;
  if (!companyId) return [];
  return allTasks.value.filter(t => t.company_id === companyId);
}

onMounted(() => {
  loadFromCache();
  fetchOptions();
});

interface TimeEntryRow {
  company_id: number | null;
  date: string | null;
  employee_id: number | null;
  project_id: number | null;
  task_id: number | null;
  hours: string;
  description: string;
}

const rows = ref<Array<TimeEntryRow>>([
  { company_id: null, date: null, employee_id: null, project_id: null, task_id: null, hours: '', description: '' },
]);

const loading = ref(false);
const success = ref(false);
const globalError = ref('');
const errors = ref<Record<string, string[]>>({});
const savingIndex = ref(-1);
const savedCount = ref(0);
const savedRows = ref(new Set<number>());

function fieldError(rowIndex: number, field: string): string[] | null {
  const key = `entries.${rowIndex}.${field}`;
  return errors.value[key] ?? null;
}

function clearError(rowIndex: number, field: string): void {
  const key = `entries.${rowIndex}.${field}`;
  if (key in errors.value) {
    const { [key]: _, ...rest } = errors.value;
    errors.value = rest as Record<string, string[]>;
  }
}

function rowHasError(rowIndex: number): boolean {
  const prefix = `entries.${rowIndex}.`;
  return Object.keys(errors.value).some((k) => k.startsWith(prefix));
}

function onCompanyChange(index: number) {
  const row = rows.value[index];
  row.employee_id = null;
  row.project_id = null;
  row.task_id = null;
}

function addRow() {
  const last = rows.value[rows.value.length - 1];
  rows.value.push({
    company_id: last.company_id,
    date: last.date,
    employee_id: last.employee_id,
    project_id: last.project_id,
    task_id: null,
    hours: '',
    description: '',
  });
}

function duplicateRow(index: number) {
  const src = rows.value[index];
  rows.value.splice(index + 1, 0, {
    company_id: src.company_id,
    date: src.date,
    employee_id: src.employee_id,
    project_id: src.project_id,
    task_id: null,
    hours: '',
    description: '',
  });
}

function deleteRow(index: number) {
  rows.value.splice(index, 1);
}

function focusNext(event: KeyboardEvent) {
  const current = event.target as HTMLElement;
  const focusable = Array.from(document.querySelectorAll('tbody input, tbody select, tbody textarea')) as HTMLElement[];
  const idx = focusable.indexOf(current);
  if (idx >= 0 && idx < focusable.length - 1) {
    focusable[idx + 1].focus();
  } else {
    addRow();
    requestAnimationFrame(() => {
      const newFocusables = Array.from(document.querySelectorAll('tbody input, tbody select, tbody textarea')) as HTMLElement[];
      if (newFocusables.length > 0) {
        newFocusables[newFocusables.length - focusable.length].focus();
      }
    });
  }
}

async function submitAll() {
  loading.value = true;
  savingIndex.value = 0;
  globalError.value = '';
  errors.value = {};
  savedCount.value = 0;
  savedRows.value = new Set();

  const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

  for (let i = 0; i < rows.value.length; i++) {
    const row = rows.value[i];
    if (!row.company_id || !row.employee_id || !row.project_id || !row.task_id || !row.hours) {
      continue;
    }

    savingIndex.value = i;

    try {
      await axios.post('/api/time-entries', {
        employee_id: row.employee_id,
        project_id: row.project_id,
        task_id: row.task_id,
        date: row.date,
        hours: row.hours,
        notes: row.description,
      });
      savedCount.value++;
      savedRows.value.add(i);
      await delay(500);
    } catch (e: any) {
      const resp = e.response?.data;
      if (resp?.errors) {
        const formatted: Record<string, string[]> = {};
        Object.keys(resp.errors).forEach(key => {
          formatted[`entries.${i}.${key}`] = resp.errors[key];
        });
        errors.value = { ...errors.value, ...formatted };
      } else {
        errors.value['entries.' + i + '._'] = [resp?.message || 'Failed to save'];
      }
    }
  }

  loading.value = false;

  if (savedCount.value > 0 && Object.keys(errors.value).length === 0) {
    success.value = true;
    rows.value = [
      { company_id: null, date: null, employee_id: null, project_id: null, task_id: null, hours: '', description: '' },
    ];
    emit('refresh');
    setTimeout(() => (success.value = false), 3000);
  }
}

function onKeyDown(event: KeyboardEvent) {
  if (event.ctrlKey && event.key === 's') {
    event.preventDefault();
    submitAll();
  }
}

onMounted(() => window.addEventListener('keydown', onKeyDown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeyDown));
</script>

<style scoped>
input:focus,
select:focus,
textarea:focus {
  outline: 2px solid #2563eb;
  outline-offset: 2px;
}
</style>