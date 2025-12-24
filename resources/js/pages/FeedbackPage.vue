<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import FeedbackLayout from '@/layouts/FeedbackLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { type FeedbacksResponse } from '@/types/feedback'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import { Badge } from '@/components/ui/badge'
import { Select } from '@/components/ui/select'
import Dialog from '@/components/ui/dialog/Dialog.vue'
import DialogContent from '@/components/ui/dialog/DialogContent.vue'
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue'
import DialogTrigger from '@/components/ui/dialog/DialogTrigger.vue'
import Label from '@/components/ui/label/Label.vue'
import { TagSelector } from '@/components/ui/tag-selector'
import { Alert } from '@/components/ui/alert'
import FeedbackBoard from '@/components_project/feedback/FeedbackBoard.vue'
import Ckeditor from '@/components/ui/ckeditor/Ckeditor.vue'
import { createFeedback as apiCreateFeedback, filterFeedbacks as apiFilterFeedbacks } from '@/api_requests/feedback/feedback'
import { FEEDBACK_STATUSES } from '@/config/feedbackStatuses'

interface Props {
  feedbacks: FeedbacksResponse
  canManageFeedback?: boolean
}

const props = defineProps<Props>()
const page = usePage()

// -[START]- Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Feedback',
    href: '/feedback',
  },
]
// -[END]- Breadcrumbs

// -[START]- Filters
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedTag = ref('')
const selectedTime = ref('')

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'planned', label: 'Planned' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'closed', label: 'Closed' },
]

const timeOptions = [
  { value: '', label: 'All Time' },
  { value: 'today', label: 'Today' },
  { value: 'this_week', label: 'This Week' },
  { value: 'this_month', label: 'This Month' },
  { value: 'this_year', label: 'This Year' },
]

const filterFeedbacks = async () => {
  isLoading.value = true

  const params = {
    search: searchQuery.value,
    status: selectedStatus.value,
    tag: selectedTag.value,
    time: selectedTime.value,
  }

  // Clear filters if no filters are applied (show all feedback in board view)
  const hasFilters = searchQuery.value || selectedStatus.value || selectedTag.value || selectedTime.value
  if (!hasFilters) {
    router.get('/feedback', {}, { preserveState: true, preserveScroll: true })
    isLoading.value = false
    return
  }

  try {
    const data = await apiFilterFeedbacks(params)
    // Update the feedbacks data in the page props
    page.props.feedbacks = data
  } catch (error) {
    console.error('Failed to filter feedbacks:', error)
  } finally {
    isLoading.value = false
  }
}

let searchTimeout: number
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(filterFeedbacks, 300)
}
// -[END]- Filters

// -[START]- Create Feedback Form
const showCreateDialog = ref(false)
const createForm = ref({
  title: '',
  description: '',
  tags: [] as string[],
})
const tagInput = ref('')
const editorInstance = ref<any>(null)

const defaultTags = ['feature', 'bug', 'question']

const handleNewFeedbackClick = () => {
  if (!(page.props as any).auth?.user) {
    router.visit('/login')
  } else {
    showCreateDialog.value = true
  }
}

const handleEditorReady = (editor: any) => {
  editorInstance.value = editor
  editor.setData('')
}

const addDefaultTag = (tag: string) => {
  if (!createForm.value.tags.includes(tag)) {
    createForm.value.tags.push(tag)
  }
}

const addTag = () => {
  const tag = tagInput.value.trim()
  if (tag && !createForm.value.tags.includes(tag)) {
    createForm.value.tags.push(tag)
    tagInput.value = ''
  }
}

const removeTag = (tag: string) => {
  createForm.value.tags = createForm.value.tags.filter(t => t !== tag)
}

const createFeedback = () => {
  if (!editorInstance.value) return

  isCreating.value = true

  const content = editorInstance.value.getData()
  const parser = new DOMParser()
  const doc = parser.parseFromString(content, 'text/html')
  const htmlContent = doc.body.innerHTML.trim()

  const formData = {
    ...createForm.value,
    description: htmlContent,
  }

  apiCreateFeedback(formData, () => {
    showCreateDialog.value = false
    createForm.value = { title: '', description: '', tags: [] }
    tagInput.value = ''
    if (editorInstance.value) {
      editorInstance.value.setData('')
    }
  }, () => {
    isCreating.value = false
  })
}
// -[END]- Create Feedback Form

// -[START]- Loading States
const isLoading = ref(false)
const isCreating = ref(false)
// -[END]- Loading States
</script>

<template>
  <Head title="Feedback" />

  <FeedbackLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto px-4 md:max-w-7xl mt-16">
      <!-- Authentication Prompt -->
      <div v-if="!(page.props as any)?.auth?.user" class="mb-6">
        <Alert
          variant="info"
          title="Sign in to participate"
          description="Create feedback, vote on suggestions, and join the conversation. Your input helps us build better features!"
          class="border-blue-200 bg-blue-50/50"
        />
      </div>

      <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold">Feedback</h1>
          <p class="text-muted-foreground">
            Share your ideas and help us improve our product
          </p>
        </div>

        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child>
            <Button class="bg-primary text-primary-foreground hover:bg-primary/90" @click.prevent="handleNewFeedbackClick">
              <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              New Feedback
            </Button>
          </DialogTrigger>

          <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Create New Feedback</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="createFeedback" class="space-y-6">
              <div>
                <Label class="mb-3 text-sm font-medium" for="title">Title</Label>
                <Input
                  id="title"
                  v-model="createForm.title"
                  placeholder="Brief title for your feedback"
                  class="h-11"
                  required
                />
              </div>

              <div>
                <Label class="mb-3 text-sm font-medium" for="description">Description</Label>
                <div class="border rounded-md overflow-hidden">
                  <Ckeditor
                    ref="editor"
                    :enable-title="false"
                    @editor-ready="handleEditorReady"
                  />
                </div>
              </div>

              <div>
                <Label class="mb-3 text-sm font-medium" for="tags">Tags (optional)</Label>

                <!-- Default Tags -->
                <div class="mb-4">
                  <p class="text-sm text-muted-foreground mb-3">Quick select:</p>
                  <div class="flex flex-wrap gap-2">
                    <Button
                      v-for="tag in defaultTags"
                      :key="tag"
                      type="button"
                      variant="outline"
                      size="sm"
                      class="h-9 px-3 transition-all duration-200"
                      :class="createForm.tags.includes(tag) ? 'bg-primary/10 border-primary text-primary hover:bg-primary/20' : 'hover:bg-muted'"
                      @click="addDefaultTag(tag)"
                    >
                      {{ tag }}
                    </Button>
                  </div>
                </div>

                <!-- Custom Tag Input -->
                <div class="flex gap-3 mb-4">
                  <Input
                    v-model="tagInput"
                    placeholder="Add custom tag"
                    class="h-10"
                    @keydown.enter.prevent="addTag"
                  />
                  <Button type="button" variant="outline" class="h-10 px-4" @click="addTag">
                    Add
                  </Button>
                </div>

                <!-- Selected Tags -->
                <div v-if="createForm.tags.length" class="flex flex-wrap gap-2">
                  <Badge
                    v-for="tag in createForm.tags"
                    :key="tag"
                    variant="secondary"
                    class="cursor-pointer transition-all duration-200 hover:bg-secondary/80"
                    @click="removeTag(tag)"
                  >
                    {{ tag }} ×
                  </Badge>
                </div>
              </div>

              <div class="flex justify-end gap-3 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  class="h-11 px-6"
                  @click="showCreateDialog = false"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  :disabled="isCreating"
                  class="bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-6"
                >
                  {{ isCreating ? 'Creating...' : 'Create Feedback' }}
                </Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Filters -->
      <Card>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
              <Label class="mb-2" for="search">Search</Label>
              <Input
                id="search"
                v-model="searchQuery"
                placeholder="Search feedback..."
                class="h-10"
                @input="debouncedSearch"
              />
            </div>

            <div>
              <Label class="mb-2" for="status">Status</Label>
              <Select
                v-model="selectedStatus"
                :options="statusOptions"
                placeholder="All Status"
                @update:model-value="filterFeedbacks"
              />
            </div>

            <div>
              <Label class="mb-2" for="tag">Tag</Label>
              <TagSelector
                v-model="selectedTag"
                placeholder="Filter by tag..."
                @update:model-value="filterFeedbacks"
              />
            </div>

            <div>
              <Label class="mb-2" for="time">Time</Label>
              <Select
                v-model="selectedTime"
                :options="timeOptions"
                placeholder="All Time"
                @update:model-value="filterFeedbacks"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Feedback Board -->
      <div v-if="isLoading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <p class="mt-2 text-muted-foreground">Loading feedback...</p>
      </div>

      <div v-else-if="feedbacks.data.length === 0" class="text-center py-8">
        <p class="text-muted-foreground">No feedback found matching your filters.</p>
      </div>

      <FeedbackBoard
        v-else
        :feedbacks="feedbacks.data"
        :statuses="FEEDBACK_STATUSES"
        :is-authenticated="!!(page.props as any).auth.user"
        :can-manage-feedback="props.canManageFeedback"
      />

      <!-- Pagination -->
      <div v-if="feedbacks.last_page > 1" class="flex justify-center">
        <div class="flex gap-2">
          <Button
            v-for="page in feedbacks.last_page"
            :key="page"
            :variant="page === feedbacks.current_page ? 'default' : 'outline'"
            size="sm"
            @click="router.get('/feedback', { page, status: selectedStatus, tag: selectedTag, search: searchQuery, time: selectedTime }, { preserveState: true, preserveScroll: true })"
          >
            {{ page }}
          </Button>
        </div>
      </div>
      </div>
    </div>
  </FeedbackLayout>
</template>