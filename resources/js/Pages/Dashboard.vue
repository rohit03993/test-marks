<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Pie, Bar, Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    stats: Object,
    studentsByClass: Array,
    examParticipation: Object,
    topPerformers: Array,
    recentExams: Array,
    examResultsOverTime: Array,
    subjectAverages: Array,
});

// Students by Class Pie Chart
const studentsByClassData = computed(() => ({
    labels: props.studentsByClass.map(item => item.name),
    datasets: [{
        data: props.studentsByClass.map(item => item.count),
        backgroundColor: [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'
        ],
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const studentsByClassOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
        title: {
            display: true,
            text: 'Students by Class',
            font: { size: 16, weight: 'bold' },
        },
    },
};

// Exam Participation Pie Chart
const examParticipationData = computed(() => ({
    labels: ['With Exams', 'Without Exams'],
    datasets: [{
        data: [props.examParticipation.with_exams, props.examParticipation.without_exams],
        backgroundColor: ['#10B981', '#EF4444'],
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const examParticipationOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
        title: {
            display: true,
            text: 'Exam Participation',
            font: { size: 16, weight: 'bold' },
        },
    },
};

// Top Performers Bar Chart
const topPerformersData = computed(() => ({
    labels: props.topPerformers.map(item => `${item.student_name} (${item.class_name})`),
    datasets: [{
        label: 'Average Score',
        data: props.topPerformers.map(item => item.average),
        backgroundColor: '#3B82F6',
        borderColor: '#2563EB',
        borderWidth: 1,
    }],
}));

const topPerformersOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: true,
            text: 'Top Performers by Class',
            font: { size: 16, weight: 'bold' },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            max: 100,
        },
    },
};

// Exam Results Over Time Line Chart
const examResultsOverTimeData = computed(() => ({
    labels: props.examResultsOverTime.map(item => item.date),
    datasets: [{
        label: 'Students',
        data: props.examResultsOverTime.map(item => item.count),
        borderColor: '#8B5CF6',
        backgroundColor: 'rgba(139, 92, 246, 0.1)',
        tension: 0.4,
        fill: true,
    }],
}));

const examResultsOverTimeOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: true,
            text: 'Exam Results Over Time',
            font: { size: 16, weight: 'bold' },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
};

// Subject Averages Bar Chart
const subjectAveragesData = computed(() => ({
    labels: props.subjectAverages.map(item => item.subject),
    datasets: [{
        label: 'Average Marks',
        data: props.subjectAverages.map(item => item.average),
        backgroundColor: '#10B981',
        borderColor: '#059669',
        borderWidth: 1,
    }],
}));

const subjectAveragesOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: true,
            text: 'Subject-wise Average Marks',
            font: { size: 16, weight: 'bold' },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            max: 100,
        },
    },
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-2xl text-gray-900">
                    Dashboard
                </h2>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Total Students -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.total_students }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Students with Exams -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Students with Exams</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.students_with_exams }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Exams -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Exams</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.total_exams }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Exam Results -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Results</dt>
                                        <dd class="text-2xl font-semibold text-gray-900">{{ stats.total_exam_results }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Students by Class Pie Chart -->
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6" v-if="studentsByClass.length > 0">
                        <div class="h-64">
                            <Pie :data="studentsByClassData" :options="studentsByClassOptions" />
                        </div>
                    </div>

                    <!-- Exam Participation Pie Chart -->
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                        <div class="h-64">
                            <Pie :data="examParticipationData" :options="examParticipationOptions" />
                        </div>
                    </div>

                    <!-- Exam Results Over Time Line Chart -->
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6" v-if="examResultsOverTime.length > 0">
                        <div class="h-64">
                            <Line :data="examResultsOverTimeData" :options="examResultsOverTimeOptions" />
                        </div>
                    </div>

                    <!-- Subject Averages Bar Chart -->
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6" v-if="subjectAverages.length > 0">
                        <div class="h-64">
                            <Bar :data="subjectAveragesData" :options="subjectAveragesOptions" />
                        </div>
                    </div>
                </div>

                <!-- Top Performers and Recent Exams -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Performers -->
                    <div class="bg-white overflow-hidden shadow rounded-lg" v-if="topPerformers.length > 0">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performers by Class</h3>
                            <div class="h-64">
                                <Bar :data="topPerformersData" :options="topPerformersOptions" />
                            </div>
                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="(performer, index) in topPerformers"
                                    :key="index"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                >
                                    <div>
                                        <p class="font-medium text-gray-900">{{ performer.student_name }}</p>
                                        <p class="text-sm text-gray-500">{{ performer.class_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-indigo-600">{{ performer.average }}%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Exams -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Exams</h3>
                            <div v-if="recentExams.length > 0" class="space-y-3">
                                <div
                                    v-for="exam in recentExams"
                                    :key="exam.id"
                                    class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
                                >
                                    <div>
                                        <p class="font-medium text-gray-900">{{ exam.name }}</p>
                                        <p class="text-sm text-gray-500">{{ exam.date }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-indigo-600">{{ exam.students_count }}</p>
                                        <p class="text-xs text-gray-500">students</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <p>No exams yet</p>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </AppLayout>
</template>
