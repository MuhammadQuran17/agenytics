<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import FeedbackLayout from '@/layouts/FeedbackLayout.vue'
import { type BreadcrumbItem } from '@/types'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import { Select } from '@/components/ui/select'
import { submitComment } from '@/api_requests/feedback/comments'
import { voteOnFeedback, updateFeedbackStatus } from '@/api_requests/feedback/feedback'
import { truncateString } from '@/lib/utils'
import { Alert } from '@/components/ui/alert'

interface Props {
  feedback: {
    id: string
    title: string
    description: string
    created_at: string
    status: string
    upvotes_count: number
    comments_count: number
    author: string
    tags: string[]
  }
  userHasVoted: boolean
  totalVotes: number
  canManageFeedback: boolean
  comments: {
    id: string
    message: string
    created_at: string
    user: {
      id: string
      name: string
    }
  }[]
}

const props = defineProps<Props>()
const page = usePage()

// -[START]- Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Feedback',
    href: '/feedback',
  },
  {
    title: truncateString(props.feedback.title, 30),
    href: `/feedback/${props.feedback.id}`,
  },
]
// -[END]- Breadcrumbs

// -[START]- Navigation
const goBack = () => {
  router.visit('/feedback')
}
// -[END]- Navigation

// -[START]- Voting
const isVoting = ref(false)
const userHasVoted = ref(props.userHasVoted)

const vote = async (direction: 'up' | 'down') => {
  if (!(page.props as any)?.auth?.user || isVoting.value) return

  isVoting.value = true
  try {
    const data = await voteOnFeedback(props.feedback.id, direction)
    const change = data.change || 0
    props.feedback.upvotes_count += change

    if (direction === 'up') {
      userHasVoted.value = change > 0
    } else {
      userHasVoted.value = false
    }
  } catch (error) {
    console.error('Failed to vote:', error)
  } finally {
    isVoting.value = false
  }
}
// -[END]- Voting

// -[START]- Comments
const newComment = ref('')
const isSubmittingComment = ref(false)

const submitCommentHandler = async () => {
  if (!(page.props as any)?.auth?.user || !newComment.value.trim() || isSubmittingComment.value) return

  isSubmittingComment.value = true
  try {
    const data = await submitComment(props.feedback.id, newComment.value)
    if (data.comment) {
      props.comments.unshift(data.comment)
      props.feedback.comments_count += 1
      newComment.value = ''
    }
  } catch (error) {
    console.error('Failed to submit comment:', error)
  } finally {
    isSubmittingComment.value = false
  }
}
// -[END]- Comments

// -[START]- Status Management
const statusOptions = [
  { value: 'planned', label: 'Planned' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'closed', label: 'Closed' },
]

const isUpdatingStatus = ref(false)

const updateStatus = async (newStatus: string) => {
  if (isUpdatingStatus.value || !props.canManageFeedback) return

  isUpdatingStatus.value = true
  try {
    const data = await updateFeedbackStatus(props.feedback.id, newStatus)
    props.feedback.status = data.status
  } catch (error) {
    console.error('Failed to update status:', error)
  } finally {
    isUpdatingStatus.value = false
  }
}
// -[END]- Status Management
</script>

<template>
  <Head :title="truncateString(feedback.title, 50)" />

  <FeedbackLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto px-4 md:max-w-4xl mt-16">
      <!-- Authentication Prompt -->
      <div v-if="!(page.props as any)?.auth?.user" class="mb-6">
        <Alert
          variant="info"
          title="Log in to participate"
          description="Vote on this feedback and join the conversation. Your input helps us build better features!"
          class="border-blue-200 bg-blue-50/50"
        />
      </div>
      <!-- Back Button as Breadcrumb -->
      <div class="mb-6">
        <Button
          variant="ghost"
          size="sm"
          @click="goBack"
          class="text-muted-foreground hover:text-foreground -ml-2"
        >
          <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Feedback
        </Button>
      </div>

      <div class="space-y-6">
        <!-- Feedback Header -->
        <div class="border border-border rounded-lg p-6">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm text-muted-foreground">
                  by {{ feedback.author }} • {{ new Date(feedback.created_at).toLocaleDateString() }}
                </span>
                <Badge variant="outline" class="text-xs">
                  {{ feedback.status }}
                </Badge>
              </div>

              <h1 class="text-2xl font-bold mb-3">{{ feedback.title }}</h1>
              <p class="text-muted-foreground mb-4 whitespace-pre-line">{{ feedback.description }}</p>

              <!-- Status Management -->
              <div v-if="canManageFeedback" class="mb-4">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium">Status:</span>
                  <Select
                    v-model="feedback.status"
                    :options="statusOptions"
                    :disabled="isUpdatingStatus"
                    class="w-40"
                    @update:model-value="updateStatus"
                  />
                  <span v-if="isUpdatingStatus" class="text-xs text-muted-foreground">Updating...</span>
                </div>
              </div>

              <!-- Enhanced Voting UI -->
              <div class="flex items-center gap-6">
                <div class="flex items-center gap-1">
                  <Button
                    variant="ghost"
                    size="sm"
                    :class="[
                      'h-8 w-8 rounded-full transition-all duration-200',
                      'hover:bg-primary/10 hover:scale-110',
                      userHasVoted ? 'bg-primary/20 text-primary' : 'text-muted-foreground',
                      !(page.props as any)?.auth?.user ? 'cursor-not-allowed opacity-50' : ''
                    ]"
                    :disabled="!(page.props as any)?.auth?.user || isVoting"
                    @click="vote('up')"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                  </Button>
                  <span class="text-sm font-semibold text-foreground min-w-[20px] text-center">
                    {{ feedback.upvotes_count }}
                  </span>
                  <Button
                    variant="ghost"
                    size="sm"
                    :class="[
                      'h-8 w-8 rounded-full transition-all duration-200',
                      'hover:bg-destructive/10 hover:scale-110',
                      'text-muted-foreground',
                      !(page.props as any)?.auth?.user ? 'cursor-not-allowed opacity-50' : ''
                    ]"
                    :disabled="!(page.props as any)?.auth?.user || isVoting"
                    @click="vote('down')"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </Button>
                </div>

                <div class="flex items-center gap-4">
                  <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="text-sm text-muted-foreground">{{ feedback.comments_count }} comments</span>
                  </div>
                </div>
              </div>

              <div v-if="feedback.tags.length" class="flex flex-wrap gap-2 mt-4">
                <Badge
                  v-for="tag in feedback.tags"
                  :key="tag"
                  variant="outline"
                  class="text-xs"
                >
                  {{ tag }}
                </Badge>
              </div>
            </div>
          </div>
        </div>

        <!-- Comments Section -->
        <div class="border border-border rounded-lg p-6">
          <h2 class="text-xl font-semibold mb-6">Comments ({{ comments.length }})</h2>

          <!-- Add Comment Form -->
          <div v-if="(page.props as any)?.auth?.user" class="mb-6">
            <div class="flex gap-3 items-end">
              <Textarea
                v-model="newComment"
                placeholder="Add a comment..."
                rows="3"
                class="flex-1"
              />
              <Button
                @click="submitCommentHandler"
                :disabled="!newComment.trim() || isSubmittingComment"
                class="bg-primary text-primary-foreground hover:bg-primary/90 shrink-0"
              >
                {{ isSubmittingComment ? 'Posting...' : 'Post' }}
              </Button>
            </div>
          </div>

          <!-- Comments List -->
          <div v-if="comments.length === 0" class="text-center py-8">
            <p class="text-muted-foreground">No comments yet. Be the first to comment!</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="comment in comments"
              :key="comment.id"
              class="border border-border rounded-lg p-4"
            >
              <div class="flex items-center gap-2 mb-2">
                <span class="font-medium text-sm">{{ comment.user.name }}</span>
                <span class="text-xs text-muted-foreground">
                  {{ new Date(comment.created_at).toLocaleDateString() }}
                </span>
              </div>
              <p class="text-sm">{{ comment.message }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FeedbackLayout>
</template>