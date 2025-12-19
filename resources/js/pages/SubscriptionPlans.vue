<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { 
  Card, 
  CardHeader, 
  CardTitle, 
  CardContent, 
  CardFooter 
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Check, Sparkles, Shield, Zap } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';

// Define props for the page
interface Plan {
  name: string;
  description: string;
  price: number;
  price_type: 'monthly' | 'one_time';
  stripe_price_id?: string;
  stripe_product_id?: string;
  features?: string[];
}

interface Props {
  plans: Record<string, Plan>;
  isEverSubscribed?: boolean;
}

const props = defineProps<Props>();
const isEverSubscribed = computed(() => props.isEverSubscribed || false);

// Define breadcrumbs for the page
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Subscription Plans',
    href: route('subscription-plans'),
  },
];

// Define hover state for each plan card
const hoveredPlan = ref<string | null>(null);

// Set hover state
const setHovered = (planId: string | null) => {
  hoveredPlan.value = planId;
};

// Function to handle subscription
const subscribe = (planId: string) => {
  window.location.href = route('subscribeOrPurchase', { plan: planId });
};

// Plan icons mapping
const planIcons = {
  'own_api_key': Shield,
  'basic': Zap,
  'advanced': Sparkles
};

// Get plan icon
const getPlanIcon = (planId: string) => {
  return planIcons[planId as keyof typeof planIcons] || Shield;
};

// Animation states
const isLoaded = ref(false);
const animationStaggerDelay = 100; // ms

onMounted(() => {
  // Trigger animations after a short delay
  setTimeout(() => {
    isLoaded.value = true;
  }, 100);
});
</script>

<template>
  <Head title="Subscription Plans" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="relative overflow-hidden">
      <!-- Background gradient effect -->
      <div class="absolute inset-0 bg-gradient-to-b from-background to-secondary pointer-events-none"></div>
      
      <!-- Animated background shapes -->
      <div 
        class="absolute top-20 left-10 w-64 h-64 bg-primary/6 rounded-full blur-2xl opacity-0 transition-all duration-1000" 
        :class="{ 'opacity-100 translate-y-0': isLoaded, 'opacity-0 translate-y-8': !isLoaded }"
      ></div>
      <div 
        class="absolute bottom-10 right-10 w-66 h-66 bg-primary/6 rounded-full blur-2xl opacity-0 transition-all duration-1000 delay-300" 
        :class="{ 'opacity-100 translate-y-0': isLoaded, 'opacity-0 translate-y-8': !isLoaded }"
      ></div>
      <div 
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-secondary/10 rounded-full blur-2xl opacity-0 transition-all duration-1000 delay-150" 
        :class="{ 'opacity-100 scale-100': isLoaded, 'opacity-0 scale-95': !isLoaded }"
      ></div>
      
      <div class="container relative mx-auto py-20 pt-32 px-4">
        <!-- Header section with enhanced typography -->
        <div class="text-center mb-8">
          <h1 
            class="text-4xl md:text-5xl font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-primary to-primary/80 opacity-0 transform transition-all duration-700" 
            :class="{ 'opacity-100 translate-y-0': isLoaded, 'opacity-0 translate-y-4': !isLoaded }"
          >
            Choose Your Subscription Plan
          </h1>
          <p 
            v-if="!isEverSubscribed"
            class="text-muted-foreground max-w-2xl mx-auto text-lg opacity-0 transform transition-all duration-700 delay-150" 
            :class="{ 'opacity-100 translate-y-0': isLoaded, 'opacity-0 translate-y-4': !isLoaded }"
          >
            Select the plan that best fits your needs.
          </p>
        </div>

        <!-- Manage Subscription Button (if already subscribed) -->
        <div v-if="isEverSubscribed" class="text-center mb-12 opacity-0 transform transition-all duration-700 delay-300"
             :class="{ 'opacity-100 translate-y-0': isLoaded, 'opacity-0 translate-y-4': !isLoaded }">
          <a href="https://billing.stripe.com/p/login/test_28EaEXgbsdLx1qj22sbjW00" target="_blank" rel="noopener">
            <Button 
              class="bg-primary text-primary-foreground hover:bg-primary/90 px-6 py-2 text-lg"
            >
              Manage Your Subscription
            </Button>
          </a>
        </div>
        
        <!-- Plans grid with enhanced cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 max-w-6xl mx-auto grid-flow-row auto-rows-fr">
          <!-- Loop through all plans -->
          <div 
            v-for="(plan, planId, index) in props.plans" 
            :key="planId"
            class="flex opacity-0 transform transition-all duration-700 h-full"
            :style="{ transitionDelay: `${300 + (index * animationStaggerDelay)}ms` }"
            :class="{ 'opacity-100 translate-y-0': isLoaded, 'opacity-0 translate-y-8': !isLoaded }"
            @mouseenter="setHovered(planId)"
            @mouseleave="setHovered(null)"
          >
            <Card 
              :class="[
                'relative w-full border-2 transition-all duration-300 overflow-hidden flex flex-col',
                'border-border hover:border-primary/30 hover:shadow-lg hover:shadow-primary/10',
                hoveredPlan === planId ? 'scale-[1.02] z-10' : 'scale-100'
              ]"
            >
             
              <!-- Plan icon with gradient background -->
              <div class="absolute top-6 right-6">
                <div 
                  :class="[
                    'flex items-center justify-center rounded-full p-2',
                    planId === 'advanced' ? 'bg-primary/10' : 'bg-secondary'
                  ]"
                >
                  <component 
                    :is="getPlanIcon(planId)" 
                    :class="[
                      'size-5',
                      planId === 'advanced' ? 'text-primary' : 'text-primary/70'
                    ]"
                  />
                </div>
              </div>
              
              <CardHeader>
                <CardTitle class="text-2xl font-bold">{{ plan.name }}</CardTitle>
              </CardHeader>
              
              <CardContent class="space-y-6 flex flex-col" style="flex: 1">
                <!-- Price with enhanced styling -->
                <div class="flex items-baseline gap-2">
                  <span class="text-3xl font-bold">$</span><span class="text-4xl font-bold">{{ Math.floor(plan.price) }}<span class="text-2xl font-normal">.{{ String(plan.price).split('.')[1] || '00' }}</span></span>
                  <span v-if="plan.price_type === 'monthly'" class="text-muted-foreground">/month</span>
                  <span v-if="plan.price_type === 'one_time'" class="text-muted-foreground">/one time</span>
                </div>
                
                <!-- Description with better typography -->
                <p class="text-sm text-muted-foreground leading-relaxed">
                  {{ plan.description }}
                </p>
                
                <!-- Features list with enhanced styling -->
                <div v-if="plan.features && plan.features.length > 0" class="pt-4 border-t border-border">
                  <ul class="space-y-3">
                    <li v-for="(feature, index) in plan.features" :key="index" class="flex gap-3">
                      <div class="flex-shrink-0 rounded-full bg-primary/10 p-1">
                        <Check class="size-4 text-primary" />
                      </div>
                      <span class="text-sm">{{ feature }}</span>
                    </li>
                  </ul>
                </div>
                
                <!-- Spacer to push button to bottom -->
                <div class="flex-grow"></div>
              </CardContent>
              
              <CardFooter class="pt-6">
                <Button 
                  :class="[
                    'w-full transition-all duration-300 font-medium',
                    planId === 'advanced'
                      ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                      : 'bg-secondary text-secondary-foreground hover:bg-primary hover:text-primary-foreground'
                  ]"
                  @click="subscribe(planId)"
                >
                  Subscribe Now
                </Button>
              </CardFooter>
            </Card>
          </div>
        </div>
        
        <!-- FAQ section -->
        <div class="mt-24 max-w-3xl mx-auto opacity-0 transform transition-all duration-700 delay-800"
             :class="{ 'opacity-100 translate-y-0': isLoaded, 'opacity-0 translate-y-4': !isLoaded }">
          <h2 class="text-2xl font-bold text-center mb-8">Frequently Asked Questions</h2>
          
          <div class="space-y-6">
            <div class="border border-border rounded-lg p-6">
              <h3 class="font-medium mb-2">How do we calculate the price of prompts?</h3>
              <p class="text-sm text-muted-foreground">We have counted the tokens used for the prompt that was used as an example in the documentation.</p>
            </div>

            <div class="border border-border rounded-lg p-6">
              <h3 class="font-medium mb-2">What does mean "for 6 months from last purchase"?</h3>
              <p class="text-sm text-muted-foreground">It means that you will get 200 prompts for 6 months. If you don't use them in this period, you will need to purchase again. Why we did this? The project features, Ai Providers pricing changes, and can affect the price of prompts. However, if you will purchase prompts in this period again, your deadline will be automatically extended for another 6 months. </p>
            </div>

            <div class="border border-border rounded-lg p-6">
              <h3 class="font-medium mb-2">Can I switch plans later?</h3>
              <p class="text-sm text-muted-foreground">Yes. It is handled by Stripe.</p>
            </div>
            
            <div class="border border-border rounded-lg p-6">
              <h3 class="font-medium mb-2">How do the extra prompts work?</h3>
              <p class="text-sm text-muted-foreground">Once you exceed your monthly prompt limit, you'll be charged at the end of the month at the per-prompt rate shown in your plan details. You can see your usage in Stripe dashboard. </p>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
