<script setup lang="ts">
import { computed } from 'vue'
import { type Feedback, type FeedbackStatus } from '@/types/feedback'
import FeedbackCard from '@/components_project/feedback/FeedbackCard.vue'

interface Props {
  status: FeedbackStatus
  feedbacks: Feedback[]
  isAuthenticated: boolean
  canManageFeedback?: boolean
}

const props = defineProps<Props>()

// -[START]- Computed
const columnTitle = computed(() => props.status.label)
const columnColor = computed(() => props.status.color)
const filteredFeedbacks = computed(() =>
  props.feedbacks.filter(feedback => feedback.status === props.status.key)
)
// -[END]- Computed
</script>

<template>
  <div class="flex flex-col min-w-80 max-w-96">
    <!-- Column Header -->
    <div class="flex items-center gap-3 mb-4 pb-2 border-b border-border">
      <div
        class="w-3 h-3 rounded-full"
        :class="columnColor"
      ></div>
      <h3 class="font-semibold text-lg">{{ columnTitle }}</h3>
      <span class="text-sm text-muted-foreground bg-muted px-2 py-1 rounded-full">
        {{ filteredFeedbacks.length }}
      </span>
    </div>

    <!-- Feedback Cards -->
    <div class="space-y-4 flex-1">
      <FeedbackCard
        v-for="feedback in filteredFeedbacks"
        :key="feedback.id"
        :feedback="feedback"
        :is-authenticated="isAuthenticated"
        :can-manage-feedback="canManageFeedback"
      />

      <!-- Empty State -->
      <div
        v-if="filteredFeedbacks.length === 0"
        class="flex-1 flex flex-col items-center justify-center text-center text-muted-foreground"
      >
        <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <p class="text-sm">No {{ columnTitle.toLowerCase() }} feedback yet</p>
      </div>
    </div>
  </div>
</template>