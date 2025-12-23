<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { router } from '@inertiajs/vue3';

const form = useForm({
    file: null,
    column_mapping: {
        name: '',
        father_name: '',
        roll_number: '',
    },
});

const fileInput = ref(null);
const headers = ref([]);
const loadingHeaders = ref(false);
const selectedFile = ref(null);

const hasFile = computed(() => selectedFile.value !== null);
const hasHeaders = computed(() => headers.value.length > 0);

const selectFile = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        form.file = file;
        loadHeaders(file);
    }
};

const loadHeaders = async (file) => {
    if (!file) return;

    loadingHeaders.value = true;
    const formData = new FormData();
    formData.append('file', file);

    try {
        // Get CSRF token safely
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        const response = await fetch(route('students.get-headers'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Failed to read file headers');
        }
        
        if (data.headers && data.headers.length > 0) {
            headers.value = data.headers;
            // Auto-detect common column names
            autoDetectColumns(data.headers);
        } else {
            alert('No headers found in file. Please ensure the first row contains column names.');
        }
    } catch (error) {
        console.error('Error loading headers:', error);
        alert('Error reading file headers: ' + (error.message || 'Please try again.'));
    } finally {
        loadingHeaders.value = false;
    }
};

const autoDetectColumns = (headers) => {
    const lowerHeaders = headers.map(h => String(h || '').toLowerCase().trim());
    
    // Auto-detect name
    const nameIndex = lowerHeaders.findIndex(h => 
        h.includes('name') && !h.includes('father') && !h.includes('parent')
    );
    if (nameIndex !== -1) {
        form.column_mapping.name = headers[nameIndex];
    }

    // Auto-detect father name
    const fatherIndex = lowerHeaders.findIndex(h => 
        h.includes('father') || h.includes('parent') || h.includes('guardian')
    );
    if (fatherIndex !== -1) {
        form.column_mapping.father_name = headers[fatherIndex];
    }

    // Auto-detect roll number
    const rollIndex = lowerHeaders.findIndex(h => 
        h.includes('roll') || h.includes('id') || h.includes('number')
    );
    if (rollIndex !== -1) {
        form.column_mapping.roll_number = headers[rollIndex];
    }
};

const submit = () => {
    if (!form.file) {
        alert('Please select a file first.');
        return;
    }

    if (!form.column_mapping.name) {
        alert('Please map the Name column.');
        return;
    }

    form.post(route('students.upload.store'), {
        forceFormData: true,
        onSuccess: () => {
            router.visit(route('students.index'));
        },
    });
};

const resetFile = () => {
    selectedFile.value = null;
    form.file = null;
    headers.value = [];
    form.column_mapping = {
        name: '',
        father_name: '',
        roll_number: '',
    };
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<template>
    <AppLayout title="Upload Students">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Upload Students
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
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-6">
                            <!-- File Upload -->
                            <div>
                                <InputLabel for="file" value="Excel/CSV File" />
                                <div class="mt-2">
                                    <input
                                        ref="fileInput"
                                        id="file"
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        @change="selectFile"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    />
                                </div>
                                <InputError :message="form.errors.file" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    Supported formats: .xlsx, .xls, .csv (Max: 10MB)
                                </p>
                            </div>

                            <!-- Column Mapping -->
                            <div v-if="hasHeaders" class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Map Columns
                                </h3>
                                <p class="text-sm text-gray-600 mb-4">
                                    Select which columns in your Excel file correspond to each field.
                                </p>

                                <div class="space-y-4">
                                    <!-- Name Mapping -->
                                    <div>
                                        <InputLabel for="name_column" value="Student Name *" />
                                        <select
                                            id="name_column"
                                            v-model="form.column_mapping.name"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        >
                                            <option value="">-- Select Column --</option>
                                            <option
                                                v-for="(header, index) in headers"
                                                :key="index"
                                                :value="header"
                                            >
                                                {{ header || `Column ${index + 1}` }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors['column_mapping.name']" class="mt-2" />
                                    </div>

                                    <!-- Father Name Mapping -->
                                    <div>
                                        <InputLabel for="father_name_column" value="Father Name (Optional)" />
                                        <select
                                            id="father_name_column"
                                            v-model="form.column_mapping.father_name"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">-- Select Column (Optional) --</option>
                                            <option
                                                v-for="(header, index) in headers"
                                                :key="index"
                                                :value="header"
                                            >
                                                {{ header || `Column ${index + 1}` }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors['column_mapping.father_name']" class="mt-2" />
                                    </div>

                                    <!-- Roll Number Mapping -->
                                    <div>
                                        <InputLabel for="roll_number_column" value="Roll Number (Optional)" />
                                        <select
                                            id="roll_number_column"
                                            v-model="form.column_mapping.roll_number"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">-- Select Column (Optional) --</option>
                                            <option
                                                v-for="(header, index) in headers"
                                                :key="index"
                                                :value="header"
                                            >
                                                {{ header || `Column ${index + 1}` }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors['column_mapping.roll_number']" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div v-if="loadingHeaders" class="text-center py-4">
                                <p class="text-gray-600">Reading file headers...</p>
                            </div>

                            <!-- Instructions -->
                            <div v-if="!hasFile" class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">Instructions:</h4>
                                <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                                    <li>Upload an Excel or CSV file with student data</li>
                                    <li>First row should contain column headers</li>
                                    <li>Required column: Student Name</li>
                                    <li>Optional columns: Father Name, Roll Number</li>
                                    <li>System will automatically detect common column names</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <SecondaryButton
                                type="button"
                                @click="resetFile"
                                class="mr-3"
                            >
                                Reset
                            </SecondaryButton>
                            <PrimaryButton
                                :disabled="form.processing || !hasFile || !form.column_mapping.name"
                            >
                                <span v-if="form.processing">Uploading...</span>
                                <span v-else>Upload Students</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

