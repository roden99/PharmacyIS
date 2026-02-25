<script setup>
import MemberForm from '@/pages/Suppliers/SupplierForm.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'


// const emit = defineEmits(['member-form-closed']);

// const isProcessing = ref(false);
// const handleSubmit = (formData) => {

//     isProcessing.value = true;
//     router.post('/members', formData, {
//         preserveScroll: "errors",
//         preserveState: "errors",
//         onSuccess: () => {
//             toast.success('Success', { description: 'Member created successfully!' });
//             isProcessing.value = false;
//             emit('member-form-closed'); // Close modal on success
//         },
//         onError: (errors) => {
//             const firstErrorKey = Object.keys(errors)[0];
//             toast.warning('Failed to create member.', { description: errors[firstErrorKey] });
//             isProcessing.value = false;
//         },
//         onFinish: () => {
//             isProcessing.value = false;
//         }
//     });
// };

</script>
<template>


    <div>
        <MemberForm :card-title="'New Suplier'" />


    </div>
    <!-- 
    <div>
        <MemberForm @saveMember="handleSubmit" @member-form-closed="emit('member-form-closed')"
            :is-processing="isProcessing" :card-title="'New Member'" :transaction-type="'create'" />
        :card-title='"New Member"' />

    </div> -->

</template>
