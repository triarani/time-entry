<template>
  <div class="max-w-187.5 mx-auto p-4 bg-white dark:bg-gray-800 rounded shadow">
    <h2 class="text-lg font-medium mb-4">History</h2>

    <div class="mb-4 flex flex-wrap gap-2 items-center">
      <input
        v-model="search"
        type="text"
        placeholder="Search..."
        class="px-3 py-2 border rounded text-sm"
      />
      <select v-model="filterEmployee" class="px-3 py-2 border rounded text-sm">
        <option value="">All Employees</option>
        <option v-for="e in employees" :key="e.id" :value="e.id">
          {{ e.first_name }} {{ e.last_name }}
        </option>
      </select>
      <select v-model="filterProject" class="px-3 py-2 border rounded text-sm">
        <option value="">All Projects</option>
        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>
      <button @click="showSummary = !showSummary" class="px-3 py-2 bg-gray-200 rounded text-sm hover:bg-gray-300">
        {{ showSummary ? 'Hide' : 'Show' }} Summary
      </button>
    </div>

    <div v-if="showSummary" class="mb-4 p-3 bg-gray-50 rounded">
      <div class="flex gap-4 mb-2">
        <button
          v-for="by in ['employee', 'project', 'company']"
          :key="by"
          @click="summaryBy = by; fetchSummary()"
          class="px-2 py-1 text-sm rounded"
          :class="summaryBy === by ? 'bg-blue-600 text-white' : 'bg-gray-200'"
        >
          By {{ by }}
        </button>
      </div>
      <div v-if="summaryLoading" class="text-sm text-gray-600">Loading summary...</div>
      <div v-else-if="summaryError" class="text-sm text-red-600">{{ summaryError }}</div>
      <div v-else>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left">
              <th class="py-1">{{ summaryBy === 'company' ? 'Company' : summaryBy === 'project' ? 'Project' : 'Employee' }}</th>
              <th class="py-1 text-right">Hours</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in summaryData" :key="item.id" class="border-t">
              <td class="py-1">{{ item.name }}</td>
              <td class="py-1 text-right font-medium">{{ item.total_hours.toFixed(2) }}h</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="font-bold border-t-2">
              <td class="py-1">Total</td>
              <td class="py-1 text-right">{{ grandTotal.toFixed(2) }}h</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div v-if="loading" class="text-sm text-gray-600">Loading...</div>
    <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>
    <div v-else>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-100 text-left">
            <th @click="sortBy('date')" class="p-2 cursor-pointer hover:bg-gray-200">
              Date {{ sortIcon('date') }}
            </th>
            <th @click="sortBy('employee')" class="p-2 cursor-pointer hover:bg-gray-200">
              Employee {{ sortIcon('employee') }}
            </th>
            <th @click="sortBy('project')" class="p-2 cursor-pointer hover:bg-gray-200">
              Project {{ sortIcon('project') }}
            </th>
            <th class="p-2">Task</th>
            <th @click="sortBy('hours')" class="p-2 cursor-pointer hover:bg-gray-200">
              Hours {{ sortIcon('hours') }}
            </th>
            <th class="p-2">Notes</th>
            <th class="p-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in filteredEntries" :key="entry.id" class="border-t" :class="{'bg-red-50': editingId === entry.id}">
            <template v-if="editingId === entry.id">
              <td class="p-2">
                <input type="date" v-model="editForm.date" class="w-full border rounded" />
              </td>
              <td class="p-2">
                <select v-model.number="editForm.employee_id" class="w-full border rounded">
                  <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option>
                </select>
              </td>
              <td class="p-2">
                <select v-model.number="editForm.project_id" class="w-full border rounded">
                  <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </td>
              <td class="p-2">
                <select v-model.number="editForm.task_id" class="w-full border rounded">
                  <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </td>
              <td class="p-2">
                <input type="number" step="0.25" min="0" v-model="editForm.hours" class="w-full border rounded" />
              </td>
              <td class="p-2">
                <textarea v-model="editForm.notes" class="w-full border rounded" rows="1"></textarea>
              </td>
              <td class="p-2 space-x-1">
                <button @click="saveEdit" class="px-2 py-1 bg-green-600 text-white rounded text-xs">Save</button>
                <button @click="cancelEdit" class="px-2 py-1 bg-gray-400 text-white rounded text-xs">Cancel</button>
              </td>
            </template>
            <template v-else>
              <td class="p-2">{{ entry.date }}</td>
              <td class="p-2">{{ entry.employee?.first_name }} {{ entry.employee?.last_name }}</td>
              <td class="p-2">{{ entry.project?.name }}</td>
              <td class="p-2">{{ entry.task?.name }}</td>
              <td class="p-2">{{ entry.hours }}h</td>
              <td class="p-2">{{ entry.notes || '-' }}</td>
              <td class="p-2 space-x-1">
                <button @click="startEdit(entry)" class="px-2 py-1 bg-blue-600 text-white rounded text-xs">Edit</button>
                <button @click="remove(entry.id)" class="px-2 py-1 bg-red-600 text-white rounded text-xs">Delete</button>
              </td>
            </template>
          </tr>
          <tr v-if="filteredEntries.length === 0">
            <td colspan="7" class="p-4 text-center text-gray-600">No entries found.</td>
          </tr>
        </tbody>
      </table>

      <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing {{ filteredEntries.length }} of {{ totalEntries }} entries
        </div>
        <div class="flex gap-2">
          <button
            @click="changePage(-1)"
            :disabled="page <= 1"
            class="px-3 py-1 border rounded text-sm"
            :class="page <= 1 ? 'bg-gray-100 text-gray-400' : 'hover:bg-gray-100'"
          >
            Previous
          </button>
          <span class="px-3 py-1 text-sm">Page {{ page }}</span>
          <button
            @click="changePage(1)"
            :disabled="page >= totalPages"
            class="px-3 py-1 border rounded text-sm"
            :class="page >= totalPages ? 'bg-gray-100 text-gray-400' : 'hover:bg-gray-100'"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

interface Entry {
  id: number;
  date: string;
  hours: number;
  notes: string | null;
  employee?: { id: number; first_name: string; last_name: string };
  project?: { id: number; name: string; company_id?: number };
  task?: { id: number; name: string };
}

const entries = ref<Entry[]>([]);
const employees = ref<any[]>([]);
const projects = ref<any[]>([]);
const tasks = ref<any[]>([]);
const loading = ref(false);
const error = ref('');
const totalEntries = ref(0);

const search = ref('');
const filterEmployee = ref('');
const filterProject = ref('');
const sortField = ref('date');
const sortDir = ref('desc');
const page = ref(1);
const perPage = 20;

const showSummary = ref(false);
const summaryBy = ref('employee');
const summaryData = ref<any[]>([]);
const grandTotal = ref(0);
const summaryLoading = ref(false);
const summaryError = ref('');

const editingId = ref<number | null>(null);
const editForm = ref({
  date: '',
  employee_id: null as number | null,
  project_id: null as number | null,
  task_id: null as number | null,
  hours: '',
  notes: '',
});

async function fetchOptions() {
  const [eRes, pRes, tRes] = await Promise.all([
    axios.get('/api/options/employees'),
    axios.get('/api/options/projects'),
    axios.get('/api/options/tasks'),
  ]);
  employees.value = eRes.data;
  projects.value = pRes.data;
  tasks.value = tRes.data;
}

async function fetchEntries() {
  loading.value = true;
  error.value = '';
  try {
    const params: any = { per_page: perPage, page: page.value };
    if (filterEmployee.value) params.employee_id = filterEmployee.value;
    if (filterProject.value) params.project_id = filterProject.value;
    const { data } = await axios.get('/api/time-entries', { params });
    entries.value = data.data;
    totalEntries.value = data.meta?.total || data.total || data.data.length;
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to load entries';
  } finally {
    loading.value = false;
  }
}

async function fetchSummary() {
  summaryLoading.value = true;
  summaryError.value = '';
  try {
    const { data } = await axios.get('/api/time-entries/summary', {
      params: {
        by: summaryBy.value,
        employee_id: filterEmployee.value || undefined,
        project_id: filterProject.value || undefined,
      },
    });
    summaryData.value = data.totals;
    grandTotal.value = data.grand_total;
  } catch (e: any) {
    summaryError.value = e.response?.data?.message || 'Failed to load summary';
  } finally {
    summaryLoading.value = false;
  }
}

const filteredEntries = computed(() => {
  let result = [...entries.value];
  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter(e =>
      (e.employee?.first_name + ' ' + e.employee?.last_name).toLowerCase().includes(q) ||
      e.project?.name?.toLowerCase().includes(q) ||
      e.task?.name?.toLowerCase().includes(q) ||
      e.notes?.toLowerCase().includes(q) ||
      e.date.includes(q)
    );
  }
  result.sort((a, b) => {
    let av: any, bv: any;
    if (sortField.value === 'date') { av = a.date; bv = b.date; }
    else if (sortField.value === 'employee') { av = a.employee?.last_name || ''; bv = b.employee?.last_name || ''; }
    else if (sortField.value === 'project') { av = a.project?.name || ''; bv = b.project?.name || ''; }
    else if (sortField.value === 'hours') { av = a.hours; bv = b.hours; }
    if (av < bv) return sortDir.value === 'asc' ? -1 : 1;
    if (av > bv) return sortDir.value === 'asc' ? 1 : -1;
    return 0;
  });
  return result;
});

const totalPages = computed(() => Math.ceil(totalEntries.value / perPage));

function sortBy(field: string) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDir.value = 'desc';
  }
}

function sortIcon(field: string) {
  if (sortField.value !== field) return '';
  return sortDir.value === 'asc' ? '↑' : '↓';
}

function changePage(delta: number) {
  const newPage = page.value + delta;
  if (newPage >= 1 && newPage <= totalPages.value) {
    page.value = newPage;
  }
}

async function remove(id: number) {
  if (!confirm('Delete this entry?')) return;
  try {
    await axios.delete(`/api/time-entries/${id}`);
    entries.value = entries.value.filter(e => e.id !== id);
    totalEntries.value--;
  } catch (e: any) {
    alert(e.response?.data?.message || 'Delete failed');
  }
}

function startEdit(entry: Entry) {
  editingId.value = entry.id;
  editForm.value = {
    date: entry.date,
    employee_id: entry.employee?.id ?? null,
    project_id: entry.project?.id ?? null,
    task_id: entry.task?.id ?? null,
    hours: entry.hours.toString(),
    notes: entry.notes || '',
  };
}

function cancelEdit() {
  editingId.value = null;
}

async function saveEdit() {
  if (!editingId.value) return;
  try {
    const { data } = await axios.put(`/api/time-entries/${editingId.value}`, {
      date: editForm.value.date,
      employee_id: editForm.value.employee_id,
      project_id: editForm.value.project_id,
      task_id: editForm.value.task_id,
      hours: editForm.value.hours,
      notes: editForm.value.notes,
    });
    const idx = entries.value.findIndex(e => e.id === editingId.value);
    if (idx !== -1) {
      entries.value[idx] = { ...entries.value[idx], ...data };
    }
    editingId.value = null;
  } catch (e: any) {
    alert(e.response?.data?.message || 'Update failed');
  }
}

watch([filterEmployee, filterProject], () => {
  page.value = 1;
  fetchEntries();
});

watch(showSummary, (val) => {
  if (val) fetchSummary();
});

onMounted(() => {
  fetchOptions();
  fetchEntries();
});
</script>