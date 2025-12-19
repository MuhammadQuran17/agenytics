<script setup lang="ts">
import FeedbackHeader from '@/components_project/feedback/layout/FeedbackHeader.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from '@/components/ui/sonner';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="min-h-screen bg-background">
        <FeedbackHeader />
        <main class="flex-1 pb-5">
            <slot />
        </main>
        <Toaster position="top-center" richColors />
    </div>
</template>