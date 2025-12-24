<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    classItem: Object,
    students: Array,
    pagination: Object,
    filters: Object,
});

const perPage = ref(props.filters.per_page || 50);

const changePerPage = () => {
    router.get(route('results.class', props.classItem.id), {
        per_page: perPage.value,
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    });
};

const goToPage = (page) => {
    router.get(route('results.class', props.classItem.id), {
        per_page: perPage.value,
        page: page,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout title="Class Results">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Results: {{ classItem.name }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Total Students: {{ pagination.total }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        :href="route('results.class.export', classItem.id)"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export to Excel
                    </a>
                    <Link
                        :href="route('results.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        ← Back to Results
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white shadow-sm rounded-lg mb-4 p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <InputLabel for="per_page" value="Show:" class="mb-0" />
                            <select
                                id="per_page"
                                v-model="perPage"
                                @change="changePerPage"
                                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option :value="20">20</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                                <option :value="500">All</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Roll Number
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Father Name
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tests Taken
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="students.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No students found in this class.
                                    </td>
                                </tr>
                                <tr
                                    v-for="student in students"
                                    :key="student.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                        {{ student.roll_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ student.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ student.father_name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold',
                                                student.test_count === 0
                                                    ? 'bg-red-100 text-red-800'
                                                    : student.test_count >= 3
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            ]"
                                        >
                                            {{ student.test_count }} {{ student.test_count === 1 ? 'test' : 'tests' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <Link
                                            :href="route('students.profile', student.id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View Profile
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} to 
                                {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
                                of {{ pagination.total }} results
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    v-if="pagination.current_page > 1"
                                    @click="goToPage(pagination.current_page - 1)"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </button>
                                <button
                                    v-for="page in Math.min(5, pagination.last_page)"
                                    :key="page"
                                    @click="goToPage(page)"
                                    :class="[
                                        'px-3 py-2 text-sm border rounded-md',
                                        page === pagination.current_page
                                            ? 'bg-indigo-500 text-white border-indigo-500'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    {{ page }}
                                </button>
                                <button
                                    v-if="pagination.current_page < pagination.last_page"
                                    @click="goToPage(pagination.current_page + 1)"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

