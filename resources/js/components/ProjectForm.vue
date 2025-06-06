<script setup lang="ts">
import InputError from '@/components/InputError.vue'; // Assuming InputError component exists for displaying errors
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

// Define the structure for available resources passed as props
interface AvailableResource {
    resource_id: number;
    name: string;
    is_assigned_elsewhere?: boolean; // Make optional to align with Create.vue
}

// Define the structure for project data (optional, for editing)
interface ProjectData {
    project_id?: number; // Optional for create
    project_name: string;
    project_description: string | null;
    start_date: string; // Use YYYY-MM-DD format for date input
    time_estimate_hours: number;
    is_task: boolean;
    deadline?: string | null; // Add optional deadline
    status: string; // Add status field
    // Include current resource IDs if editing
    resource_ids?: number[]; // Changed from resource_id
}

const props = defineProps<{
    availableResources: AvailableResource[];
    project?: ProjectData | null; // Project data for editing, null for create
    currentResourceIds?: number[]; // Changed from currentResourceId, expect an array
    submitButtonText?: string;
    formTitle?: string;
    formDescription?: string;
}>();

const emit = defineEmits<{
    (e: 'submit', formData: typeof form): void;
    (e: 'cancel'): void;
}>();

// Define statuses for dropdown
const projectStatuses = [
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'archived', label: 'Archived' },
    { value: 'on_pause', label: 'On Pause' },
];

// Initialize form state using useForm
const form = useForm({
    project_name: props.project?.project_name ?? '',
    project_description: props.project?.project_description ?? '',
    // Ensure date is in YYYY-MM-DD format for input type="date"
    start_date: props.project?.start_date ? new Date(props.project.start_date).toISOString().split('T')[0] : '',
    time_estimate_hours: props.project?.time_estimate_hours ?? 0, // Use 0 as default instead of null
    is_task: props.project?.is_task ?? false,
    // Use currentResourceIds for default selection in edit mode, otherwise an empty array
    resource_ids: props.currentResourceIds ?? [], // Changed to resource_ids (array)
    // Format deadline similarly to start_date, use undefined if null/missing
    deadline: props.project?.deadline ? new Date(props.project.deadline).toISOString().split('T')[0] : undefined,
    status: props.project?.status ?? 'active', // Initialize status, default to 'active'
    _method: props.project ? 'PUT' : 'POST', // Set method for update or store
});

// Handle form submission
function submitForm() {
    emit('submit', form);
}

// Handle cancellation
function cancelForm() {
    emit('cancel');
}

// Computed property for the title
const cardTitle = computed(() => props.formTitle ?? (props.project ? 'Edit Project/Task' : 'Create New Project/Task'));
const cardDescription = computed(() => props.formDescription ?? (props.project ? 'Update the details below.' : 'Fill in the details below.'));
const submitText = computed(() => props.submitButtonText ?? (props.project ? 'Update' : 'Create'));

// Computed property to display the calculated end date
const displayEstimatedEndDate = computed(() => {
    // Pass the number of selected resources to the calculation function
    const numberOfResources = form.resource_ids.length > 0 ? form.resource_ids.length : 1; // Avoid division by zero if none selected yet
    return calculateEstimatedEndDate(form.start_date, form.time_estimate_hours, numberOfResources);
});

// Watch for project prop changes if the component might be reused without unmounting
watch(
    () => props.project,
    (newProject) => {
        form.reset(); // Reset form state and errors
        form.project_name = newProject?.project_name ?? '';
        form.project_description = newProject?.project_description ?? '';
        form.start_date = newProject?.start_date ? new Date(newProject.start_date).toISOString().split('T')[0] : '';
        form.time_estimate_hours = newProject?.time_estimate_hours ?? 0; // Use 0 as default instead of null
        form.is_task = newProject?.is_task ?? false;
        form.resource_ids = props.currentResourceIds ?? []; // Use updated currentResourceIds (array)
        form.deadline = newProject?.deadline ? new Date(newProject.deadline).toISOString().split('T')[0] : undefined; // Reset/set deadline (use undefined)
        form.status = newProject?.status ?? 'active'; // Reset/set status
        form._method = newProject ? 'PUT' : 'POST';
    },
    { deep: true },
);

watch(
    () => props.currentResourceIds,
    (newResourceIds) => {
        // Update form's resource_ids only if it wasn't already set by the project prop watcher
        // Compare arrays by converting to JSON strings for simplicity, or use a deep comparison library
        if (JSON.stringify(form.resource_ids) !== JSON.stringify(newResourceIds ?? [])) {
            form.resource_ids = newResourceIds ?? [];
        }
    },
    { deep: true }, // Add deep watch for array changes
);

// --- Date Calculation Helpers (copied from Index.vue) ---

// Format date helper (basic)
function formatDate(dateString: string | null | undefined): string {
    // Allow null/undefined
    if (!dateString) return 'N/A';
    try {
        // Basic check for YYYY-MM-DD format before parsing
        if (!/^\d{4}-\d{2}-\d{2}/.test(dateString)) return dateString;
        return new Date(dateString).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) {
        console.error('Error formatting date:', dateString, e);
        return dateString; // Return original if parsing fails
    }
}

// Helper function to add working days, skipping weekends
function addWorkDays(startDate: Date, days: number): Date {
    let currentDate = new Date(startDate);
    let addedDays = 0;
    while (addedDays < days) {
        currentDate.setDate(currentDate.getDate() + 1);
        const dayOfWeek = currentDate.getDay(); // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            addedDays++;
        }
    }
    return currentDate;
}

// Function to calculate estimated end date, considering number of resources
function calculateEstimatedEndDate(startDateString: string | null | undefined, estimatedHours: number, numberOfResources: number): string {
    // Ensure hours is positive and resources is at least 1
    if (!startDateString || !estimatedHours || estimatedHours <= 0 || numberOfResources <= 0) return 'N/A';

    try {
        const startDate = new Date(startDateString);
        // Check if startDate is valid
        if (isNaN(startDate.getTime())) return 'Invalid Start Date';

        // Adjust start date if it falls on a weekend
        let startDayOfWeek = startDate.getDay();
        while (startDayOfWeek === 0 || startDayOfWeek === 6) {
            // If Sunday (0) or Saturday (6)
            startDate.setDate(startDate.getDate() + 1);
            startDayOfWeek = startDate.getDay();
        }

        const baseWorkHoursPerDay = 7;
        // Calculate effective work hours per day based on number of resources
        const effectiveWorkHoursPerDay = baseWorkHoursPerDay * numberOfResources;

        // Calculate full work days needed using effective hours. Use Math.ceil to round up.
        // Subtract 1 because the start date counts as the first day.
        const workDaysNeeded = Math.ceil(estimatedHours / effectiveWorkHoursPerDay) - 1;

        // If it takes less than a full day's effective work, the end date is the start date (after weekend adjustment)
        if (workDaysNeeded < 0) {
            return formatDate(startDate.toISOString().split('T')[0]); // Format adjusted start date
        }

        const endDate = addWorkDays(startDate, workDaysNeeded);
        return formatDate(endDate.toISOString().split('T')[0]); // Format calculated end date
    } catch (e) {
        console.error('Error calculating end date:', e);
        return 'Error'; // Indicate calculation error
    }
}

// --- End Date Calculation Helpers ---
</script>

<template>
    <Card>
        <form @submit.prevent="submitForm">
            <CardHeader>
                <CardTitle>{{ cardTitle }}</CardTitle>
                <CardDescription>{{ cardDescription }}</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Project Name -->
                    <div class="space-y-2">
                        <Label for="project_name">Project/Task Name</Label>
                        <Input id="project_name" v-model="form.project_name" required />
                        <InputError :message="form.errors.project_name" />
                    </div>

                    <!-- Resource Assignment -->
                    <div class="space-y-2">
                        <Label>Assign Resources</Label>
                        <div class="max-h-40 space-y-2 overflow-y-auto rounded-md border p-2">
                            <div v-if="availableResources.length === 0" class="text-sm text-muted-foreground">No available resources</div>
                            <div v-else v-for="resource in availableResources" :key="resource.resource_id" class="flex items-center space-x-2">
                                <Checkbox
                                    :id="'resource_' + resource.resource_id"
                                    :checked="form.resource_ids.includes(resource.resource_id)"
                                    @update:checked="
                                        (checked) => {
                                            if (checked) {
                                                form.resource_ids.push(resource.resource_id);
                                            } else {
                                                form.resource_ids = form.resource_ids.filter((id) => id !== resource.resource_id);
                                            }
                                        }
                                    "
                                    :disabled="resource.is_assigned_elsewhere === true && !props.currentResourceIds?.includes(resource.resource_id)"
                                />
                                <label
                                    :for="'resource_' + resource.resource_id"
                                    class="text-sm font-medium leading-none"
                                    :class="{
                                        'cursor-not-allowed text-muted-foreground':
                                            resource.is_assigned_elsewhere === true && !props.currentResourceIds?.includes(resource.resource_id),
                                    }"
                                >
                                    {{ resource.name }}
                                    <span
                                        v-if="resource.is_assigned_elsewhere === true && !props.currentResourceIds?.includes(resource.resource_id)"
                                        class="text-xs text-muted-foreground"
                                    >
                                        (Assigned elsewhere)</span
                                    >
                                </label>
                            </div>
                        </div>
                        <InputError :message="form.errors.resource_ids" />
                    </div>

                    <!-- Status Selection -->
                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <Select v-model="form.status" required>
                            <SelectTrigger id="status">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>Project Status</SelectLabel>
                                    <SelectItem v-for="status in projectStatuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <!-- Project Description -->
                <div class="space-y-2">
                    <Label for="project_description">Description</Label>
                    <Textarea id="project_description" v-model="form.project_description" placeholder="Optional description..." />
                    <InputError :message="form.errors.project_description" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Start Date -->
                    <div class="space-y-2">
                        <Label for="start_date">Start Date</Label>
                        <Input id="start_date" type="date" v-model="form.start_date" required />
                        <InputError :message="form.errors.start_date" />
                    </div>

                    <!-- Time Estimate -->
                    <div class="space-y-2">
                        <Label for="time_estimate_hours">Estimated Hours</Label>
                        <Input id="time_estimate_hours" type="number" v-model.number="form.time_estimate_hours" required min="1" />
                        <InputError :message="form.errors.time_estimate_hours" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Deadline -->
                    <div class="space-y-2">
                        <Label for="deadline">Deadline (Optional)</Label>
                        <Input id="deadline" type="date" v-model="form.deadline" />
                        <InputError :message="form.errors.deadline" />
                    </div>
                    <!-- Empty div for alignment or add another field here -->
                    <!-- Display Estimated End Date -->
                    <div class="space-y-2">
                        <Label for="est_end_date_display">Est. End Date (Calculated)</Label>
                        <div class="h-10 w-full rounded-md border px-3 py-2 text-sm text-gray-500">{{ displayEstimatedEndDate }}</div>
                        <!-- No InputError needed as this is display-only -->
                    </div>
                </div>

                <!-- Is Task Checkbox -->
                <div class="flex items-center space-x-2">
                    <Checkbox id="is_task" :checked="form.is_task" @update:checked="form.is_task = $event" />
                    <label for="is_task" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Mark as Task (shorter duration, distinct display)
                    </label>
                    <InputError :message="form.errors.is_task" class="ml-auto" />
                    <!-- Adjust positioning if needed -->
                </div>
            </CardContent>
            <CardFooter class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="cancelForm" :disabled="form.processing">Cancel</Button>
                <Button type="submit" :disabled="form.processing">
                    <span v-if="form.processing">Processing...</span>
                    <span v-else>{{ submitText }}</span>
                </Button>
            </CardFooter>
        </form>
    </Card>
</template>
