<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    student: Object,
    examHistory: Array,
});

// Get all unique subjects from all exams and sort them consistently
// This creates a fixed column structure for alignment
const allSubjects = computed(() => {
    if (!props.examHistory || !Array.isArray(props.examHistory) || props.examHistory.length === 0) {
        return [];
    }
    
    const subjectMap = new Map();
    
    // Collect all subjects from all exams
    props.examHistory.forEach(exam => {
        if (exam && exam.subjects && Array.isArray(exam.subjects)) {
            exam.subjects.forEach(subject => {
                if (subject && subject.id) {
                    if (!subjectMap.has(subject.id)) {
                        subjectMap.set(subject.id, {
                            id: subject.id,
                            name: subject.name,
                        });
                    }
                }
            });
        }
    });
    
    // Convert to array and sort alphabetically by name for consistency
    return Array.from(subjectMap.values()).sort((a, b) => 
        a.name.localeCompare(b.name)
    );
});

// Helper function to check if subject exists in exam
const hasSubject = (exam, subjectId) => {
    return exam.subjects.some(s => s.id === subjectId);
};

// Helper function to get subject mark for a specific exam
const getSubjectMark = (exam, subjectId) => {
    const subject = exam.subjects.find(s => s.id === subjectId);
    return subject ? subject.marks : null;
};

// Calculate Grand Total and Grand Average across all exams
const grandTotal = computed(() => {
    if (!props.examHistory || !Array.isArray(props.examHistory) || props.examHistory.length === 0) {
        return 0;
    }
    let total = 0;
    props.examHistory.forEach(exam => {
        if (exam && exam.subjects && Array.isArray(exam.subjects)) {
            exam.subjects.forEach(subject => {
                // Only count marks that are not null/undefined (excluding absent)
                if (subject && subject.marks !== null && subject.marks !== undefined) {
                    const markValue = Number(subject.marks);
                    if (!isNaN(markValue)) {
                        total += markValue;
                    }
                }
            });
        }
    });
    return Number(total) || 0;
});

const grandAverage = computed(() => {
    if (!props.examHistory || !Array.isArray(props.examHistory) || props.examHistory.length === 0) {
        return 0;
    }
    let totalMarks = 0;
    let count = 0;
    
    props.examHistory.forEach(exam => {
        if (exam && exam.subjects && Array.isArray(exam.subjects)) {
            exam.subjects.forEach(subject => {
                // Only count marks that are not null/undefined (excluding absent)
                if (subject && subject.marks !== null && subject.marks !== undefined) {
                    const markValue = Number(subject.marks);
                    if (!isNaN(markValue)) {
                        totalMarks += markValue;
                        count++;
                    }
                }
            });
        }
    });
    
    const average = count > 0 ? totalMarks / count : 0;
    return Number(average) || 0;
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatMarks = (marks) => {
    // Only null/undefined means absent
    // 0, -1, -2, etc. are valid marks and should be displayed as-is
    if (marks === null || marks === undefined) {
        return 'Absent';
    }
    // Display the mark as-is (including 0 and negative values)
    return typeof marks === 'number' ? marks.toFixed(2) : marks;
};

const getMarkColor = (marks) => {
    if (marks === null || marks === undefined) {
        return 'text-red-600'; // Absent
    }
    if (marks >= 80) {
        return 'text-green-600'; // Excellent
    } else if (marks >= 60) {
        return 'text-blue-600'; // Good
    } else if (marks >= 40) {
        return 'text-yellow-600'; // Average
    } else if (marks >= 0) {
        return 'text-orange-600'; // Below average
    } else {
        return 'text-red-600'; // Negative marks
    }
};

const getMarkBgColor = (marks) => {
    if (marks === null || marks === undefined) {
        return 'bg-red-50 border-red-200'; // Absent
    }
    if (marks >= 80) {
        return 'bg-green-50 border-green-200'; // Excellent
    } else if (marks >= 60) {
        return 'bg-blue-50 border-blue-200'; // Good
    } else if (marks >= 40) {
        return 'bg-yellow-50 border-yellow-200'; // Average
    } else if (marks >= 0) {
        return 'bg-orange-50 border-orange-200'; // Below average
    } else {
        return 'bg-red-50 border-red-200'; // Negative marks
    }
};

// Edit Marks Modal
const showEditModal = ref(false);
const examToEdit = ref(null);
const editForm = useForm({
    marks: {},
});

const openEditModal = (exam) => {
    examToEdit.value = exam;
    // Initialize form with current marks
    const marks = {};
    exam.subjects.forEach(subject => {
        marks[subject.id] = subject.marks !== null && subject.marks !== undefined ? subject.marks : '';
    });
    editForm.marks = marks;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    examToEdit.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const saveMarks = () => {
    if (!examToEdit.value) return;
    
    editForm.put(route('exam-results.update-marks', examToEdit.value.exam_result_id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

// Delete Result Modal
const showDeleteModal = ref(false);
const examToDelete = ref(null);

const openDeleteModal = (exam) => {
    examToDelete.value = exam;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    examToDelete.value = null;
};

const deleteResult = () => {
    if (!examToDelete.value) return;
    
    router.delete(route('exam-results.delete', examToDelete.value.exam_result_id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};

// Print/PDF Export function
const printReport = () => {
    window.print();
};
</script>

<template>
    <AppLayout title="Student Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Student Profile
                </h2>
                <div class="flex items-center gap-3">
                    <button
                        @click="printReport"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 print:hidden"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print / Save as PDF
                    </button>
                    <Link
                        :href="route('students.index')"
                        class="text-gray-600 hover:text-gray-900 print:hidden"
                    >
                        ← Back to Students
                    </Link>
                </div>
            </div>
        </template>

        <!-- Print Header (only visible when printing) -->
        <div class="hidden print:block print:mb-8">
            <div class="text-center print:mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-4 print:mb-6">Student Academic Report</h1>
                <div class="print:bg-gray-50 print:border print:border-gray-300 print:rounded print:p-4 print:mb-6">
                    <div class="grid grid-cols-3 gap-4 text-base print:text-sm">
                        <div class="text-left">
                            <span class="font-semibold text-gray-700">Name:</span> 
                            <span class="text-gray-900">{{ student.name }}</span>
                        </div>
                        <div class="text-center">
                            <span class="font-semibold text-gray-700">Class:</span> 
                            <span class="text-gray-900">
                                <span v-if="student.active_class_student?.academic_class">
                                    {{ student.active_class_student.academic_class.name }}
                                </span>
                                <span v-else>Not assigned</span>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-gray-700">Roll Number:</span> 
                            <span class="text-gray-900 font-mono">{{ student.active_class_student?.roll_number || student.roll_number || '-' }}</span>
                        </div>
                    </div>
                    <div v-if="student.father_name" class="mt-2 text-sm text-gray-600 text-center">
                        <span class="font-semibold">Father's Name:</span> {{ student.father_name }}
                    </div>
                </div>
            </div>
        </div>

        <div class="py-6 sm:py-8 print:py-0">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-6 print:px-0">
                <!-- Student Information Card (hidden when printing) -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl mb-6 border border-gray-100 print:hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">Student Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Name</p>
                                <p class="text-lg font-semibold text-gray-900">{{ student.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Father Name</p>
                                <p class="text-lg font-medium text-gray-700">{{ student.father_name || '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Current Class</p>
                                <p class="text-lg font-medium text-gray-900">
                                    <span
                                        v-if="student.active_class_student?.academic_class"
                                        class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200"
                                    >
                                        {{ student.active_class_student.academic_class.name }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">Not assigned</span>
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Roll Number</p>
                                <p class="text-lg font-semibold text-gray-900 font-mono">
                                    {{ student.active_class_student?.roll_number || student.roll_number || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam History Card -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-100">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">Exam History</h3>
                    </div>
                    <div class="p-6">
                        <!-- Grand Total and Average Summary -->
                        <div v-if="examHistory.length > 0" class="mb-6 bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-6">
                                    <div class="text-center">
                                        <p class="text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">Grand Total</p>
                                        <p class="text-2xl font-bold text-indigo-700">{{ typeof grandTotal === 'number' ? grandTotal.toFixed(2) : '0.00' }}</p>
                                    </div>
                                    <div class="h-12 w-px bg-indigo-300"></div>
                                    <div class="text-center">
                                        <p class="text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">Grand Average</p>
                                        <p class="text-2xl font-bold text-indigo-700">{{ typeof grandAverage === 'number' ? grandAverage.toFixed(2) : '0.00' }}</p>
                                    </div>
                                    <div class="h-12 w-px bg-indigo-300"></div>
                                    <div class="text-center">
                                        <p class="text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">Total Exams</p>
                                        <p class="text-2xl font-bold text-indigo-700">{{ examHistory.length }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="examHistory.length === 0" class="text-center py-12 print:py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400 print:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-4 text-sm text-gray-500 print:text-base print:font-semibold">No exam results found for this student.</p>
                        </div>

                        <div v-else class="space-y-2 print:space-y-4">
                            <div
                                v-for="(exam, index) in examHistory"
                                :key="index"
                                class="border border-gray-200 rounded-lg overflow-hidden shadow-sm print:shadow-none print:border-gray-300"
                            >
                                <!-- Compact Row/Column Layout with Fixed Subject Columns -->
                                <div class="bg-gray-50 px-3 py-2.5 border-b border-gray-200 print:bg-gray-100">
                                    <div class="flex items-center gap-3 text-sm">
                                        <!-- Left: Subjects in Fixed Columns - All exams use same column positions -->
                                        <div class="flex-1 grid gap-2" :style="{ gridTemplateColumns: `repeat(${allSubjects.length}, 1fr)` }">
                                            <template v-for="subject in allSubjects" :key="subject.id">
                                                <div class="flex flex-col items-center justify-center">
                                                    <!-- Only show subject name if it exists in this exam -->
                                                    <span 
                                                        v-if="hasSubject(exam, subject.id)"
                                                        class="text-xs font-semibold text-gray-600 uppercase leading-tight mb-0.5 text-center"
                                                    >
                                                        {{ subject.name }}
                                                    </span>
                                                    <span v-else class="text-xs leading-tight mb-0.5 text-center">&nbsp;</span>
                                                    <!-- Show mark if subject exists in this exam, otherwise show empty -->
                                                    <span 
                                                        v-if="hasSubject(exam, subject.id)"
                                                        :class="['text-sm font-bold leading-tight text-center', getMarkColor(getSubjectMark(exam, subject.id))]"
                                                    >
                                                        {{ formatMarks(getSubjectMark(exam, subject.id)) }}
                                                    </span>
                                                    <span v-else class="text-sm font-bold leading-tight text-center">&nbsp;</span>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Middle: Total, Average, Status (Stacked) -->
                                        <div class="flex items-center gap-3 border-l border-gray-300 pl-3 flex-shrink-0">
                                            <div class="flex flex-col items-center min-w-[50px]">
                                                <span class="text-xs font-semibold text-gray-600 leading-tight mb-0.5">Total</span>
                                                <span class="text-sm font-bold text-gray-900 leading-tight">{{ typeof exam.total === 'number' ? exam.total.toFixed(2) : exam.total }}</span>
                                            </div>
                                            <div class="flex flex-col items-center min-w-[50px]">
                                                <span class="text-xs font-semibold text-gray-600 leading-tight mb-0.5">Avg</span>
                                                <span class="text-sm font-bold text-indigo-600 leading-tight">{{ typeof exam.average === 'number' ? exam.average.toFixed(2) : exam.average }}</span>
                                            </div>
                                            <div class="flex flex-col items-center min-w-[30px]">
                                                <span class="text-xs font-semibold text-gray-600 leading-tight mb-0.5">Status</span>
                                                <span
                                                    :class="[
                                                        'px-1.5 py-0.5 rounded text-xs font-semibold leading-tight',
                                                        exam.status === 'present'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                    ]"
                                                >
                                                    {{ exam.status === 'present' ? 'P' : 'A' }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Right: Actions, Exam Name, Date -->
                                        <div class="flex items-center gap-2 border-l border-gray-300 pl-3 print:hidden flex-shrink-0">
                                            <button
                                                @click="openEditModal(exam)"
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                                title="Edit Marks"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="openDeleteModal(exam)"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                                                title="Delete Result"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex flex-col items-end text-right border-l border-gray-300 pl-3 min-w-[140px] flex-shrink-0">
                                            <span class="text-sm font-bold text-gray-900 leading-tight">{{ exam.exam_name }}</span>
                                            <span class="text-xs text-gray-600 leading-tight">{{ formatDate(exam.exam_date) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Print: Summary Section at the end -->
                        <div v-if="examHistory.length > 0" class="hidden print:block print:mt-8 print:pt-6 print:border-t-2 print:border-gray-600">
                            <div class="print:bg-gray-100 print:border-2 print:border-gray-600 print:rounded print:p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-4 text-center">Summary</h3>
                                <div class="grid grid-cols-3 gap-6 text-center">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 mb-1">Grand Total</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ typeof grandTotal === 'number' ? grandTotal.toFixed(2) : '0.00' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 mb-1">Grand Average</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ typeof grandAverage === 'number' ? grandAverage.toFixed(2) : '0.00' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 mb-1">Total Exams</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ examHistory.length }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 text-center text-sm text-gray-600">
                                    <p>Generated on: {{ new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Marks Modal -->
        <Modal :show="showEditModal" @close="closeEditModal" max-width="2xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Edit Marks: {{ examToEdit?.exam_name }}
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    Student: <span class="font-semibold">{{ student.name }}</span> | 
                    Date: <span class="font-semibold">{{ examToEdit ? formatDate(examToEdit.exam_date) : '' }}</span>
                </p>

                <form @submit.prevent="saveMarks">
                    <div class="space-y-4">
                        <div
                            v-for="subject in examToEdit?.subjects || []"
                            :key="subject.id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <InputLabel :for="`subject_${subject.id}`" :value="subject.name" class="text-sm font-medium text-gray-700 w-32" />
                            <div class="flex-1 max-w-xs ml-4">
                                <TextInput
                                    :id="`subject_${subject.id}`"
                                    v-model="editForm.marks[subject.id]"
                                    type="number"
                                    step="0.01"
                                    class="block w-full"
                                    placeholder="Enter marks (leave blank for absent)"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Current: {{ formatMarks(subject.marks) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeEditModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing">
                            <span v-if="editForm.processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Result Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete Exam Result
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete the result for "{{ examToDelete?.exam_name }}"?
                </p>
                <p class="mt-2 text-sm font-medium text-red-600">
                    ⚠️ This will permanently delete this exam result for {{ student.name }}. This action cannot be undone.
                </p>
                <p v-if="examToDelete" class="mt-2 text-sm text-gray-700">
                    Date: {{ formatDate(examToDelete.exam_date) }} | 
                    Total: {{ typeof examToDelete.total === 'number' ? examToDelete.total.toFixed(2) : examToDelete.total }} | 
                    Average: {{ typeof examToDelete.average === 'number' ? examToDelete.average.toFixed(2) : examToDelete.average }}
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        Cancel
                    </SecondaryButton>
                    <DangerButton @click="deleteResult">
                        Delete Result
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
/* Print Styles */
@media print {
    @page {
        size: A4;
        margin: 1cm;
    }
    
    body {
        background: white;
        color: black;
    }
    
    /* Hide non-essential elements */
    .print\\:hidden {
        display: none !important;
    }
    
    /* Optimize colors for printing */
    .print\\:bg-gray-100 {
        background-color: #f3f4f6 !important;
    }
    
    .print\\:border-gray-400 {
        border-color: #9ca3af !important;
    }
    
    .print\\:text-gray-900 {
        color: #111827 !important;
    }
    
    /* Remove shadows for cleaner print */
    .shadow-lg,
    .shadow-sm {
        box-shadow: none !important;
    }
    
    /* Ensure text is readable */
    * {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

