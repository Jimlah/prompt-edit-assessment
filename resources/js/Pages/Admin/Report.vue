<script setup>
import { DateInput, DateInputSegment } from '@ui-sdk/vue/date-input';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppIcon from '../../components/AppIcon.vue';
import { Button } from '../../components/ui/button';
import { CopyButton } from '../../components/ui/copy-button';
import { TextField } from '../../components/ui/textfield';

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
    generations: {
        type: Array,
        default: () => [],
    },
    transactions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const errors = computed(() => page.props.errors ?? {});
const startDate = ref(props.start || null);
const endDate = ref(props.end || null);

function applyRange() {
    const query = {};

    if (startDate.value) {
        query.start = startDate.value;
    }

    if (endDate.value) {
        query.end = endDate.value;
    }

    router.get('/admin/report', query, { preserveScroll: true });
}
</script>

<template>
  <Head title="Admin Report — intellect" />

  <main class="min-h-[100svh] max-w-[1180px] mx-auto pt-[clamp(28px,3vw,56px)] px-[clamp(18px,2.5vw,42px)] pb-16 text-foreground bg-[#e9ecd9] dark:bg-[#232b1a] bg-[radial-gradient(circle_at_12%_18%,rgb(146_154_72/22%),transparent_32%),radial-gradient(circle_at_88%_8%,rgb(12_72_58/24%),transparent_42%)]">
    <header class="flex flex-wrap items-end justify-between gap-[22px] mb-[clamp(26px,2.4vw,44px)]">
      <div>
        <p class="m-0 text-primary text-xs font-semibold tracking-[0.14em] uppercase">
         Pixelforge operations
        </p>
        <h1 class="mt-1 mb-1.5 text-[clamp(30px,2.1vw,42px)] font-medium tracking-[-0.04em]">Admin Report</h1>
        <p class="m-0 text-muted-foreground text-[15px]">
          Credit spend, provider costs and profit, per day and per user.
        </p>
      </div>
      <form
        class="flex flex-wrap items-end gap-3 py-4 px-[18px] bg-secondary border border-border rounded-[18px]"
        @submit.prevent="applyRange"
      >
        <TextField
          name="start"
          variant="panel"
          size="none"
          class="w-[165px]"
        >
          <TextField.Label class="w-fit text-xs font-normal text-muted-foreground">
            From
          </TextField.Label>
          <DateInput
            v-model:value="startDate"
            v-slot="{ state }"
            class="flex min-h-[42px] w-full items-center gap-0.5 rounded-[10px] border border-border bg-secondary px-3 text-sm text-foreground outline-none focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-ring"
          >
            <template
              v-for="segment in state.segments"
              :key="segment.key"
            >
              <DateInputSegment
                v-if="segment.editable"
                :segment="segment"
                class="min-w-[1.6ch] py-1 px-1 text-center tabular-nums outline-none data-empty:text-muted-foreground/70"
              />
              <DateInputSegment
                v-else
                :segment="segment"
                class="text-muted-foreground"
              >–</DateInputSegment>
            </template>
          </DateInput>
        </TextField>
        <TextField
          name="end"
          variant="panel"
          size="none"
          class="w-[165px]"
        >
          <TextField.Label class="w-fit text-xs font-normal text-muted-foreground">
            To
          </TextField.Label>
          <DateInput
            v-model:value="endDate"
            v-slot="{ state }"
            class="flex min-h-[42px] w-full items-center gap-0.5 rounded-[10px] border border-border bg-secondary px-3 text-sm text-foreground outline-none focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-ring"
          >
            <template
              v-for="segment in state.segments"
              :key="segment.key"
            >
              <DateInputSegment
                v-if="segment.editable"
                :segment="segment"
                class="min-w-[1.6ch] py-1 px-1 text-center tabular-nums outline-none data-empty:text-muted-foreground/70"
              />
              <DateInputSegment
                v-else
                :segment="segment"
                class="text-muted-foreground"
              >–</DateInputSegment>
            </template>
          </DateInput>
        </TextField>
        <Button
          type="submit"
          variant="primary"
          size="none"
          class="min-h-[42px] px-5 mb-0.5 rounded-[10px]"
        >
          Apply
        </Button>
      </form>
    </header>

    <p
      v-if="errors.start"
      role="alert"
      class="m-0 mb-6 rounded-xl bg-destructive/10 px-4 py-3 text-sm text-destructive"
    >
      {{ errors.start }}
    </p>

    <section
      class="mt-[clamp(26px,2.4vw,40px)]"
      aria-labelledby="daily-title"
    >
      <h2 id="daily-title" class="m-0 mb-[14px] text-[clamp(20px,1.4vw,26px)] font-medium tracking-[-0.025em]">
        By day
      </h2>
      <table
        class="w-full bg-secondary border border-border border-collapse rounded-2xl overflow-hidden max-[760px]:block max-[760px]:overflow-x-auto"
        role="table"
        aria-label="Daily credit spend, provider costs and profit"
      >
        <thead>
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Day (UTC)
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Generations
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Provider cost
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Credit spend
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Profit
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="day in days"
            :key="day.date"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border"
          >
            <td data-testid="report-day" class="tabular-nums">{{ day.date }}</td>
            <td class="tabular-nums">{{ day.generations }}</td>
            <td class="tabular-nums">{{ day.provider_display }}</td>
            <td class="tabular-nums">{{ day.spend_display }}</td>
            <td class="tabular-nums">{{ day.profit_display }}</td>
          </tr>
          <tr
            v-if="days.length === 0"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border [&>td]:border-b-0"
          >
            <td colspan="5">
              No completed generations in this range.
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section
      class="mt-[clamp(26px,2.4vw,40px)]"
      aria-labelledby="users-title"
    >
      <h2 id="users-title" class="m-0 mb-[14px] text-[clamp(20px,1.4vw,26px)] font-medium tracking-[-0.025em]">
        By user
      </h2>
      <table
        class="w-full bg-secondary border border-border border-collapse rounded-2xl overflow-hidden max-[760px]:block max-[760px]:overflow-x-auto"
        role="table"
        aria-label="Per-user credit spend, provider costs and profit"
      >
        <thead>
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              User
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Generations
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Provider cost
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Credit spend
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Profit
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in users"
            :key="item.email + item.name"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border"
          >
            <td>
              <strong class="block font-medium">{{ item.name }}</strong>
              <small class="text-xs text-muted-foreground">{{ item.email }}</small>
            </td>
            <td class="tabular-nums">{{ item.generations }}</td>
            <td class="tabular-nums">{{ item.provider_display }}</td>
            <td class="tabular-nums">{{ item.spend_display }}</td>
            <td class="tabular-nums">{{ item.profit_display }}</td>
          </tr>
          <tr
            v-if="users.length === 0"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border [&>td]:border-b-0"
          >
            <td colspan="5">
              No users charged in this range.
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section
      class="mt-[clamp(26px,2.4vw,40px)]"
      aria-labelledby="generations-title"
    >
      <h2 id="generations-title" class="m-0 mb-[14px] text-[clamp(20px,1.4vw,26px)] font-medium tracking-[-0.025em]">
        All generations
      </h2>
      <p class="mt-[-8px] mb-[14px] text-muted-foreground text-[13.5px]">
        Every generation requested in this range, with the deducted cost and the actual provider cost.
      </p>
      <table
        class="w-full bg-secondary border border-border border-collapse rounded-2xl overflow-hidden max-[760px]:block max-[760px]:overflow-x-auto"
        role="table"
        aria-label="All generations with users, deducted and actual costs"
      >
        <thead>
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Requested
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              User
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Generation
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Status
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Deducted
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Actual
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in generations"
            :key="item.id"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border"
          >
            <td class="whitespace-nowrap tabular-nums">{{ item.datetime }}</td>
            <td>
              <strong class="block font-medium">{{ item.name }}</strong>
              <small class="text-xs text-muted-foreground">{{ item.email }}</small>
            </td>
            <td class="max-w-[340px]">
              <div class="flex items-start justify-between gap-2">
                <span class="block">{{ item.prompt }}</span>
                <CopyButton
                  :value="item.prompt || ''"
                  label="Copy prompt"
                  copied-label="Prompt copied"
                  :show-toast="false"
                  class="size-7 shrink-0 rounded-lg p-0 text-muted-foreground hover:text-foreground"
                />
              </div>
              <div class="mt-0.5 flex items-center gap-1.5">
                <small class="block break-all text-xs text-muted-foreground">{{ item.provider_id ?? 'No provider id yet' }}</small>
                <CopyButton
                  v-if="item.provider_id"
                  :value="String(item.provider_id)"
                  label="Copy provider ID"
                  copied-label="Provider ID copied"
                  :show-toast="false"
                  :icon-size="12"
                  class="size-6 shrink-0 rounded-md p-0 text-muted-foreground hover:text-foreground"
                />
              </div>
            </td>
            <td class="capitalize">{{ item.status }}</td>
            <td class="tabular-nums">{{ item.deducted_display ?? '—' }}</td>
            <td class="tabular-nums">{{ item.actual_display ?? '—' }}</td>
          </tr>
          <tr
            v-if="generations.length === 0"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border [&>td]:border-b-0"
          >
            <td colspan="6">
              No generations in this range.
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section
      class="mt-[clamp(26px,2.4vw,40px)]"
      aria-labelledby="transactions-title"
    >
      <h2 id="transactions-title" class="m-0 mb-[14px] text-[clamp(20px,1.4vw,26px)] font-medium tracking-[-0.025em]">
        All transactions
      </h2>
      <p class="mt-[-8px] mb-[14px] text-muted-foreground text-[13.5px]">
        Credit purchases, generation charges and refunds across every user.
      </p>
      <table
        class="w-full bg-secondary border border-border border-collapse rounded-2xl overflow-hidden max-[760px]:block max-[760px]:overflow-x-auto"
        role="table"
        aria-label="All balance transactions"
      >
        <thead>
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Date
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              User
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Type
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Amount
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Description
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in transactions"
            :key="item.id"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border"
          >
            <td class="whitespace-nowrap tabular-nums">{{ item.datetime }}</td>
            <td>
              <strong class="block font-medium">{{ item.name }}</strong>
              <small class="text-xs text-muted-foreground">{{ item.email }}</small>
            </td>
            <td class="capitalize">{{ item.type }}</td>
            <td class="tabular-nums">{{ item.amount_display }}</td>
            <td class="max-w-[360px]">
              <span class="block">{{ item.description ?? '—' }}</span>
              <div
                v-if="item.generation_id"
                class="mt-0.5 flex items-center gap-1.5"
              >
                <small class="block break-all text-xs text-muted-foreground">{{ item.generation_id }}</small>
                <CopyButton
                  :value="String(item.generation_id)"
                  label="Copy generation ID"
                  copied-label="Generation ID copied"
                  :show-toast="false"
                  :icon-size="12"
                  class="size-6 shrink-0 rounded-md p-0 text-muted-foreground hover:text-foreground"
                />
              </div>
            </td>
          </tr>
          <tr
            v-if="transactions.length === 0"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border [&>td]:border-b-0"
          >
            <td colspan="5">
              No transactions in this range.
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section
      class="mt-[clamp(26px,2.4vw,40px)]"
      aria-labelledby="reconciliation-title"
    >
      <div>
        <h2 id="reconciliation-title" class="m-0 mb-[14px] text-[clamp(20px,1.4vw,26px)] font-medium tracking-[-0.025em]">
          Pixelforge reconciliation
        </h2>
        <p class="mt-[-8px] mb-[14px] text-muted-foreground text-[13.5px]">Our recorded provider costs against the Pixelforge usage snapshot the daily sync keeps in the database.</p>
      </div>
      <table
        v-if="reconciliation && reconciliation.rows.length > 0"
        class="w-full bg-secondary border border-border border-collapse rounded-2xl overflow-hidden max-[760px]:block max-[760px]:overflow-x-auto"
        role="table"
        aria-label="Reconciliation of provider costs"
      >
        <thead>
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Day (UTC)
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Ours
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Pixelforge
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.06em] text-muted-foreground bg-white/4 border-b border-border">
              Match
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in reconciliation.rows"
            :key="row.date"
            class="[&>td]:px-4 [&>td]:py-3 [&>td]:text-sm [&>td]:text-left [&>td]:border-b [&>td]:border-border [&>td]:tabular-nums"
            :class="{ '[&>td]:bg-[rgb(255_105_71/10%)] [&>td]:border-b-0': !row.matches }"
          >
            <td>{{ row.date }}</td>
            <td>{{ row.our_cost_display }} ({{ row.our_generations }})</td>
            <td>{{ row.provider_cost_display }} ({{ row.provider_generations }})</td>
            <td>
              <span
                class="inline-flex items-center gap-[5px] py-[3px] px-[9px] text-xs font-semibold rounded-full"
                :class="row.matches ? 'text-[#d7e8a4] bg-[rgb(148_190_86/16%)]' : 'text-[#ffb59e] bg-[rgb(255_105_71/14%)]'"
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
        class="flex items-center gap-[9px] text-muted-foreground text-sm"
      >
        No Pixelforge usage is synced for this range yet — the daily sync refreshes the snapshot.
      </p>
    </section>
  </main>
</template>
