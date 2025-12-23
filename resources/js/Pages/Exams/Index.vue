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
    exams: Object,
    classes: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const classFilter = ref(props.filters.class_id || '');
const showDeleteModal = ref(false);
const examToDelete = ref(null);

watch([search, classFilter], ([searchValue, classValue]) => {
    router.get(
        route('exams.index'),
        { 
            search: searchValue,
            class_id: classValue || null,
        },
        {
            preserveState: true,
            replace: true,
            only: ['exams', 'filters'],
        }
    );
});

const confirmDelete = (exam) => {
    examToDelete.value = exam;
    showDeleteModal.value = true;
};

const deleteExam = () => {
    if (examToDelete.value) {
        router.delete(route('exams.destroy', examToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                examToDelete.value = null;
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
    <AppLayout title="Exams">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Exams
                </h2>
                <Link
                    :href="route('exams.create')"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Create Exam
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Search and Filter Bar -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="search" value="Search Exams" />
                                <TextInput
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Search by exam name..."
                                    autofocus
                                />
                            </div>
                            <div>
                                <InputLabel for="class_filter" value="Filter by Class" />
                                <select
                                    id="class_filter"
                                    v-model="classFilter"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Classes</option>
                                    <option
                                        v-for="classItem in classes"
                                        :key="classItem.id"
                                        :value="classItem.id"
                                    >
                                        {{ classItem.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Exams Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Exam Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Exam Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Results
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="exams.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No exams found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="exam in exams.data"
                                    :key="exam.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ exam.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="classItem in exam.academic_classes || []"
                                                :key="classItem.id"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                                            >
                                                {{ classItem.name }}
                                            </span>
                                            <span v-if="!exam.academic_classes || exam.academic_classes.length === 0" class="text-gray-400">
                                                -
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(exam.exam_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ exam.exam_results_count || 0 }} students
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('exams.edit', exam.id)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4"
                                        >
                                            Edit
                                        </Link>
                                        <Link
                                            :href="route('exams.upload-results', exam.id)"
                                            class="text-green-600 hover:text-green-900 mr-4"
                                        >
                                            Upload Results
                                        </Link>
                                        <button
                                            @click="confirmDelete(exam)"
                                            class="text-red-600 hover:text-red-900"
                                            :disabled="exam.exam_results_count > 0"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="exams.links && exams.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ exams.from }} to {{ exams.to }} of {{ exams.total }} results
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in exams.links"
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
                    Delete Exam
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete "{{ examToDelete?.name }}"? This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDeleteModal = false">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        @click="deleteExam"
                    >
                        Delete Exam
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

