<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    students: Array,
    classes: Array,
    selectedStudentIds: Array,
});

const form = useForm({
    student_ids: props.selectedStudentIds,
    class_id: '',
    roll_numbers: {},
});

// Initialize roll numbers with student's existing roll number or empty
const initializeRollNumbers = () => {
    props.students.forEach(student => {
        form.roll_numbers[student.id] = student.roll_number || '';
    });
};

initializeRollNumbers();

const selectedClass = computed(() => {
    return props.classes.find(c => c.id == form.class_id);
});

const submit = () => {
    if (!form.class_id) {
        alert('Please select a class.');
        return;
    }

    // Convert roll_numbers object to array in student_ids order
    const rollNumbersArray = form.student_ids.map(id => form.roll_numbers[id] || null);
    
    form.transform((data) => ({
        ...data,
        roll_numbers: rollNumbersArray,
    })).post(route('students.bulk-assign.store'));
};
</script>

<template>
    <AppLayout title="Bulk Assign Students to Class">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Assign Students to Class
                </h2>
                <Link
                    :href="route('students.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Back to Students
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-6">
                            <!-- Selected Students Info -->
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <p class="text-sm font-medium text-blue-900">
                                    {{ students.length }} student(s) selected for assignment
                                </p>
                            </div>

                            <!-- Class Selection -->
                            <div>
                                <InputLabel for="class_id" value="Select Class *" />
                                <select
                                    id="class_id"
                                    v-model="form.class_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">-- Select Class --</option>
                                    <option
                                        v-for="classItem in classes"
                                        :key="classItem.id"
                                        :value="classItem.id"
                                    >
                                        {{ classItem.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.class_id" class="mt-2" />
                            </div>

                            <!-- Roll Numbers Assignment -->
                            <div v-if="form.class_id">
                                <div class="border-t border-gray-200 pt-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                                        Assign Roll Numbers
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-4">
                                        Enter roll numbers for each student in the selected class.
                                    </p>

                                    <div class="space-y-4">
                                        <div
                                            v-for="student in students"
                                            :key="student.id"
                                            class="flex items-center space-x-4 p-4 border border-gray-200 rounded-md"
                                        >
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ student.name }}
                                                </p>
                                                <p v-if="student.father_name" class="text-xs text-gray-500">
                                                    {{ student.father_name }}
                                                </p>
                                            </div>
                                            <div class="w-32">
                                                <TextInput
                                                    :id="`roll_${student.id}`"
                                                    v-model="form.roll_numbers[student.id]"
                                                    type="text"
                                                    class="block w-full"
                                                    placeholder="Roll No."
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <Link
                                :href="route('students.index')"
                                class="mr-3"
                            >
                                <SecondaryButton type="button">
                                    Cancel
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing || !form.class_id">
                                <span v-if="form.processing">Assigning...</span>
                                <span v-else>Assign to Class</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

