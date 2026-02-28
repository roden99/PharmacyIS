<script setup>
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Button from '@/components/ui/buttonorig/Button.vue';
import CardFooter from './ui/card/CardFooter.vue';
import { Loader2 } from "lucide-vue-next";




const emit = defineEmits(['close', 'submit']);
const props = defineProps({

    cardTitle: {
        type: String,
        default: 'Card Title'
    },

    loading: {
        type: Boolean,
        default: true
    },

    text: {
        type: String,
        default: 'Save',
    }

});

</script>
<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Overlay to dim and "disable" the main form -->
        <div class="absolute inset-0 bg-gray-200 opacity-70 pointer-events-none"></div>
        <Card class="relative w-full max-w-3xl pointer-events-auto z-10 pb-0">
            <CardHeader>
                <CardTitle>{{ cardTitle }}</CardTitle>
            </CardHeader>

            <CardContent class="space-y-3 pb-0" :class="{ 'opacity-50 pointer-events-none': loading }">

                <slot></slot>

            </CardContent>
            <CardFooter class="flex justify-end space-x-2">
                <slot name="footer"> </slot>
            </CardFooter>
        </Card>
    </div>
</template>
