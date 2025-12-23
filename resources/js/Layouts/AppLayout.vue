<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    title: String,
});

const showingMobileMenu = ref(false);
const showingUserMenu = ref(false);
const showingAdminMenu = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};

const isActive = (routeName) => {
    return route().current(routeName);
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-50 flex">
            <!-- Sidebar -->
            <aside 
                :class="[
                    'fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-indigo-700 to-indigo-900 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex flex-col h-screen',
                    showingMobileMenu ? 'translate-x-0' : '-translate-x-full'
                ]"
            >
                <!-- Sidebar Header -->
                <div class="flex items-center justify-between h-16 px-6 border-b border-indigo-800 flex-shrink-0">
                    <Link :href="route('dashboard')" class="flex items-center space-x-3">
                        <ApplicationMark class="block h-8 w-auto" />
                        <span class="text-white font-bold text-lg">Test Marks</span>
                    </Link>
                    <button 
                        @click="showingMobileMenu = false"
                        class="lg:hidden text-indigo-200 hover:text-white"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="mt-6 px-3 space-y-1 flex-1 overflow-y-auto pb-4">
                    <Link
                        :href="route('dashboard')"
                        :class="[
                            'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                            isActive('dashboard') 
                                ? 'bg-indigo-800 text-white shadow-lg' 
                                : 'text-indigo-100 hover:bg-indigo-800 hover:text-white'
                        ]"
                    >
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </Link>

                    <Link
                        :href="route('students.index')"
                        :class="[
                            'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                            isActive('students.*') 
                                ? 'bg-indigo-800 text-white shadow-lg' 
                                : 'text-indigo-100 hover:bg-indigo-800 hover:text-white'
                        ]"
                    >
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Students
                    </Link>

                    <Link
                        :href="route('classes.index')"
                        :class="[
                            'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                            isActive('classes.*') 
                                ? 'bg-indigo-800 text-white shadow-lg' 
                                : 'text-indigo-100 hover:bg-indigo-800 hover:text-white'
                        ]"
                    >
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Classes
                    </Link>

                    <Link
                        :href="route('exams.index')"
                        :class="[
                            'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                            isActive('exams.*') 
                                ? 'bg-indigo-800 text-white shadow-lg' 
                                : 'text-indigo-100 hover:bg-indigo-800 hover:text-white'
                        ]"
                    >
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exams
                    </Link>

                    <Link
                        :href="route('search.index')"
                        :class="[
                            'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                            isActive('search.*') 
                                ? 'bg-indigo-800 text-white shadow-lg' 
                                : 'text-indigo-100 hover:bg-indigo-800 hover:text-white'
                        ]"
                    >
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Search
                    </Link>

                    <Link
                        :href="route('quick-upload.index')"
                        :class="[
                            'flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                            isActive('quick-upload.*') 
                                ? 'bg-indigo-800 text-white shadow-lg' 
                                : 'text-indigo-100 hover:bg-indigo-800 hover:text-white'
                        ]"
                    >
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Quick Upload
                    </Link>

                    <!-- Admin Profile Section (Collapsible) -->
                    <div class="mt-2">
                        <button
                            @click="showingAdminMenu = !showingAdminMenu"
                            :class="[
                                'w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-colors',
                                showingAdminMenu
                                    ? 'bg-indigo-800 text-white shadow-lg' 
                                    : 'text-indigo-100 hover:bg-indigo-800 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0 mr-3">
                                    <img 
                                        v-if="$page.props.jetstream?.managesProfilePhotos && $page.props.auth?.user?.profile_photo_url"
                                        class="h-8 w-8 rounded-full object-cover border-2 border-indigo-400"
                                        :src="$page.props.auth.user.profile_photo_url" 
                                        :alt="$page.props.auth?.user?.name || 'User'"
                                    >
                                    <div v-else class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                                    </div>
                                </div>
                                <span>Admin</span>
                            </div>
                            <svg 
                                class="h-4 w-4 transition-transform duration-200"
                                :class="{ 'rotate-180': showingAdminMenu }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <!-- Expanded Admin Details -->
                        <div 
                            v-show="showingAdminMenu"
                            class="mt-2 px-4 py-3 bg-indigo-900 rounded-lg border border-indigo-800"
                        >
                            <div class="space-y-2">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <img 
                                            v-if="$page.props.jetstream?.managesProfilePhotos && $page.props.auth?.user?.profile_photo_url"
                                            class="h-10 w-10 rounded-full object-cover border-2 border-indigo-400"
                                            :src="$page.props.auth.user.profile_photo_url" 
                                            :alt="$page.props.auth?.user?.name || 'User'"
                                        >
                                        <div v-else class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                                            {{ $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white">
                                            {{ $page.props.auth?.user?.name || 'Admin' }}
                                        </p>
                                        <p class="text-xs text-indigo-200 truncate">
                                            {{ $page.props.auth?.user?.email || 'admin@testmarks.com' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="pt-2 border-t border-indigo-800 space-y-1">
                                    <Link 
                                        :href="route('profile.show')" 
                                        class="block px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-800 rounded transition-colors"
                                    >
                                        Profile
                                    </Link>
                                    <Link 
                                        v-if="$page.props.jetstream?.hasApiFeatures" 
                                        :href="route('api-tokens.index')" 
                                        class="block px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-800 rounded transition-colors"
                                    >
                                        API Tokens
                                    </Link>
                                    <Link 
                                        :href="route('reset.index')" 
                                        class="block px-3 py-2 text-sm text-red-300 hover:bg-indigo-800 rounded transition-colors"
                                    >
                                        Reset All Data
                                    </Link>
                                    <form @submit.prevent="logout" class="block">
                                        <button type="submit" class="w-full text-left px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-800 rounded transition-colors">
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
            </aside>

            <!-- Mobile Overlay for Sidebar -->
            <div 
                v-if="showingMobileMenu"
                @click="showingMobileMenu = false"
                class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 lg:hidden"
            ></div>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col lg:ml-0">
                <!-- Top Bar -->
                <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
                    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                        <!-- Mobile Menu Button -->
                        <button
                            @click="showingMobileMenu = !showingMobileMenu"
                            class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <!-- Page Title (Mobile) -->
                        <h1 class="lg:hidden text-lg font-semibold text-gray-900">{{ title }}</h1>

                        <!-- User Menu (Desktop) -->
                        <div class="hidden lg:flex lg:items-center lg:space-x-4">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center space-x-3 text-sm focus:outline-none">
                                        <div class="flex-shrink-0">
                                            <img 
                                                v-if="$page.props.jetstream.managesProfilePhotos"
                                                class="h-8 w-8 rounded-full object-cover border-2 border-indigo-500"
                                                :src="$page.props.auth.user.profile_photo_url" 
                                                :alt="$page.props.auth.user.name"
                                            >
                                            <div v-else class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold text-sm">
                                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <span class="text-gray-700 font-medium">{{ $page.props.auth.user.name }}</span>
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs text-gray-400">Manage Account</div>
                                    <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                                    <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">API Tokens</DropdownLink>
                                    <div class="border-t border-gray-200" />
                                    <div class="block px-4 py-2 text-xs text-gray-400">Administration</div>
                                    <DropdownLink :href="route('reset.index')" class="text-red-600 hover:text-red-700">Reset All Data</DropdownLink>
                                    <div class="border-t border-gray-200" />
                                    <form @submit.prevent="logout">
                                        <DropdownLink as="button">Log Out</DropdownLink>
                                    </form>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- User Menu (Mobile) -->
                        <div class="lg:hidden relative">
                            <button
                                @click="showingUserMenu = !showingUserMenu"
                                class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                            >
                                <img 
                                    v-if="$page.props.jetstream.managesProfilePhotos"
                                    class="h-8 w-8 rounded-full object-cover"
                                    :src="$page.props.auth.user.profile_photo_url" 
                                    :alt="$page.props.auth.user.name"
                                >
                                <div v-else class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold text-sm">
                                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </div>
                            </button>
                            
                            <!-- Mobile User Dropdown -->
                            <div
                                v-if="showingUserMenu"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                            >
                                <Link :href="route('profile.show')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</Link>
                                <Link v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">API Tokens</Link>
                                <div class="border-t border-gray-200"></div>
                                <Link :href="route('reset.index')" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Reset All Data</Link>
                                <div class="border-t border-gray-200"></div>
                                <form @submit.prevent="logout">
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>
                
                <!-- Mobile User Menu Overlay -->
                <div 
                    v-if="showingUserMenu"
                    @click="showingUserMenu = false"
                    class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 lg:hidden"
                ></div>

                <!-- Page Heading -->
                <header v-if="$slots.header" class="bg-white border-b border-gray-200">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-x-hidden">
                    <div class="py-6">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
