<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    classes: Array,
});

const selectedClassId = ref('');

const viewClassResults = () => {
    if (selectedClassId.value) {
        router.visit(route('results.class', selectedClassId.value));
    }
};
</script>

<template>
    <AppLayout title="Results">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Results
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Select Class to View Results
                        </h3>
                        <p class="text-sm text-gray-600 mb-6">
                            Choose a class to view all students and their test participation counts.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <InputLabel for="class_id" value="Select Class *" />
                                <select
                                    id="class_id"
                                    v-model="selectedClassId"
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
                            </div>

                            <div>
                                <PrimaryButton
                                    @click="viewClassResults"
                                    :disabled="!selectedClassId"
                                >
                                    View Class Results
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

