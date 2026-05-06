<template>
  <div class="overflow-x-auto">
    <table class="min-w-full border-collapse">
      <thead>
        <tr class="bg-gray-100">
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
        <tr v-for="(row, i) in rows" :key="i" class="border-t" :class="{'bg-red-50': rowHasError(i)}">
          <td class="p-2 border">
            <input type="date" v-model="row.date" class="w-full border rounded" @keydown.enter.prevent="addRow" @keydown.tab.prevent="focusNext($event)" @input="clearError(i, 'date')" :aria-invalid="!!fieldError(i, 'date')" :aria-describedby="fieldError(i, 'date') ? `error-${i}-date` : undefined" :class="{'border-red-500': fieldError(i, 'date')}" />
            <p v-if="fieldError(i, 'date')" :id="`error-${i}-date`" class="text-sm text-red-600" role="alert">{{ fieldError(i, 'date')?.[0] }}</p>
          </td>
          <td class="p-2 border">
              <select v-model.number="row.employee_id" class="w-full border rounded" @keydown.enter.prevent="addRow" @keydown.tab.prevent="focusNext($event)" @change="clearError(i, 'employee_id')" :aria-invalid="!!fieldError(i, 'employee_id')" :aria-describedby="fieldError(i, 'employee_id') ? `error-${i}-employee` : undefined" :class="{'border-red-500': fieldError(i, 'employee_id')}">
              <option value="" disabled>Select employee</option>
              <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option>
            </select>
            <p v-if="fieldError(i, 'employee_id')" :id="`error-${i}-employee`" class="text-sm text-red-600" role="alert">{{ fieldError(i, 'employee_id')?.[0] }}</p>
          </td>
          <td class="p-2 border">
              <select v-model.number="row.project_id" class="w-full border rounded" @keydown.enter.prevent="addRow" @keydown.tab.prevent="focusNext($event)" @change="clearError(i, 'project_id')" :aria-invalid="!!fieldError(i, 'project_id')" :aria-describedby="fieldError(i, 'project_id') ? `error-${i}-project` : undefined" :class="{'border-red-500': fieldError(i, 'project_id')}">
              <option value="" disabled>Select project</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <p v-if="fieldError(i, 'project_id')" :id="`error-${i}-project`" class="text-sm text-red-600" role="alert">{{ fieldError(i, 'project_id')?.[0] }}</p>
          </td>
          <td class="p-2 border">
              <select v-model.number="row.task_id" class="w-full border rounded" @keydown.enter.prevent="addRow" @keydown.tab.prevent="focusNext($event)" @change="clearError(i, 'task_id')" :aria-invalid="!!fieldError(i, 'task_id')" :aria-describedby="fieldError(i, 'task_id') ? `error-${i}-task` : undefined" :class="{'border-red-500': fieldError(i, 'task_id')}">
              <option value="" disabled>Select task</option>
              <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <p v-if="fieldError(i, 'task_id')" :id="`error-${i}-task`" class="text-sm text-red-600" role="alert">{{ fieldError(i, 'task_id')?.[0] }}</p>
          </td>
          <td class="p-2 border">
            <input type="number" step="0.25" min="0" v-model="row.hours" class="w-full border rounded" @keydown.enter.prevent="addRow" @keydown.tab.prevent="focusNext($event)" @input="clearError(i, 'hours')" :aria-invalid="!!fieldError(i, 'hours')" :aria-describedby="fieldError(i, 'hours') ? `error-${i}-hours` : undefined" :class="{'border-red-500': fieldError(i, 'hours')}" />
            <p v-if="fieldError(i, 'hours')" :id="`error-${i}-hours`" class="text-sm text-red-600" role="alert">{{ fieldError(i, 'hours')?.[0] }}</p>
          </td>
          <td class="p-2 border">
            <textarea v-model="row.description" class="w-full border rounded" rows="1" @keydown.tab.prevent="focusNext($event)"></textarea>
          </td>
          <td class="p-2 border text-center">
            <button type="button" @click="duplicateRow(i)" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300" title="Duplicate row">
              ⧉
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <div class="mt-4 flex justify-end">
      <button @click="submitAll" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        {{ loading ? 'Saving...' : 'Save All' }}
      </button>
    </div>
    <p v-if="globalError" class="mt-2 text-red-600" role="alert">{{ globalError }}</p>
    <p v-if="success" class="mt-2 text-green-600">Saved!</p>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

// option data
const employees = ref<Array<any>>([]);
const projects = ref<Array<any>>([]);
const tasks = ref<Array<any>>([]);

/**
 * Simple client‑side cache for dropdown options.
 * Stores data in localStorage with a timestamp. TTL is 60 minutes.
 */
function getCached(key: string) {
  const raw = localStorage.getItem(key);
  if (!raw) return null;
  try {
    const { data, ts } = JSON.parse(raw);
    // 60 minutes TTL
    if (Date.now() - ts < 60 * 60 * 1000) return data;
  } catch (_) {}
  return null;
}

function setCached(key: string, data: any) {
  const payload = { data, ts: Date.now() };
  localStorage.setItem(key, JSON.stringify(payload));
}

async function fetchOptions() {
  // Try to load from cache first
  const cachedEmployees = getCached('options:employees');
  const cachedProjects = getCached('options:projects');
  const cachedTasks = getCached('options:tasks');

  if (cachedEmployees && cachedProjects && cachedTasks) {
    employees.value = cachedEmployees;
    projects.value = cachedProjects;
    tasks.value = cachedTasks;
    return;
  }

  const [eRes, pRes, tRes] = await Promise.all([
    axios.get('/api/options/employees'),
    axios.get('/api/options/projects'),
    axios.get('/api/options/tasks'),
  ]);
  employees.value = eRes.data;
  projects.value = pRes.data;
  tasks.value = tRes.data;

  // Store in cache for future loads
  setCached('options:employees', employees.value);
  setCached('options:projects', projects.value);
  setCached('options:tasks', tasks.value);
}

onMounted(fetchOptions);

// rows for batch entry
// Define the shape of a row for better type safety. Using `null` for optional fields
// aligns with Vue's `<input type="date">` which can emit `null` when cleared.
interface TimeEntryRow {
  date: string | null;
  employee_id: number | null;
  project_id: number | null;
  task_id: number | null;
  hours: string;
  description: string;
}

const rows = ref<Array<TimeEntryRow>>([
  { date: null, employee_id: null, project_id: null, task_id: null, hours: '', description: '' },
]);

const loading = ref(false);
const success = ref(false);
const globalError = ref('');
const errors = ref<Record<string, string[]>>({}); // keyed by "entries.{row}.{field}"

function fieldError(rowIndex: number, field: string): string[] | null {
  const key = `entries.${rowIndex}.${field}`;
  return errors.value[key] ?? null;
}

/**
 * Remove a specific field error for a given row.
 * This is used by the input/@input handlers to clear the error as the user edits the field.
 */
function clearError(rowIndex: number, field: string): void {
  const key = `entries.${rowIndex}.${field}`;
  if (key in errors.value) {
    // Delete the key and trigger reactivity by assigning a new object copy
    const { [key]: _, ...rest } = errors.value;
    errors.value = rest as Record<string, string[]>;
  }
}

/**
 * Determine whether a given row has any validation errors.
 * The server returns an `errors` object keyed by "entries.{row}.{field}".
 * If any key for the supplied row index exists, we consider the row to be in error state.
 */
function rowHasError(rowIndex: number): boolean {
  const prefix = `entries.${rowIndex}.`;
  return Object.keys(errors.value).some((k) => k.startsWith(prefix));
}

function addRow() {
  const last = rows.value[rows.value.length - 1];
  rows.value.push({
    date: last.date,
    employee_id: last.employee_id,
    project_id: last.project_id,
    task_id: null,
    hours: '',
    description: '',
  });
}

/**
 * Duplicate a row while preserving date, employee and project.
 * The task, hours and description are reset to allow fresh entry.
 */
function duplicateRow(index: number) {
  const src = rows.value[index];
  rows.value.splice(index + 1, 0, {
    date: src.date,
    employee_id: src.employee_id,
    project_id: src.project_id,
    task_id: null,
    hours: '',
    description: '',
  });
}

/**
 * Move focus to the next input/select/textarea in the table.
 * If the current element is the last one, a new row is added and focus moves to its first field.
 */
function focusNext(event: KeyboardEvent) {
  const current = event.target as HTMLElement;
  // Gather all focusable elements within the table body
  const focusable = Array.from(document.querySelectorAll('tbody input, tbody select, tbody textarea')) as HTMLElement[];
  const idx = focusable.indexOf(current);
  if (idx >= 0 && idx < focusable.length - 1) {
    focusable[idx + 1].focus();
  } else {
    // At the end – add a new row then focus the first input of that row
    addRow();
    // Wait for DOM update
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
  globalError.value = '';
  errors.value = {};
  try {
    await axios.post('/api/time-entries/batch', { entries: rows.value });
    success.value = true;
    rows.value = [
      { date: null, employee_id: null, project_id: null, task_id: null, hours: '', description: '' },
    ];
  } catch (e: any) {
    const resp = e.response?.data;
    if (resp?.errors) {
      errors.value = resp.errors;
    } else {
      globalError.value = resp?.message || 'Failed to save';
    }
  } finally {
    loading.value = false;
    setTimeout(() => (success.value = false), 3000);
  }
}

// Global Ctrl+S listener
function onKeyDown(event: KeyboardEvent) {
  if (event.ctrlKey && event.key === 's') {
    event.preventDefault();
    submitAll();
  }
}

onMounted(() => window.addEventListener('keydown', onKeyDown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeyDown));
</script>

<!-- Feature implementation completed: tab navigation, enter to add row, Ctrl+S submit, row duplication, inline error with accessibility -->

<style scoped>
/* Simple focus outline for accessibility */
input:focus,
select:focus,
textarea:focus {
  outline: 2px solid #2563eb; /* blue-600 */
  outline-offset: 2px;
}
</style>
