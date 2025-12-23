<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    classItem: Object,
});

const form = useForm({
    name: props.classItem?.name || '',
});

const submit = () => {
    if (props.classItem) {
        form.put(route('classes.update', props.classItem.id));
    } else {
        form.post(route('classes.store'));
    }
};
</script>

<template>
    <AppLayout :title="classItem ? 'Edit Class' : 'Create Class'">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ classItem ? 'Edit Class' : 'Create Class' }}
                </h2>
                <Link
                    :href="route('classes.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Back to Classes
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-6">
                            <!-- Class Name -->
                            <div>
                                <InputLabel for="name" value="Class Name *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., Class 12 NEET 2025-26"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    Enter a descriptive name for the class (e.g., "Class 12 NEET 2025-26")
                                </p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <Link
                                :href="route('classes.index')"
                                class="mr-3"
                            >
                                <SecondaryButton type="button">
                                    Cancel
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>{{ classItem ? 'Update Class' : 'Create Class' }}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

