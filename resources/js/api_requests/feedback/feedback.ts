import { router } from '@inertiajs/vue3'
import axios from 'axios'

// -[START]- Feedback CRUD
export const createFeedback = (form: any, onSuccess?: () => void, onFinish?: () => void) => {
  router.post('/feedback', form, {
    preserveScroll: true,
    onSuccess,
    onFinish,
  })
}

export const deleteFeedbackApi = async (feedbackId: string) => {
  const response = await axios.delete(`/feedback/${feedbackId}`)
  return response.data
}

export const filterFeedbacks = async (params: any) => {
  const response = await axios.get('/feedback/filter', { params })
  return response.data
}

export const updateFeedbackApi = async (feedbackId: string, form: any) => {
  const response = await axios.patch(`/feedback/${feedbackId}/edit`, form)
  return response.data
}
// -[END]- Feedback CRUD

// -[START]- Feedback Voting
export const voteOnFeedback = async (feedbackId: string, direction: string) => {
  const response = await axios.post(`/feedback/${feedbackId}/vote`, { direction })
  return response.data
}
// -[END]- Feedback Voting

// -[START]- Feedback Status Management
export const updateFeedbackStatus = async (feedbackId: string, status: string) => {
  const response = await axios.patch(`/feedback/${feedbackId}/status`, { status })
  return response.data
}
// -[END]- Feedback Status Management