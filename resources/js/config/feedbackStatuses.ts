import { type FeedbackStatus } from '@/types/feedback'

export const FEEDBACK_STATUSES: FeedbackStatus[] = [
  {
    key: 'planned',
    label: 'Planned',
    color: 'bg-secondary',
    order: 1,
  },
  {
    key: 'in_progress',
    label: 'In Progress',
    color: 'bg-blue-500',
    order: 2,
  },
  {
    key: 'completed',
    label: 'Completed',
    color: 'bg-green-500',
    order: 3,
  },
  {
    key: 'closed',
    label: 'Closed',
    color: 'bg-gray-500',
    order: 4,
  },
]

export const getStatusByKey = (key: string): FeedbackStatus | undefined => {
  return FEEDBACK_STATUSES.find(status => status.key === key)
}

export const getStatusColor = (key: string): string => {
  return getStatusByKey(key)?.color || 'bg-secondary'
}

export const getStatusLabel = (key: string): string => {
  return getStatusByKey(key)?.label || key.replace('_', ' ')
}