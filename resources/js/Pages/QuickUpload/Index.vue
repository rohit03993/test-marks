<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { router } from '@inertiajs/vue3';

const form = useForm({
    exam_name: '',
    exam_date: '',
    file: null,
    column_mapping: {
        roll_number: '',
        physics: '',
        chemistry: '',
        mathematics: '',
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        const response = await fetch(route('quick-upload.get-headers'), {
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
    
    // Auto-detect roll number
    const rollIndex = lowerHeaders.findIndex(h => 
        h.includes('roll') || h.includes('id') || h.includes('number')
    );
    if (rollIndex !== -1) {
        form.column_mapping.roll_number = headers[rollIndex];
    }

    // Auto-detect Physics
    const physicsIndex = lowerHeaders.findIndex(h => h.includes('physics') || h.includes('phy'));
    if (physicsIndex !== -1) {
        form.column_mapping.physics = headers[physicsIndex];
    }

    // Auto-detect Chemistry
    const chemistryIndex = lowerHeaders.findIndex(h => h.includes('chemistry') || h.includes('chem'));
    if (chemistryIndex !== -1) {
        form.column_mapping.chemistry = headers[chemistryIndex];
    }

    // Auto-detect Mathematics
    const mathIndex = lowerHeaders.findIndex(h => 
        h.includes('mathematics') || h.includes('math') || h.includes('maths')
    );
    if (mathIndex !== -1) {
        form.column_mapping.mathematics = headers[mathIndex];
    }
};

const submit = () => {
    if (!form.exam_name) {
        alert('Please enter exam name.');
        return;
    }

    if (!form.exam_date) {
        alert('Please select exam date.');
        return;
    }

    if (!form.file) {
        alert('Please select a file first.');
        return;
    }

    if (!form.column_mapping.roll_number) {
        alert('Please map the Roll Number column.');
        return;
    }

    form.post(route('quick-upload.store'), {
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
        roll_number: '',
        physics: '',
        chemistry: '',
        mathematics: '',
    };
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<template>
    <AppLayout title="Quick Upload Results">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Quick Upload Exam Results
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Upload results without creating a class. System will automatically match students by roll number.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-6">
                            <!-- Exam Name -->
                            <div>
                                <InputLabel for="exam_name" value="Exam/Test Name *" />
                                <TextInput
                                    id="exam_name"
                                    v-model="form.exam_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., Unit Test 1, Mid Term Exam"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.exam_name" class="mt-2" />
                            </div>

                            <!-- Exam Date -->
                            <div>
                                <InputLabel for="exam_date" value="Exam Date *" />
                                <TextInput
                                    id="exam_date"
                                    v-model="form.exam_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.exam_date" class="mt-2" />
                            </div>

                            <!-- File Upload -->
                            <div>
                                <InputLabel for="file" value="Excel/CSV File *" />
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
                                    Select which columns in your Excel file correspond to each field. The system will automatically match students by roll number.
                                </p>

                                <div class="space-y-4">
                                    <!-- Roll Number Mapping -->
                                    <div>
                                        <InputLabel for="roll_number_column" value="Roll Number *" />
                                        <select
                                            id="roll_number_column"
                                            v-model="form.column_mapping.roll_number"
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
                                        <InputError :message="form.errors['column_mapping.roll_number']" class="mt-2" />
                                    </div>

                                    <!-- Physics Mapping -->
                                    <div>
                                        <InputLabel for="physics_column" value="Physics Marks (Optional)" />
                                        <select
                                            id="physics_column"
                                            v-model="form.column_mapping.physics"
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
                                        <InputError :message="form.errors['column_mapping.physics']" class="mt-2" />
                                    </div>

                                    <!-- Chemistry Mapping -->
                                    <div>
                                        <InputLabel for="chemistry_column" value="Chemistry Marks (Optional)" />
                                        <select
                                            id="chemistry_column"
                                            v-model="form.column_mapping.chemistry"
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
                                        <InputError :message="form.errors['column_mapping.chemistry']" class="mt-2" />
                                    </div>

                                    <!-- Mathematics Mapping -->
                                    <div>
                                        <InputLabel for="mathematics_column" value="Mathematics Marks (Optional)" />
                                        <select
                                            id="mathematics_column"
                                            v-model="form.column_mapping.mathematics"
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
                                        <InputError :message="form.errors['column_mapping.mathematics']" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div v-if="loadingHeaders" class="text-center py-4">
                                <p class="text-gray-600">Reading file headers...</p>
                            </div>

                            <!-- Instructions -->
                            <div v-if="!hasFile" class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">How it works:</h4>
                                <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                                    <li>Enter exam name and date</li>
                                    <li>Upload Excel/CSV file with roll numbers and marks</li>
                                    <li>System automatically matches students by roll number from master data</li>
                                    <li>Results are saved and linked to student profiles</li>
                                    <li>No need to create classes - perfect for quick uploads!</li>
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
                                :disabled="form.processing || !form.exam_name || !form.exam_date || !hasFile || !form.column_mapping.roll_number"
                            >
                                <span v-if="form.processing">Uploading...</span>
                                <span v-else>Upload Results</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

