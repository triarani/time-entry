<template>
  <!-- Centered container with max width 750px -->
  <div class="max-w-187.5 mx-auto p-4 bg-white dark:bg-gray-800 rounded shadow">
    <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">New Time Entry</h2>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1" for="date">Date</label>
        <input
          id="date"
          type="date"
          v-model="form.date"
          class="w-full px-3 py-2 border rounded bg-gray-50 dark:bg-gray-700"
          required
        />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="hours">Hours</label>
        <input
          id="hours"
          type="number"
          step="0.25"
          min="0"
          v-model="form.hours"
          class="w-full px-3 py-2 border rounded bg-gray-50 dark:bg-gray-700"
          required
        />
      </div>
      <!-- Employee select -->
      <div>
        <label class="block text-sm font-medium mb-1" for="employee">Employee</label>
        <select
          id="employee"
          v-model="form.employee_id"
          class="w-full px-3 py-2 border rounded bg-gray-50 dark:bg-gray-700"
          required
        >
          <option value="" disabled>Select employee</option>
          <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option>
        </select>
      </div>
      <!-- Project select -->
      <div>
        <label class="block text-sm font-medium mb-1" for="project">Project</label>
        <select
          id="project"
          v-model="form.project_id"
          class="w-full px-3 py-2 border rounded bg-gray-50 dark:bg-gray-700"
          required
        >
          <option value="" disabled>Select project</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>
      <!-- Task select -->
      <div>
        <label class="block text-sm font-medium mb-1" for="task">Task</label>
        <select
          id="task"
          v-model="form.task_id"
          class="w-full px-3 py-2 border rounded bg-gray-50 dark:bg-gray-700"
          required
        >
          <option value="" disabled>Select task</option>
          <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="description">Description</label>
        <textarea
          id="description"
          v-model="form.description"
          rows="2"
          class="w-full px-3 py-2 border rounded bg-gray-50 dark:bg-gray-700"
        ></textarea>
      </div>
      <button
        type="submit"
        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : 'Save' }}
      </button>
    </form>
    <p v-if="error" class="mt-2 text-red-600">{{ error }}</p>
    <p v-if="success" class="mt-2 text-green-600">Saved!</p>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Form fields now include foreign keys required by the backend
const form = ref({
  date: '',
  hours: '',
  description: '',
  employee_id: null as number | null,
  project_id: null as number | null,
  task_id: null as number | null,
});
const loading = ref(false);
const error = ref('');
const success = ref(false);

// Options for selects (generic objects)
const employees = ref<Array<any>>([]);
const projects = ref<Array<any>>([]);
const tasks = ref<Array<any>>([]);

async function fetchOptions() {
  try {
    const [empRes, projRes, taskRes] = await Promise.all([
      axios.get('/api/employees'),
      axios.get('/api/projects'),
      axios.get('/api/tasks'),
    ]);
    employees.value = empRes.data;
    projects.value = projRes.data;
    tasks.value = taskRes.data;
  } catch (e) {
    // ignore errors for now; selects will be empty
  }
}

onMounted(fetchOptions);

async function submit() {
  loading.value = true;
  error.value = '';
  success.value = false;
  try {
    await axios.post('/api/time-entries', {
      date: form.value.date,
      hours: form.value.hours,
      description: form.value.description,
      employee_id: form.value.employee_id,
      project_id: form.value.project_id,
      task_id: form.value.task_id,
    });
    success.value = true;
    // reset form
    form.value = {
      date: '',
      hours: '',
      description: '',
      employee_id: null,
      project_id: null,
      task_id: null,
    };
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to save';
  } finally {
    loading.value = false;
    setTimeout(() => (success.value = false), 3000);
  }
}
</script>
