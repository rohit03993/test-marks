<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Welcome" />
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-blue-50">
        <!-- Navigation Bar -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link :href="route('dashboard')" class="flex items-center space-x-3">
                            <ApplicationMark class="block h-10 w-auto" />
                            <span class="text-xl font-bold text-gray-900">Test Marks</span>
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="flex items-center space-x-4">
                        <Link
                            v-if="canLogin && $page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition"
                            >
                                Login
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Register
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="text-center">
                    <!-- Main Heading -->
                    <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 mb-6">
                        Track Your Daily Reports
                        <span class="block text-indigo-600 mt-2">in a Personalized Way</span>
                    </h1>

                    <!-- Subheading -->
                    <p class="mt-6 text-xl text-gray-600 max-w-3xl mx-auto">
                        Manage student data, exam results, and academic performance all in one place.
                        Streamline your coaching institute's workflow with our comprehensive CRM system.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="mt-10 flex justify-center space-x-4">
                        <Link
                            v-if="canLogin && !$page.props.auth.user"
                            :href="route('login')"
                            class="bg-indigo-600 text-white hover:bg-indigo-700 px-8 py-3 rounded-lg text-lg font-semibold shadow-lg hover:shadow-xl transition"
                        >
                            Get Started
                        </Link>
                        <Link
                            v-else-if="canLogin && $page.props.auth.user"
                            :href="route('dashboard')"
                            class="bg-indigo-600 text-white hover:bg-indigo-700 px-8 py-3 rounded-lg text-lg font-semibold shadow-lg hover:shadow-xl transition"
                        >
                            Go to Dashboard
                        </Link>
                        <Link
                            v-if="canRegister && !$page.props.auth.user"
                            :href="route('register')"
                            class="bg-white text-indigo-600 border-2 border-indigo-600 hover:bg-indigo-50 px-8 py-3 rounded-lg text-lg font-semibold shadow-lg hover:shadow-xl transition"
                        >
                            Sign Up
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="bg-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Powerful Features</h2>
                    <p class="text-lg text-gray-600">Everything you need to manage your students and exams</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-8 rounded-xl shadow-lg hover:shadow-xl transition">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Student Management</h3>
                        <p class="text-gray-600">
                            Upload and manage student master data with ease. Track student information and academic history.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-8 rounded-xl shadow-lg hover:shadow-xl transition">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Exam Results</h3>
                        <p class="text-gray-600">
                            Upload exam results via Excel/CSV. Automatic roll number mapping and result calculation.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-8 rounded-xl shadow-lg hover:shadow-xl transition">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Quick Search</h3>
                        <p class="text-gray-600">
                            Search students by name or roll number. View complete academic profiles and exam history.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-gray-400">
                    © {{ new Date().getFullYear() }} Test Marks CRM. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>
