<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, version } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';


import { CalendarDate, fromDate, getLocalTimeZone } from '@internationalized/date';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { normalizeDate, set } from '@vueuse/core';



const props = defineProps({
    // isProcessing: {
    //     type: Boolean,
    //     default: false,
    // },

    cardTitle: {
        type: String,
        default: 'Form',
    },

    // member: {
    //     type: Object,
    //     default: null,
    // },

    // transactionType: {
    //     type: String,
    //     default: 'create',
    // },



});


</script>

<template>
    <!-- <FormCard v-show="!isDialogOpen" :card-title="cardTitle"> -->

    <FormCard :loading="false" :card-title="cardTitle">
        <form @submit.prevent="Submit" class="space-y-6">
            <div class="border-b pb-3">
                <p class="text-sm">Fill up the form properly</p>
            </div>


            <div class="grid grid-cols-1 gap-6">

                <div class="grid w-full gap-2">
                    <Label for="member_lastname"> Supplier Name: <span class="text-red-500">*</span></Label>
                    <Input placeholder="Enter Supplier Name" required />
                    <!-- <InputError :message="form.errors.member_lastname" /> -->
                </div>

            </div>


            <div class="grid grid-cols-2 gap-6">

                <div class="grid w-full gap-2">
                    <Label for="member_middlename"> Address:</Label>
                    <Input placeholder="Enter middle name" />
                    <!-- <InputError :message="form.errors.member_middlename" /> -->
                </div>

                <div class="grid w-full gap-2">
                    <Label for="member_firstname"> Contact Person: <span class="text-red-500">*</span></Label>
                    <Input placeholder="Enter first name" required />
                    <!-- <InputError :message="form.errors.member_firstname" /> -->
                </div>

            </div>

            <div class="grid grid-cols-2 gap-6">

                <div class="grid w-full gap-2">
                    <Label for="member_firstname"> Email: <span class="text-red-500">*</span></Label>
                    <Input placeholder="Enter email" required />
                    <!-- <InputError :message="form.errors.member_firstname" /> -->
                </div>

                <div class="grid w-full gap-2">
                    <Label for="member_firstname"> Phone Number: <span class="text-red-500">*</span></Label>
                    <Input placeholder="Enter email" required />
                    <!-- <InputError :message="form.errors.member_firstname" /> -->
                </div>

            </div>




            <div class="flex space-x-2">

                <!-- <BaseButton v-if="!isVerified || (isVerified && props.transactionType === 'update')" :text="'Verify'"
                    :disabled="isVerified && props.transactionType === 'update'" variant="secondary" color="secondary"
                    type="button" @click="getMemberPin" :loading="isVerifying">
                </BaseButton>


                <BaseButton v-if="isVerified && props.transactionType === 'create'" text="Clear" variant="secondary"
                    color="secondary" type="button" @click="clearForm">
                </BaseButton> -->


                <div class="flex space-x-2 ml-auto">

                    <BaseButton text="Cancel" variant="outline" color="secondary" type="button" />

                    <BaseButton :text="Save" variant="default" color="primary" type="button" />

                </div>
            </div>
        </form>
    </FormCard>


</template>