<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    exam: Object,
    students: Array,
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
    return typeof marks === 'number' ? marks.toFixed(2) : marks;
};

const getMarkColor = (marks) => {
    if (marks === null || marks === undefined) {
        return 'text-red-600';
    }
    if (marks >= 80) {
        return 'text-green-600';
    } else if (marks >= 60) {
        return 'text-blue-600';
    } else if (marks >= 40) {
        return 'text-yellow-600';
    } else {
        return 'text-orange-600';
    }
};
</script>

<template>
    <AppLayout title="View Exam Results">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Exam Results: {{ exam.name }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Date: {{ formatDate(exam.exam_date) }} | Classes: {{ exam.classes }} | 
                        Total Students: {{ students.length }}
                    </p>
                </div>
                <Link
                    :href="route('exams.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Back to Exams
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rank
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Roll Number
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th
                                        v-for="subject in exam.subjects"
                                        :key="subject"
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        {{ subject }}
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
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(student, index) in students"
                                    :key="student.student_id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        #{{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                        {{ student.roll_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ student.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ student.class_name }}
                                        </span>
                                    </td>
                                    <td
                                        v-for="subject in exam.subjects"
                                        :key="subject"
                                        class="px-6 py-4 whitespace-nowrap text-sm text-center"
                                    >
                                        <span :class="['font-semibold', getMarkColor(student.subject_marks[subject])]">
                                            {{ formatMarks(student.subject_marks[subject]) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900">
                                        {{ typeof student.total === 'number' ? student.total.toFixed(2) : student.total }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-indigo-600">
                                        {{ typeof student.average === 'number' ? student.average.toFixed(2) : student.average }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                student.status === 'present'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ student.status === 'present' ? 'Present' : 'Absent' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <Link
                                            :href="route('students.profile', student.student_id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View Profile
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

