<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    students: Object,
    classes: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const classFilter = ref(props.filters.class_id || '');
const perPage = ref(props.filters.per_page || '20');
const selectedStudents = ref([]);
const selectAll = ref(false);

watch([search, classFilter, perPage], ([searchValue, classValue, perPageValue]) => {
    router.get(
        route('students.index'),
        { 
            search: searchValue,
            class_id: classValue || null,
            per_page: perPageValue || null,
        },
        {
            preserveState: true,
            replace: true,
            only: ['students', 'filters'],
        }
    );
});

const hasSelectedStudents = computed(() => selectedStudents.value.length > 0);

const toggleSelectAll = () => {
    if (selectAll.value) {
        // Select all students on current page
        selectedStudents.value = props.students.data.map(s => s.id);
    } else {
        // Deselect all
        selectedStudents.value = [];
    }
};

const toggleStudent = (studentId) => {
    const index = selectedStudents.value.indexOf(studentId);
    if (index > -1) {
        selectedStudents.value.splice(index, 1);
    } else {
        selectedStudents.value.push(studentId);
    }
    selectAll.value = selectedStudents.value.length === props.students.data.length;
};

const goToBulkAssign = () => {
    if (selectedStudents.value.length === 0) {
        alert('Please select at least one student.');
        return;
    }
    router.visit(route('students.bulk-assign', { student_ids: selectedStudents.value }));
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
    <AppLayout title="Students">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Students
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton
                        v-if="hasSelectedStudents"
                        @click="goToBulkAssign"
                        class="inline-flex items-center px-4 py-2"
                    >
                        Assign to Class ({{ selectedStudents.length }})
                    </SecondaryButton>
                    <Link
                        :href="route('students.upload')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Upload Students
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Search and Filter Bar -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel for="search" value="Search Students" />
                                <TextInput
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Search by name or roll number..."
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
                                    <option value="unassigned">Unassigned</option>
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
                                <InputLabel for="per_page" value="Show Per Page" />
                                <select
                                    id="per_page"
                                    v-model="perPage"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <Checkbox v-model:checked="selectAll" @change="toggleSelectAll" />
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Roll Number
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Father Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current Class
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tests Given
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created At
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="students.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        No students found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="student in students.data"
                                    :key="student.id"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    :class="{ 'bg-blue-50': selectedStudents.includes(student.id) }"
                                    @click="router.visit(route('students.profile', student.id))"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                        <Checkbox
                                            :checked="selectedStudents.includes(student.id)"
                                            @change="toggleStudent(student.id)"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ student.active_class_student?.roll_number || student.roll_number || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ student.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ student.father_name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span
                                            v-if="student.active_class_student?.academic_class"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                                        >
                                            {{ student.active_class_student.academic_class.name }}
                                        </span>
                                        <span v-else class="text-gray-400 italic">
                                            Not assigned
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                student.test_count === 0
                                                    ? 'bg-red-100 text-red-800'
                                                    : student.test_count <= 2
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : 'bg-green-100 text-green-800'
                                            ]"
                                        >
                                            {{ student.test_count || 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(student.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="students.links && students.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                <span v-if="perPage === 'all'">
                                    Showing all {{ students.total }} results
                                </span>
                                <span v-else>
                                    Showing {{ students.from }} to {{ students.to }} of {{ students.total }} results
                                </span>
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in students.links"
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
    </AppLayout>
</template>

