<script setup>
import AppIcon from '../AppIcon.vue';
import { CopyButton } from '../ui/copy-button';

defineProps({
    generation: {
        type: Object,
        required: true,
    },
});

function costCaption(generation) {
    if (generation.status === 'failed') {
        return 'No charge';
    }

    if (generation.status === 'succeeded') {
        return generation.user_cost_display ? `Paid ${generation.user_cost_display}` : '';
    }

    return generation.estimated_display ? `Est. ${generation.estimated_display}` : '';
}

function formatQuality(quality) {
    if (!quality) {
        return '';
    }

    return `${quality.charAt(0).toUpperCase()}${quality.slice(1)}`;
}
</script>

<template>
  <article
    class="group flex flex-col overflow-hidden bg-secondary border border-border-subtle rounded-[20px] shadow-[0_14px_30px_rgb(0_0_0/14%)] transition-[transform,border-color,box-shadow] duration-200 hover:-translate-y-1 hover:border-[rgb(255_218_42/55%)] hover:shadow-[0_20px_38px_rgb(0_0_0/22%)] motion-reduce:transition-none"
    :title="generation.prompt"
  >
    <div class="relative w-full aspect-square overflow-hidden bg-black/10">
      <a
        v-if="generation.status === 'succeeded' && generation.image_url"
        :href="generation.image_url"
        target="_blank"
        rel="noopener"
        class="absolute inset-0 block text-white no-underline"
        :aria-label="`Open generated image: ${generation.prompt}`"
      >
        <img
          :src="generation.image_url"
          :alt="generation.prompt"
          loading="lazy"
          class="w-full h-full object-cover transition-transform duration-500 [transition-timing-function:cubic-bezier(0.2,0.8,0.2,1)] group-hover:scale-[1.045] motion-reduce:transition-none"
        >
        <span class="absolute inset-x-0 bottom-0 flex items-center justify-end gap-1.5 pt-7 px-3 pb-2.5 text-xs font-medium text-white bg-[linear-gradient(transparent,rgb(0_0_0/65%))] opacity-0 translate-y-1 transition-[opacity,transform] duration-[180ms] ease-[ease] group-hover:opacity-100 group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:translate-y-0 motion-reduce:transition-none">
          <AppIcon
            name="download"
            :size="16"
          />
          Open
        </span>
      </a>
      <div
        v-else-if="generation.status === 'failed'"
        class="absolute inset-0 flex items-center justify-center flex-col gap-2 p-[14%] text-center text-muted-foreground dark:text-[#ffd6c9] text-[clamp(12px,0.75vw,14px)] bg-[rgb(116_46_33/28%)]"
      >
        <AppIcon
          name="alert"
          :size="26"
        />
        <span class="font-medium">Generation failed</span>
        <small class="text-[11px] leading-[1.4] line-clamp-2 max-[760px]:hidden">{{ generation.error?.message || 'Please try another prompt.' }}</small>
      </div>
      <div
        v-else
        class="absolute inset-0 flex items-center justify-center flex-col gap-2 p-[14%] text-foreground text-center text-[clamp(12px,0.75vw,14px)] bg-[radial-gradient(circle_at_40%_35%,rgb(48_151_127/20%),transparent_45%),linear-gradient(135deg,rgb(255_255_255/8%),rgb(255_255_255/2%))]"
      >
        <span class="size-[34px] mb-1 bg-[conic-gradient(from_30deg,#28dbc1,#f8dd51,#b26cff,#28dbc1)] rounded-[42%_58%_61%_39%] blur-[1px] [animation:morph_2.4s_ease-in-out_infinite,spin_4s_linear_infinite] motion-reduce:[animation-duration:4s] max-[760px]:size-[27px]" />
        <span class="font-medium">{{ generation.status === 'processing' ? 'Creating your image' : 'Waiting in queue' }}</span>
        <small class="text-muted-foreground text-[11px] leading-[1.4] max-[760px]:hidden">PixelForge is at work</small>
      </div>
      <p
        v-if="costCaption(generation)"
        data-testid="cost-caption"
        class="absolute left-2 bottom-2 z-[3] inline-flex w-fit max-w-[calc(100%-16px)] items-center py-1 px-2.5 m-0 overflow-hidden text-white text-[11px] font-medium whitespace-nowrap bg-[rgb(24_30_21/76%)] border border-white/10 rounded-full backdrop-blur-[10px] max-[760px]:left-1.5 max-[760px]:bottom-1.5 max-[760px]:max-w-[calc(100%-12px)] max-[760px]:px-2 max-[760px]:py-0.5 max-[760px]:text-[10px]"
      >
        {{ costCaption(generation) }}
      </p>
    </div>

    <div class="flex flex-1 flex-col justify-between gap-2 p-3 max-[760px]:p-2.5">
      <p class="m-0 text-xs leading-[1.45] text-foreground/90 line-clamp-2">
        {{ generation.prompt }}
      </p>
      <div class="flex items-center justify-between gap-2 text-[11px] text-muted-foreground max-[600px]:text-[10px]">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1">
          <span
            v-if="generation.size"
            class="rounded-md bg-foreground/6 px-1.5 py-0.5 tabular-nums"
          >{{ generation.size.replace('x', '×') }}</span>
          <span
            v-if="generation.quality"
            class="rounded-md bg-foreground/6 px-1.5 py-0.5"
          >{{ formatQuality(generation.quality) }}</span>
        </div>
        <CopyButton
          :value="generation.prompt || ''"
          label="Copy prompt"
          copied-label="Prompt copied"
          success-message="Prompt copied to your clipboard."
          class="size-7 shrink-0 rounded-lg p-0 text-muted-foreground hover:text-foreground"
        />
      </div>
    </div>
  </article>
</template>
