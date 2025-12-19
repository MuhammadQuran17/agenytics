<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { type Feedback } from '@/types/feedback'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem,
} from '@/components/ui/dropdown-menu'
import { voteOnFeedback, deleteFeedbackApi } from '@/api_requests/feedback/feedback'
import { truncateString } from '@/lib/utils'
import { MoreVertical, Pencil, Trash2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

interface Props {
  feedback: Feedback
  isAuthenticated: boolean
  canManageFeedback?: boolean
}

const { feedback, isAuthenticated, canManageFeedback } = defineProps<Props>()

// -[START]- Voting
const isVoting = ref(false)
const userHasVoted = ref(feedback.user_has_voted || false)

const vote = async (direction: 'up' | 'down') => {
  if (!isAuthenticated || isVoting.value) return

  isVoting.value = true
  try {
    const data = await voteOnFeedback(feedback.id, direction)
    const change = data.change || 0
    feedback.upvotes_count += change

    if (direction === 'up') {
      userHasVoted.value = change > 0
      feedback.user_has_voted = change > 0
    } else {
      userHasVoted.value = false
      feedback.user_has_voted = false
    }
  } catch (error) {
    console.error('Failed to vote:', error)
  } finally {
    isVoting.value = false
  }
}
// -[END]- Voting

// -[START]- Admin Actions
const isDeleting = ref(false)

const deleteFeedback = async () => {
  if (!canManageFeedback || isDeleting.value) return

  if (!confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
    return
  }

  isDeleting.value = true
  try {
    const data = await deleteFeedbackApi(feedback.id)
    if (data.message) {
      toast.success(data.message)
      router.visit(route('feedback.list'))
      isDeleting.value = false
    }
  } catch (error) {
    console.error('Failed to delete feedback:', error)
    toast.error('Failed to delete feedback')
  }
}

const editFeedback = () => {
  if (!canManageFeedback) return
  router.visit(route('feedback.edit', feedback.id))
}
// -[END]- Admin Actions

</script>

<template>
  <div class="border border-border rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer relative group" @click="router.visit(`/feedback/${$props.feedback.id}`)">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-sm text-muted-foreground">
            by {{ $props.feedback.author }} • {{ new Date($props.feedback.created_at).toLocaleDateString() }}
          </span>
        </div>

        <h3 class="text-xl font-semibold mb-2">{{ truncateString($props.feedback.title, 32) }}</h3>
        <p class="text-muted-foreground mb-4">{{ truncateString($props.feedback.description, 100) }}</p>

        <div class="flex items-center gap-4">
          <div class="flex items-center gap-1">
            <Button
              variant="ghost"
              size="sm"
              :class="[
                'h-7 w-7 rounded-full transition-all duration-200',
                'hover:bg-primary/10 hover:scale-110',
                userHasVoted ? 'bg-primary/20 text-primary' : 'text-muted-foreground',
                !$props.isAuthenticated ? 'cursor-not-allowed opacity-50' : ''
              ]"
              :disabled="!$props.isAuthenticated || isVoting"
              @click.stop="vote('up')"
            >
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
              </svg>
            </Button>
            <span class="text-xs font-semibold text-foreground min-w-[16px] text-center">
              {{ $props.feedback.upvotes_count }}
            </span>
            <Button
              variant="ghost"
              size="sm"
              :class="[
                'h-7 w-7 rounded-full transition-all duration-200',
                'hover:bg-destructive/10 hover:scale-110',
                'text-muted-foreground',
                !$props.isAuthenticated ? 'cursor-not-allowed opacity-50' : ''
              ]"
              :disabled="!$props.isAuthenticated || isVoting"
              @click.stop="vote('down')"
            >
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </Button>
          </div>

          <div class="flex items-center gap-1">
            <Button
              variant="ghost"
              size="sm"
              class="text-muted-foreground hover:text-foreground"
              @click.stop
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              {{ $props.feedback.comments_count }}
            </Button>
          </div>
        </div>

        <div v-if="$props.feedback.tags.length" class="flex flex-wrap gap-2 mt-4">
          <Badge
            v-for="tag in $props.feedback.tags"
            :key="tag"
            variant="outline"
            class="text-xs"
          >
            {{ tag }}
          </Badge>
        </div>

      </div>

      <!-- Admin Actions Dropdown -->
      <div v-if="canManageFeedback" class="ml-4 transition-opacity" @click.stop>
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="h-8 w-8 p-0"
            >
              <MoreVertical class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-48">
            <DropdownMenuItem @click="editFeedback" class="cursor-pointer">
              <Pencil class="h-4 w-4 mr-2" />
              <span>Edit</span>
            </DropdownMenuItem>
            <DropdownMenuItem @click="deleteFeedback" class="cursor-pointer text-destructive focus:text-destructive" :disabled="isDeleting">
              <Trash2 class="h-4 w-4 mr-2" />
              <span>{{ isDeleting ? 'Deleting...' : 'Delete' }}</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  </div>
</template>