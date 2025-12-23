<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    classes: Array,
    searchResults: Array,
    searchType: String,
    searchTerm: String,
});

const form = useForm({
    search_type: 'name',
    name: '',
    roll_number: '',
    class_id: '',
});

const searchByRollNumber = computed(() => form.search_type === 'roll_number');

const submit = () => {
    form.post(route('search.perform'));
};
</script>

<template>
    <AppLayout title="Search Students">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Search Students
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Search Type Selection -->
                        <div>
                            <InputLabel value="Search By" />
                            <div class="mt-2 flex space-x-4">
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.search_type"
                                        value="name"
                                        class="mr-2"
                                    />
                                    <span>Student Name</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.search_type"
                                        value="roll_number"
                                        class="mr-2"
                                    />
                                    <span>Roll Number + Class</span>
                                </label>
                            </div>
                        </div>

                        <!-- Name Search -->
                        <div v-if="form.search_type === 'name'">
                            <InputLabel for="name" value="Student Name *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Enter student name..."
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Roll Number + Class Search -->
                        <div v-if="form.search_type === 'roll_number'" class="space-y-4">
                            <div>
                                <InputLabel for="roll_number" value="Roll Number *" />
                                <TextInput
                                    id="roll_number"
                                    v-model="form.roll_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Enter roll number..."
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.roll_number" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="class_id" value="Class *" />
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
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">
                                <span v-if="form.processing">Searching...</span>
                                <span v-else>Search</span>
                            </PrimaryButton>
                        </div>
                    </form>

                    <!-- Search Results -->
                    <div v-if="searchResults && searchResults.length > 0" class="border-t border-gray-200 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Search Results ({{ searchResults.length }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="student in searchResults"
                                :key="student.id"
                                class="p-4 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer"
                                @click="router.visit(route('students.profile', student.id))"
                            >
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ student.name }}</p>
                                        <p v-if="student.father_name" class="text-sm text-gray-500">
                                            {{ student.father_name }}
                                        </p>
                                        <p v-if="student.active_class_student?.academic_class" class="text-sm text-indigo-600 mt-1">
                                            {{ student.active_class_student.academic_class.name }}
                                            <span v-if="student.active_class_student.roll_number">
                                                (Roll: {{ student.active_class_student.roll_number }})
                                            </span>
                                        </p>
                                    </div>
                                    <span class="text-indigo-600 hover:text-indigo-900">
                                        View Profile →
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

