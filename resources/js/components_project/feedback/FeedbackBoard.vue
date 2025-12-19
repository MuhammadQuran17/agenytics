<script setup lang="ts">
import { computed } from 'vue'
import { type Feedback, type FeedbackStatus } from '@/types/feedback'
import FeedbackColumn from '@/components_project/feedback/FeedbackColumn.vue'

interface Props {
  feedbacks: Feedback[]
  statuses: FeedbackStatus[]
  isAuthenticated: boolean
  canManageFeedback?: boolean
}

const props = defineProps<Props>()

// -[START]- Computed
const sortedStatuses = computed(() =>
  [...props.statuses].sort((a, b) => a.order - b.order)
)
// -[END]- Computed
</script>

<template>
  <div class="w-full">
    <!-- Board Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold mb-2">Roadmap</h2>
    </div>

    <!-- Columns Container -->
    <div class="flex gap-6 overflow-x-auto pb-4">
      <FeedbackColumn
        v-for="status in sortedStatuses"
        :key="status.key"
        :status="status"
        :feedbacks="feedbacks"
        :is-authenticated="isAuthenticated"
        :can-manage-feedback="canManageFeedback"
      />
    </div>
  </div>
</template>