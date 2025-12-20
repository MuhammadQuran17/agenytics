<script setup lang="ts">
import { ref, inject, computed, watch, nextTick, type Ref } from 'vue';
import { usePage } from "@inertiajs/vue3";
import type { SharedData, User } from "@/types";
import axios from 'axios';
import ChatInput from '@/components_project/chat/ChatInput.vue';
import { useAiChatStore } from '@/store/aiChatStore';
import { toast } from 'vue-sonner';
import ChatMessageLoading from '@/components_project/chat/ChatMessageLoading.vue';
import { router } from '@inertiajs/vue3';
import { marked } from 'marked';
import { useChatPolling } from '@/composables/useChatPolling';

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
const { startPolling } = useChatPolling({
    currentChatSessionId,
    messages,
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
                            class="mb-16 max-w-[90%] markdown-content"
                            :ref="(el: any) => { if (el) messageRefs[index] = el as HTMLElement }"
                            v-html="message.content && marked.parse(message.content as string)"
                        ></div>
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

.markdown-content {
  color: var(--foreground);
  line-height: 1.75;
}

.markdown-content :deep(h1),
.markdown-content :deep(h2),
.markdown-content :deep(h3),
.markdown-content :deep(h4),
.markdown-content :deep(h5),
.markdown-content :deep(h6) {
  font-weight: 600;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
  line-height: 1.25;
}

.markdown-content :deep(h1) {
  font-size: 2em;
}

.markdown-content :deep(h2) {
  font-size: 1.5em;
}

.markdown-content :deep(h3) {
  font-size: 1.25em;
}

.markdown-content :deep(p) {
  margin-top: 1em;
  margin-bottom: 1em;
}

.markdown-content :deep(ul),
.markdown-content :deep(ol) {
  margin-top: 1em;
  margin-bottom: 1em;
  padding-left: 2em;
}

.markdown-content :deep(li) {
  margin-top: 0.5em;
  margin-bottom: 0.5em;
}

.markdown-content :deep(strong) {
  font-weight: 600;
}

.markdown-content :deep(em) {
  font-style: italic;
}

.markdown-content :deep(code) {
  background-color: var(--muted);
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-family: var(--font-mono);
  font-size: 0.875em;
}

.markdown-content :deep(pre) {
  background-color: var(--muted);
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin-top: 1em;
  margin-bottom: 1em;
}

.markdown-content :deep(pre code) {
  background-color: transparent;
  padding: 0;
}

.markdown-content :deep(blockquote) {
  border-left: 4px solid var(--border);
  padding-left: 1em;
  margin-left: 0;
  margin-top: 1em;
  margin-bottom: 1em;
  color: var(--muted-foreground);
  font-style: italic;
}

.markdown-content :deep(a) {
  color: var(--primary);
  text-decoration: underline;
}

.markdown-content :deep(a:hover) {
  color: var(--primary-foreground);
}
</style>