<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppIcon from '../../components/AppIcon.vue';

const props = defineProps({
    start: {
        type: String,
        default: '',
    },
    end: {
        type: String,
        default: '',
    },
    days: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    reconciliation: {
        type: Object,
        default: null,
    },
});

const startDate = ref(props.start);
const endDate = ref(props.end);

function applyRange() {
    router.get('/admin/report', {
        start: startDate.value,
        end: endDate.value,
    }, { preserveScroll: true });
}
</script>

<template>
  <Head title="Admin Report — intellect" />

  <main class="report-page">
    <header class="report-header">
      <div>
        <p class="eyebrow">
         Pixelforge operations
        </p>
        <h1>Admin Report</h1>
        <p class="report-sub">
          Credit spend, provider costs and profit, per day and per user.
        </p>
      </div>
      <form
        class="range-form"
        @submit.prevent="applyRange"
      >
        <label>
          <span>From</span>
          <input
            v-model="startDate"
            type="date"
          >
        </label>
        <label>
          <span>To</span>
          <input
            v-model="endDate"
            type="date"
          >
        </label>
        <button
          type="submit"
          class="apply-button"
        >
          Apply
        </button>
      </form>
    </header>

    <section
      class="report-section"
      aria-labelledby="daily-title"
    >
      <h2 id="daily-title">
        By day
      </h2>
      <table
        class="report-table"
        role="table"
        aria-label="Daily credit spend, provider costs and profit"
      >
        <thead>
          <tr>
            <th scope="col">
              Day (UTC)
            </th>
            <th scope="col">
              Generations
            </th>
            <th scope="col">
              Provider cost
            </th>
            <th scope="col">
              Credit spend
            </th>
            <th scope="col">
              Profit
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="day in days"
            :key="day.date"
          >
            <td data-testid="report-day">{{ day.date }}</td>
            <td>{{ day.generations }}</td>
            <td>{{ day.provider_display }}</td>
            <td>{{ day.spend_display }}</td>
            <td>{{ day.profit_display }}</td>
          </tr>
          <tr v-if="days.length === 0">
            <td colspan="5">
              No completed generations in this range.
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section
      class="report-section"
      aria-labelledby="users-title"
    >
      <h2 id="users-title">
        By user
      </h2>
      <table
        class="report-table"
        role="table"
        aria-label="Per-user credit spend, provider costs and profit"
      >
        <thead>
          <tr>
            <th scope="col">
              User
            </th>
            <th scope="col">
              Generations
            </th>
            <th scope="col">
              Provider cost
            </th>
            <th scope="col">
              Credit spend
            </th>
            <th scope="col">
              Profit
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in users"
            :key="item.email + item.name"
          >
            <td>
              <strong>{{ item.name }}</strong>
              <small>{{ item.email }}</small>
            </td>
            <td>{{ item.generations }}</td>
            <td>{{ item.provider_display }}</td>
            <td>{{ item.spend_display }}</td>
            <td>{{ item.profit_display }}</td>
          </tr>
          <tr v-if="users.length === 0">
            <td colspan="5">
              No users charged in this range.
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section
      class="report-section"
      aria-labelledby="reconciliation-title"
    >
      <div class="section-heading">
        <h2 id="reconciliation-title">
          Pixelforge reconciliation
        </h2>
        <p>Our recorded provider costs against GET /v1/usage.</p>
      </div>
      <table
        v-if="reconciliation"
        class="report-table"
        role="table"
        aria-label="Reconciliation of provider costs"
      >
        <thead>
          <tr>
            <th scope="col">
              Day (UTC)
            </th>
            <th scope="col">
              Ours
            </th>
            <th scope="col">
              Pixelforge
            </th>
            <th scope="col">
              Match
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in reconciliation.rows"
            :key="row.date"
            :class="{ mismatch: !row.matches }"
          >
            <td>{{ row.date }}</td>
            <td>{{ row.our_cost_display }} ({{ row.our_generations }})</td>
            <td>{{ row.provider_cost_display }} ({{ row.provider_generations }})</td>
            <td>
              <span
                class="match-badge"
                :class="{ ok: row.matches }"
              >
                <AppIcon
                  :name="row.matches ? 'check' : 'alert'"
                  :size="15"
                />
                {{ row.matches ? 'Matches' : 'Mismatch' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
      <p
        v-else
        class="missing-note"
      >
        Pixelforge's usage report could not be fetched for this range (check the API key configuration).
      </p>
    </section>
  </main>
</template>

<style scoped>
.report-page {
  min-height: 100svh;
  max-width: 1180px;
  margin: 0 auto;
  padding: clamp(28px, 3vw, 56px) clamp(18px, 2.5vw, 42px) 64px;
  color: #f3f4e9;
  font-family: inherit;
  background:
    radial-gradient(circle at 12% 18%, rgb(146 154 72 / 22%), transparent 32%),
    radial-gradient(circle at 88% 8%, rgb(12 72 58 / 24%), transparent 42%),
    #232b1a;
}

.report-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 22px;
  margin-bottom: clamp(26px, 2.4vw, 44px);
}

.report-header h1 {
  margin: 4px 0 6px;
  font-size: clamp(30px, 2.1vw, 42px);
  font-weight: 500;
  letter-spacing: -0.04em;
}

.eyebrow {
  margin: 0;
  color: #ffd400;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.report-sub {
  margin: 0;
  color: #c0c3b5;
  font-size: 15px;
}

.range-form {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 12px;
  padding: 16px 18px;
  background: rgb(255 255 255 / 6%);
  border: 1px solid rgb(255 255 255 / 11%);
  border-radius: 18px;
}

.range-form label {
  display: flex;
  flex-direction: column;
  gap: 5px;
  color: #c0c3b5;
  font-size: 12px;
}

.range-form input {
  min-height: 42px;
  padding: 0 12px;
  color: #f3f4e9;
  font: inherit;
  font-size: 14px;
  background: rgb(255 255 255 / 7%);
  border: 1px solid rgb(255 255 255 / 12%);
  border-radius: 10px;
}

.range-form input:focus-visible {
  outline: 2px solid #ffd400;
  outline-offset: 2px;
}

.apply-button {
  min-height: 42px;
  padding: 0 20px;
  margin-bottom: 2px;
  color: #2c290f;
  font: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  background: #ffd400;
  border: 0;
  border-radius: 10px;
}

.report-section {
  margin-top: clamp(26px, 2.4vw, 40px);
}

.report-section h2 {
  margin: 0 0 14px;
  font-size: clamp(20px, 1.4vw, 26px);
  font-weight: 500;
  letter-spacing: -0.025em;
}

.section-heading p {
  margin: -8px 0 14px;
  color: #c0c3b5;
  font-size: 13.5px;
}

.report-table {
  width: 100%;
  background: rgb(255 255 255 / 5%);
  border: 1px solid rgb(255 255 255 / 10%);
  border-collapse: collapse;
  border-radius: 16px;
  overflow: hidden;
}

.report-table th,
.report-table td {
  padding: 12px 16px;
  font-size: 14px;
  text-align: left;
  border-bottom: 1px solid rgb(255 255 255 / 7%);
  font-variant-numeric: tabular-nums;
}

.report-table th {
  color: #c0c3b5;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  background: rgb(255 255 255 / 4%);
}

.report-table tbody tr:last-child td {
  border-bottom: 0;
}

.report-table strong {
  display: block;
  font-weight: 500;
}

.report-table small {
  color: #b9bcae;
  font-size: 12px;
}

.report-table tr.mismatch td {
  background: rgb(255 105 71 / 10%);
}

.match-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  color: #ffb59e;
  font-size: 12px;
  font-weight: 600;
  background: rgb(255 105 71 / 14%);
  border-radius: 999px;
}

.match-badge.ok {
  color: #d7e8a4;
  background: rgb(148 190 86 / 16%);
}

.missing-note {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #c0c3b5;
  font-size: 14px;
}

button:focus-visible {
  outline: 2px solid #ffd400;
  outline-offset: 3px;
}

@media (max-width: 760px) {
  .report-table {
    display: block;
    overflow-x: auto;
  }
}
</style>
