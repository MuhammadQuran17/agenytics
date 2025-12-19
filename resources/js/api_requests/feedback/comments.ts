import axios from 'axios'

export const submitComment = async (feedbackId: string, message: string) => {
  const response = await axios.post(`/feedback/${feedbackId}/comment`, { message })
  return response.data
}

export const loadComments = async (feedbackId: string) => {
  const response = await axios.get(`/feedback/${feedbackId}/comments`)
  return response.data
}