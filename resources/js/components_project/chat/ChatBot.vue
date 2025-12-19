<script setup lang="ts">
import { ref, inject, provide, computed, watch, nextTick, onMounted, onBeforeUnmount, type Ref } from 'vue';
import { usePage } from "@inertiajs/vue3";
import type { SharedData, User } from "@/types";
import axios from 'axios';
import ChatInput from '@/components_project/chat/ChatInput.vue';
import { useAiChatStore } from '@/store/aiChatStore';
import { toast } from 'vue-sonner';
import ChatMessageLoading from '@/components_project/chat/ChatMessageLoading.vue';
import { router } from '@inertiajs/vue3';

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const currentChatSessionId = inject<Ref<string> | undefined>('currentChatSessionId');

const messageRefs = ref<HTMLElement[]>([]);

const aiChatStore = useAiChatStore();
const messages = computed(() => aiChatStore.currentChatHistory);
const hasActiveChatHistory = computed(() => aiChatStore.hasActiveChatHistory);
const isLoading = computed(() => currentChatSessionId?.value ? aiChatStore.isSessionProcessing(currentChatSessionId.value) : false);

// [START] Scroll to message
const scrollToMessage = (index: number) => {
    nextTick(() => {
        messageRefs.value[index]?.scrollIntoView({ behavior: 'smooth' });
    });
};

watch(messages, (newMessages) => {
    if (newMessages.length > 0) {
        scrollToMessage(newMessages.length - 1);
    }
}, { deep: true });
// [END] Scroll to message


// [START] Handle send message
const handleSendMessage = async (message: string) => {
    try {
        aiChatStore.addMessage({
            content: message,
            role: 'user',
            created_at: new Date().toISOString(),
        });

        const response = await axios.post(route('chat.send'), {
            message,
            sessionId: currentChatSessionId?.value,
        });

        const { jobId } = response.data;

        // Start polling for job status
        if (currentChatSessionId?.value) {
            startPolling(currentChatSessionId.value, jobId);
        }

    } catch (error: any) {
        toast.error(error.response?.data?.message || 'An error occurred');

        if(error.response?.status === 402) {
            router.visit(route('subscription-plans'));
        }
        
        if (currentChatSessionId?.value) {
            aiChatStore.stopPollingForSession(currentChatSessionId.value);
        }
    }
};
// [END] Handle send message


// [START] Polling state - session-aware
const pollingIntervalIds = ref<Map<string, number>>(new Map());
const pollingAttempts = ref<Map<string, number>>(new Map());
const maxPollingAttempts = 150; // 150 * 4 seconds = 10 minutes max

const stopPolling = (sessionId: string) => {
    const intervalId = pollingIntervalIds.value.get(sessionId);
    if (intervalId) {
        clearInterval(intervalId);
        pollingIntervalIds.value.delete(sessionId);
        pollingAttempts.value.delete(sessionId);
    }
};

const pollJobStatus = async (sessionId: string, jobId: string) => {
    try {
        const response = await axios.post(route('chat.status'), { 
            jobId: String(jobId) 
        });
        const { status, response: aiResponse, error } = response.data;

        if (status === 'completed') {
            stopPolling(sessionId);
            aiChatStore.stopPollingForSession(sessionId);

            if (aiResponse) {
                aiChatStore.addMessage({
                    content: aiResponse,
                    role: 'assistant',
                    created_at: new Date().toISOString(),
                });
            }
        } else if (status === 'failed') {
            stopPolling(sessionId);
            aiChatStore.stopPollingForSession(sessionId);
            toast.error(error || 'Processing failed');
        } else if (status === 'processing') {
            // Continue polling
            const attempts = (pollingAttempts.value.get(sessionId) || 0) + 1;
            pollingAttempts.value.set(sessionId, attempts);

            if (attempts >= maxPollingAttempts) {
                stopPolling(sessionId);
                aiChatStore.stopPollingForSession(sessionId);
                toast.error('Processing timeout. Please try again.');
            }
        }
    } catch (error: any) {
        stopPolling(sessionId);
        aiChatStore.stopPollingForSession(sessionId);
        toast.error(error.response?.data?.message || 'Failed to check job status');
    }
};

const startPolling = (sessionId: string, jobId: string) => {
    // Stop any existing polling for this session
    stopPolling(sessionId);
    
    pollingAttempts.value.set(sessionId, 0);
    aiChatStore.startPollingForSession(sessionId, jobId);
    
    const intervalId = window.setInterval(() => {
        pollJobStatus(sessionId, jobId);
    }, 4000); // Poll every 4 seconds
    
    pollingIntervalIds.value.set(sessionId, intervalId);
};

const resumePollingForSession = (sessionId: string, jobId: string) => {
    // Only resume if not already polling
    if (!pollingIntervalIds.value.has(sessionId)) {
        startPolling(sessionId, jobId);
    }
};

const checkAndResumePolling = () => {
    if (!currentChatSessionId?.value) return;
    
    // Load polling state from localStorage
    aiChatStore.loadPollingStateFromStorage();
    
    const activeJobId = aiChatStore.getActiveJobForSession(currentChatSessionId.value);
    
    if (activeJobId) {
        // Check if we already have the assistant response in chatHistory
        const lastMessage = messages.value[messages.value.length - 1];
        
        if (lastMessage && lastMessage.role === 'assistant') {
            // Job completed, clean up
            aiChatStore.cleanupCompletedJob(currentChatSessionId.value);
        } else {
            // Job still processing, resume polling
            resumePollingForSession(currentChatSessionId.value, activeJobId);
        }
    }
};

// On mount: check and resume polling if needed
onMounted(() => {
    checkAndResumePolling();
});

// Watch for chat session changes (navigation between chats)
watch(() => currentChatSessionId?.value, (newSessionId, oldSessionId) => {
    if (!newSessionId) return;
    
    if (oldSessionId && newSessionId !== oldSessionId) {
        // Stop polling interval for old session (but keep job in store)
        stopPolling(oldSessionId);
    }
    
    if (newSessionId) {
        // Check if new session has active job and resume if needed
        const activeJobId = aiChatStore.getActiveJobForSession(newSessionId);
        if (activeJobId) {
            resumePollingForSession(newSessionId, activeJobId);
        }
    }
});

// Clean up all polling on component unmount
onBeforeUnmount(() => {
    // Stop all active polling intervals
    pollingIntervalIds.value.forEach((_, sessionId) => {
        stopPolling(sessionId);
    });
}); 

// [END] Polling state - session-aware
</script>

<template>
    <div class="flex h-full flex-col relative">
        <div ref="chatContainer" class="flex-1 overflow-y-auto py-8 px-4 md:px-0">
            <div class="max-w-3xl mx-auto">
                <div v-if="!hasActiveChatHistory" class="text-center mb-16">
                    <h1 class="text-3xl font-medium text-foreground mb-4">Hello {{ user.name }}!</h1>
                    <p class="text-xl text-muted-foreground">What we should do today?</p>
                </div>

                <template v-else>
                    <template v-for="(message, index) in messages" :key="index">
                        <div 
                            v-if="message.role === 'assistant'" 
                            class="mb-16 max-w-[90%]"
                            :ref="(el: any) => { if (el) messageRefs[index] = el as HTMLElement }"
                        >
                            {{ message.content }}
                        </div>
                        <div 
                            v-else 
                            class="w-full"
                            :ref="(el: any) => { if (el) messageRefs[index] = el as HTMLElement }"
                        >
                            <div class="mb-6 ml-auto w-fit text-right bg-muted rounded-3xl py-2.5 px-5 text-medium">
                                <div class="text-secondary-foreground">{{ message.content }}</div>
                            </div>
                        </div>
                    </template>

                    <ChatMessageLoading v-if="isLoading" />
                </template>

            </div>
        </div>

        <ChatInput @send="handleSendMessage" />
    </div>
</template>

<style scoped>
.fade-enter-active {
  transition: opacity 1s ease;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}
</style>