<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    classes: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showDeleteModal = ref(false);
const classToDelete = ref(null);

watch(search, (value) => {
    router.get(
        route('classes.index'),
        { search: value },
        {
            preserveState: true,
            replace: true,
            only: ['classes', 'filters'],
        }
    );
});

const confirmDelete = (classItem) => {
    classToDelete.value = classItem;
    showDeleteModal.value = true;
};

const deleteClass = () => {
    if (classToDelete.value) {
        router.delete(route('classes.destroy', classToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                classToDelete.value = null;
            },
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout title="Classes">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Classes
                </h2>
                <Link
                    :href="route('classes.create')"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Create Class
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Search Bar -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <InputLabel for="search" value="Search Classes" />
                                <TextInput
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Search by class name..."
                                    autofocus
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Classes Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Active Students
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Exams
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created At
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="classes.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No classes found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="classItem in classes.data"
                                    :key="classItem.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ classItem.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ classItem.active_students_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ classItem.exams_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(classItem.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('classes.edit', classItem.id)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="confirmDelete(classItem)"
                                            class="text-red-600 hover:text-red-900"
                                            :disabled="classItem.active_students_count > 0 || classItem.exams_count > 0"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="classes.links && classes.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ classes.from }} to {{ classes.to }} of {{ classes.total }} results
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in classes.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-2 text-sm leading-4 border rounded-md',
                                        link.active
                                            ? 'bg-indigo-500 text-white border-indigo-500'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete Class
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete "{{ classToDelete?.name }}"? This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDeleteModal = false">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        @click="deleteClass"
                    >
                        Delete Class
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

