<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth as any);
const isAuthenticated = computed(() => !!auth.value?.user);

</script>

<template>  
  <header class="border-b border-border bg-background ">
    <div class="mx-auto flex h-16 items-center justify-between px-4 md:max-w-7xl">
      <!-- Logo -->
     <component :is="isAuthenticated ? Link : 'a'" :href="isAuthenticated ? route('dashboard') : route('home')" class="flex items-center gap-x-2">
       <AppLogo />
     </component>

      <!-- Right side buttons -->
      <div class="flex items-center gap-5">
        <template v-if="isAuthenticated">
          <Button as-child variant="default">
            <Link :href="route('chat.index')">Go to Chat</Link>
          </Button>
        </template>
        <template v-else>
          <Button as-child variant="outline">
            <Link :href="route('login')">Log In</Link>
          </Button>
        </template>
      </div>
    </div>
  </header>
</template>