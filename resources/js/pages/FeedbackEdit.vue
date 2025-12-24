<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import FeedbackLayout from '@/layouts/FeedbackLayout.vue'
import { type BreadcrumbItem } from '@/types'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import Input from '@/components/ui/input/Input.vue'
import { Select } from '@/components/ui/select'
import Label from '@/components/ui/label/Label.vue'
import { Alert } from '@/components/ui/alert'
import { TagSelector } from '@/components/ui/tag-selector'
import Ckeditor from '@/components/ui/ckeditor/Ckeditor.vue'
import { toast } from 'vue-sonner'
import { updateFeedbackApi } from '@/api_requests/feedback/feedback'

interface Props {
  feedback: {
    id: string
    title: string
    description: string
    tags: string[]
    status: string
  }
  canEdit: boolean
}

const props = defineProps<Props>()

// -[START]- Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Feedback',
    href: '/feedback',
  },
  {
    title: 'Edit',
    href: `/feedback/${props.feedback.id}/edit`,
  },
]
// -[END]- Breadcrumbs

// -[START]- Form State
const form = reactive({
  title: props.feedback.title,
  description: props.feedback.description,
  tags: props.feedback.tags || [],
  status: props.feedback.status,
})

const isSubmitting = ref(false)
const error = ref<string | null>(null)
const editorInstance = ref<any>(null)

const statusOptions = [
  { value: 'planned', label: 'Planned' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'closed', label: 'Closed' },
]

const handleEditorReady = (editor: any) => {
  editorInstance.value = editor
  editor.setData(props.feedback.description || '')
}

const handleSubmit = async () => {
  if (!props.canEdit) return

  if (!editorInstance.value) return

  const content = editorInstance.value.getData()
  const parser = new DOMParser()
  const doc = parser.parseFromString(content, 'text/html')
  const htmlContent = doc.body.innerHTML.trim()

  if (!form.title.trim() || !htmlContent) {
    error.value = 'Title and description are required'
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    const formData = {
      ...form,
      description: htmlContent,
    }
    const data = await updateFeedbackApi(props.feedback.id, formData)
    if (data.message) {
      toast.success(data.message)
      router.visit(route('feedback.list'))
      isSubmitting.value = false
    }
  } catch (error) {
    console.error('Failed to update feedback:', error)
    toast.error('Failed to update feedback')
  }
}

const handleCancel = () => {
  router.visit(`/feedback/${props.feedback.id}`)
}
// -[END]- Form State
</script>

<template>
  <Head title="Edit Feedback" />
  <FeedbackLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <!-- Access Control Alert -->
        <Alert v-if="!canEdit" class="mb-6 bg-destructive/10 border-destructive/30">
          <div class="text-destructive">
            <p class="font-semibold">Access Denied</p>
            <p class="text-sm">You do not have permission to edit this feedback.</p>
          </div>
        </Alert>

        <!-- Edit Form Card -->
        <Card>
          <CardContent class="pt-6">
            <h1 class="text-2xl font-bold mb-6">Edit Feedback</h1>

            <!-- Error Alert -->
            <Alert v-if="error" class="mb-6 bg-destructive/10 border-destructive/30">
              <div class="text-destructive">
                <p class="font-semibold">Error</p>
                <p class="text-sm">{{ error }}</p>
              </div>
            </Alert>

            <form @submit.prevent="handleSubmit" class="space-y-6">
              <!-- Title Field -->
              <div>
                <Label for="title" class="mb-2">Title</Label>
                <Input
                  id="title"
                  v-model="form.title"
                  type="text"
                  placeholder="Enter feedback title"
                  :disabled="!canEdit || isSubmitting"
                  required
                />
              </div>

              <!-- Description Field -->
              <div>
                <Label for="description" class="mb-2">Description</Label>
                <div class="border rounded-md overflow-hidden" :class="{ 'opacity-50 pointer-events-none': !canEdit || isSubmitting }">
                  <Ckeditor
                    ref="editor"
                    :enable-title="false"
                    @editor-ready="handleEditorReady"
                  />
                </div>
              </div>

              <!-- Status Field -->
              <div>
                <Label for="status" class="mb-2">Status</Label>
                <Select
                  v-model="form.status"
                  :options="statusOptions"
                  :disabled="!canEdit || isSubmitting"
                />
              </div>

              <!-- Action Buttons -->
              <div class="flex justify-between pt-4">
                <Button
                  type="button"
                  variant="outline"
                  @click="handleCancel"
                  :disabled="isSubmitting"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  :disabled="!canEdit || isSubmitting"
                  :loading="isSubmitting"
                >
                  {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </FeedbackLayout>
</template>

