<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref } from 'vue';

const props = defineProps({
    examItem: Object,
    classes: Array,
    subjects: Array,
});

const form = useForm({
    name: props.examItem?.name || '',
    exam_date: props.examItem?.exam_date || '',
    class_ids: props.examItem?.class_ids || [],
    subject_ids: props.examItem?.subject_ids || [],
});

const newSubjectName = ref('');
const showNewSubjectInput = ref(false);
const creatingSubject = ref(false);
const subjects = ref([...props.subjects]);

const addNewSubject = async () => {
    if (!newSubjectName.value.trim()) return;
    
    const subjectName = newSubjectName.value.trim();
    
    // Check if subject already exists
    const existingSubject = subjects.value.find(
        s => s.name.toLowerCase() === subjectName.toLowerCase()
    );
    
    if (existingSubject) {
        // If exists, just add to selection
        if (!form.subject_ids.includes(existingSubject.id)) {
            form.subject_ids.push(existingSubject.id);
        }
        newSubjectName.value = '';
        showNewSubjectInput.value = false;
        return;
    }
    
    // Create new subject via API
    creatingSubject.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        const response = await fetch(route('exams.create-subject'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name: subjectName }),
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to create subject');
        }
        
        // Add new subject to the list
        subjects.value.push(data.subject);
        subjects.value.sort((a, b) => a.name.localeCompare(b.name));
        
        // Add to selection
        if (!form.subject_ids.includes(data.subject.id)) {
            form.subject_ids.push(data.subject.id);
        }
        
        newSubjectName.value = '';
        showNewSubjectInput.value = false;
    } catch (error) {
        console.error('Error creating subject:', error);
        alert('Error creating subject: ' + (error.message || 'Please try again.'));
    } finally {
        creatingSubject.value = false;
    }
};

const submit = () => {
    if (props.examItem) {
        form.put(route('exams.update', props.examItem.id));
    } else {
        form.post(route('exams.store'));
    }
};
</script>

<template>
    <AppLayout :title="examItem ? 'Edit Exam' : 'Create Exam'">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ examItem ? 'Edit Exam' : 'Create Exam' }}
                </h2>
                <Link
                    :href="route('exams.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Back to Exams
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-6">
                            <!-- Exam Name -->
                            <div>
                                <InputLabel for="name" value="Exam Name *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., Unit Test 1, Mid Term Exam"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <!-- Exam Date -->
                            <div>
                                <InputLabel for="exam_date" value="Exam Date *" />
                                <TextInput
                                    id="exam_date"
                                    v-model="form.exam_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.exam_date" class="mt-2" />
                            </div>

                            <!-- Class Selection (Multiple) -->
                            <div>
                                <InputLabel value="Classes *" />
                                <div class="mt-2 space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                                    <label
                                        v-for="classItem in classes"
                                        :key="classItem.id"
                                        class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="classItem.id"
                                            v-model="form.class_ids"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm text-gray-700">{{ classItem.name }}</span>
                                    </label>
                                </div>
                                <InputError :message="form.errors.class_ids" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    Select one or more classes for which this exam is conducted (e.g., both Class 11 JEE and Class 12 JEE)
                                </p>
                            </div>

                            <!-- Subject Selection (Multiple) -->
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel value="Subjects *" />
                                    <button
                                        type="button"
                                        @click="showNewSubjectInput = !showNewSubjectInput"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                    >
                                        + Add New Subject
                                    </button>
                                </div>
                                
                                <!-- New Subject Input -->
                                <div v-if="showNewSubjectInput" class="mt-2 mb-3 flex gap-2">
                                    <TextInput
                                        v-model="newSubjectName"
                                        type="text"
                                        class="flex-1"
                                        placeholder="Enter subject name (e.g., Biology, English)"
                                        @keyup.enter="addNewSubject"
                                        :disabled="creatingSubject"
                                    />
                                    <PrimaryButton type="button" @click="addNewSubject" class="px-4" :disabled="creatingSubject">
                                        <span v-if="creatingSubject">Adding...</span>
                                        <span v-else>Add</span>
                                    </PrimaryButton>
                                    <SecondaryButton type="button" @click="showNewSubjectInput = false; newSubjectName = ''" class="px-4" :disabled="creatingSubject">
                                        Cancel
                                    </SecondaryButton>
                                </div>

                                <div class="mt-2 space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                                    <label
                                        v-for="subject in subjects"
                                        :key="subject.id"
                                        class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="subject.id"
                                            v-model="form.subject_ids"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm text-gray-700">{{ subject.name }}</span>
                                    </label>
                                    <p v-if="subjects.length === 0" class="text-sm text-gray-500 italic p-2">
                                        No subjects available. Please add subjects first.
                                    </p>
                                </div>
                                <InputError :message="form.errors.subject_ids" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    Select the subjects that will be included in this exam. Subjects will be automatically detected from your uploaded result file.
                                </p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <Link
                                :href="route('exams.index')"
                                class="mr-3"
                            >
                                <SecondaryButton type="button">
                                    Cancel
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>{{ examItem ? 'Update Exam' : 'Create Exam' }}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

