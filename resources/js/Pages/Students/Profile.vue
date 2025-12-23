<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    student: Object,
    examHistory: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatMarks = (marks) => {
    if (marks === null || marks === undefined) {
        return 'Absent';
    }
    return marks;
};
</script>

<template>
    <AppLayout title="Student Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Student Profile
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
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Student Information Card -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Student Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Name</p>
                                <p class="text-base font-medium text-gray-900">{{ student.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Father Name</p>
                                <p class="text-base font-medium text-gray-900">{{ student.father_name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Current Class</p>
                                <p class="text-base font-medium text-gray-900">
                                    <span
                                        v-if="student.active_class_student?.academic_class"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                                    >
                                        {{ student.active_class_student.academic_class.name }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">Not assigned</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Current Roll Number</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ student.active_class_student?.roll_number || student.roll_number || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam History Card -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Exam History</h3>
                        
                        <div v-if="examHistory.length === 0" class="text-center py-8 text-gray-500">
                            No exam results found for this student.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Exam Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Class
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Roll No.
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Physics
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Chemistry
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Mathematics
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Average
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="(exam, index) in examHistory"
                                        :key="index"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ exam.exam_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(exam.exam_date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ exam.class_name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ exam.roll_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                            {{ formatMarks(exam.physics) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                            {{ formatMarks(exam.chemistry) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                            {{ formatMarks(exam.mathematics) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-gray-900">
                                            {{ exam.total }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-gray-900">
                                            {{ typeof exam.average === 'number' ? exam.average.toFixed(2) : exam.average }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    exam.status === 'present'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800'
                                                ]"
                                            >
                                                {{ exam.status === 'present' ? 'Present' : 'Absent' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

