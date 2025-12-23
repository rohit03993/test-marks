<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    counts: Object,
});

const form = useForm({
    confirm_text: '',
    password: '',
});

const showConfirmModal = ref(false);
const confirmText = ref('');

const openConfirmModal = () => {
    if (form.confirm_text !== 'RESET ALL DATA') {
        form.setError('confirm_text', 'Please type "RESET ALL DATA" exactly to confirm.');
        return;
    }
    showConfirmModal.value = true;
};

const closeModal = () => {
    showConfirmModal.value = false;
    form.reset();
    confirmText.value = '';
};

const submitReset = () => {
    form.post(route('reset.store'), {
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <AppLayout title="Reset All Data">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reset All Data
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Warning Card -->
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>DANGER ZONE:</strong> This action will permanently delete ALL data including students, classes, exams, and results. This action cannot be undone!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Data Summary -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Current Data Summary</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">Students</div>
                                <div class="text-2xl font-bold text-gray-900">{{ counts.students || 0 }}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">Classes</div>
                                <div class="text-2xl font-bold text-gray-900">{{ counts.classes || 0 }}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">Exams</div>
                                <div class="text-2xl font-bold text-gray-900">{{ counts.exams || 0 }}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">Class Assignments</div>
                                <div class="text-2xl font-bold text-gray-900">{{ counts.class_students || 0 }}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">Exam Results</div>
                                <div class="text-2xl font-bold text-gray-900">{{ counts.exam_results || 0 }}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">Subject Marks</div>
                                <div class="text-2xl font-bold text-gray-900">{{ counts.exam_subject_marks || 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reset Form -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="openConfirmModal">
                        <div class="p-6 space-y-6">
                            <div>
                                <InputLabel for="confirm_text" value="Type 'RESET ALL DATA' to confirm *" />
                                <TextInput
                                    id="confirm_text"
                                    v-model="form.confirm_text"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="RESET ALL DATA"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.confirm_text" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    You must type exactly "RESET ALL DATA" (case-sensitive) to proceed.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <DangerButton
                                type="submit"
                                :disabled="form.confirm_text !== 'RESET ALL DATA'"
                            >
                                Proceed to Final Confirmation
                            </DangerButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Final Confirmation Modal -->
        <Modal :show="showConfirmModal" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="ml-3 text-lg font-medium text-gray-900">
                        Final Confirmation Required
                    </h2>
                </div>

                <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-4">
                    <p class="text-sm text-red-800 font-medium mb-2">
                        You are about to permanently delete:
                    </p>
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                        <li>{{ counts.students || 0 }} Students</li>
                        <li>{{ counts.classes || 0 }} Classes</li>
                        <li>{{ counts.exams || 0 }} Exams</li>
                        <li>{{ counts.class_students || 0 }} Class Assignments</li>
                        <li>{{ counts.exam_results || 0 }} Exam Results</li>
                        <li>{{ counts.exam_subject_marks || 0 }} Subject Marks</li>
                    </ul>
                    <p class="text-sm text-red-800 font-medium mt-3">
                        This action CANNOT be undone!
                    </p>
                </div>

                <div class="mt-6">
                    <InputLabel for="password" value="Enter your password to confirm *" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="Your current password"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        @click="submitReset"
                        :disabled="form.processing || !form.password"
                    >
                        <span v-if="form.processing">Resetting...</span>
                        <span v-else>Yes, Reset Everything</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

