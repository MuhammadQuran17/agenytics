export interface Feedback {
  id: string
  title: string
  description: string
  created_at: string
  status: string
  upvotes_count: number
  comments_count: number
  author: string
  tags: string[]
  user_has_voted?: boolean
}

export interface FeedbackDetail extends Feedback {
  canChangeStatus?: boolean
}

export interface FeedbackStatus {
  key: string
  label: string
  color: string
  order: number
}

export interface FeedbacksResponse {
  data: Feedback[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface Comment {
  id: string
  message: string
  created_at: string
  user: {
    name: string
  }
}

export interface FeedbackVote {
  id: string
  user_id: string
  feedback_id: string
  created_at: string
}

export interface CreateFeedbackData {
  title: string
  description: string
  tags: string[]
}

export interface FeedbackFilters {
  search?: string
  status?: string
  tag?: string
  time?: string
}