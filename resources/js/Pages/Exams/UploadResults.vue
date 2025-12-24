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

const props = defineProps({
    exam: Object,
});

// Initialize column mapping dynamically based on exam subjects
const initColumnMapping = () => {
    const mapping = { roll_number: '' };
    if (props.exam?.subjects) {
        props.exam.subjects.forEach(subject => {
            mapping[subject.name.toLowerCase()] = '';
        });
    }
    return mapping;
};

const form = useForm({
    file: null,
    class_id: props.exam?.classes?.length === 1 ? props.exam.classes[0].id : '',
    column_mapping: initColumnMapping(),
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

        const response = await fetch(route('exams.get-headers'), {
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

    // Auto-detect subjects dynamically
    if (props.exam?.subjects) {
        props.exam.subjects.forEach(subject => {
            const subjectNameLower = subject.name.toLowerCase();
            const subjectKey = subjectNameLower;
            
            // Try to find matching header
            const subjectIndex = lowerHeaders.findIndex(h => {
                const headerLower = h.toLowerCase();
                // Direct match
                if (headerLower === subjectNameLower) return true;
                // Contains match
                if (headerLower.includes(subjectNameLower) || subjectNameLower.includes(headerLower)) return true;
                // Common abbreviations
                if (subjectNameLower.includes('physics') && (headerLower.includes('phy') || headerLower.includes('physics'))) return true;
                if (subjectNameLower.includes('chemistry') && (headerLower.includes('chem') || headerLower.includes('chemistry'))) return true;
                if (subjectNameLower.includes('mathematics') && (headerLower.includes('math') || headerLower.includes('maths'))) return true;
                if (subjectNameLower.includes('biology') && (headerLower.includes('bio') || headerLower.includes('biology'))) return true;
                return false;
            });
            
            if (subjectIndex !== -1 && !form.column_mapping[subjectKey]) {
                form.column_mapping[subjectKey] = headers[subjectIndex];
            }
        });
    }
};

const submit = () => {
    if (!form.file) {
        alert('Please select a file first.');
        return;
    }

    if (props.exam.classes && props.exam.classes.length > 1 && !form.class_id) {
        alert('Please select a class first.');
        return;
    }

    if (!form.column_mapping.roll_number) {
        alert('Please map the Roll Number column.');
        return;
    }

    form.post(route('exams.upload-results.store', props.exam.id), {
        forceFormData: true,
        onSuccess: () => {
            router.visit(route('exams.index'));
        },
    });
};

const resetFile = () => {
    selectedFile.value = null;
    form.file = null;
    headers.value = [];
    form.column_mapping = initColumnMapping();
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<template>
    <AppLayout title="Upload Exam Results">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Upload Results: {{ exam.name }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Classes: {{ exam.classes?.map(c => c.name).join(', ') || 'N/A' }} | Date: {{ new Date(exam.exam_date).toLocaleDateString() }}
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
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-6">
                            <!-- Class Selection (if multiple classes) -->
                            <div v-if="exam.classes && exam.classes.length > 1">
                                <InputLabel for="class_id" value="Select Class *" />
                                <select
                                    id="class_id"
                                    v-model="form.class_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">-- Select Class --</option>
                                    <option
                                        v-for="classItem in exam.classes"
                                        :key="classItem.id"
                                        :value="classItem.id"
                                    >
                                        {{ classItem.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.class_id" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">
                                    This exam is for multiple classes. Select which class you're uploading results for.
                                </p>
                            </div>

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

                                    <!-- Dynamic Subject Mapping -->
                                    <div v-for="subject in exam.subjects" :key="subject.id">
                                        <InputLabel :for="`subject_${subject.id}_column`" :value="`${subject.name} Marks`" />
                                        <select
                                            :id="`subject_${subject.id}_column`"
                                            v-model="form.column_mapping[subject.name.toLowerCase()]"
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
                                        <InputError :message="form.errors[`column_mapping.${subject.name.toLowerCase()}`]" class="mt-2" />
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
                                    <li>Upload an Excel or CSV file with exam results</li>
                                    <li>First row should contain column headers</li>
                                    <li>Required column: Roll Number</li>
                                    <li v-if="exam.subjects && exam.subjects.length > 0">
                                        Subject columns for this exam: {{ exam.subjects.map(s => s.name).join(', ') }}
                                    </li>
                                    <li>System will automatically detect and map subject columns</li>
                                    <li>System will automatically map students by roll number</li>
                                    <li>Students in class but not in Excel will be marked as absent</li>
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
                                :disabled="form.processing || !hasFile || !form.column_mapping.roll_number"
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

