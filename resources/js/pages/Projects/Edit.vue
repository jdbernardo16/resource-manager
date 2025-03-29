<script setup lang="ts">
import ProjectForm from '@/components/ProjectForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

// Define structure for available resources
interface AvailableResource {
    resource_id: number;
    name: string;
    is_assigned_elsewhere: boolean; // Added flag
}

// Define structure for the project being edited
interface Project {
    project_id: number;
    project_name: string;
    project_description: string | null;
    start_date: string; // Assuming string format from controller
    time_estimate_hours: number;
    is_task: boolean;
    deadline?: string | null; // Add optional deadline
    status: string; // Add status field
    // Include assignment details if needed
    assignments?: Array<{ resource_id: number }>; // Basic structure for assignment
}

// Define props passed from ProjectController->edit
const props = defineProps<{
    project: Project;
    availableResources: AvailableResource[];
    currentResourceIds?: number[]; // Changed from currentResourceId, expect array
}>();

const pageTitle = computed(() => `Edit ${props.project.is_task ? 'Task' : 'Project'}: ${props.project.project_name}`);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: props.project.is_task ? 'Tasks' : 'Projects', href: route('projects.index', { type: props.project.is_task ? 'task' : 'project' }) },
    { title: 'Edit', href: route('projects.edit', props.project.project_id) },
];

// Handle form submission from ProjectForm component
function handleSubmit(formData: any) {
    // Type formData more strictly if needed
    // The form object includes _method='PUT', which Inertia needs for PUT requests
    router.put(route('projects.update', props.project.project_id), formData, {
        onError: (errors) => {
            console.error('Error updating project:', errors);
        },
        onSuccess: () => {
            // Redirect is handled by the controller
        },
    });
}

// Handle cancellation
function handleCancel() {
    router.visit(route('projects.index', { type: props.project.is_task ? 'task' : 'project' }));
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <ProjectForm
                :project="props.project"
                :available-resources="props.availableResources"
                :current-resource-ids="props.currentResourceIds"
                @submit="handleSubmit"
                @cancel="handleCancel"
                :form-title="pageTitle"
                form-description="Update the project/task details below."
                submit-button-text="Update"
            />
        </div>
    </AppLayout>
</template>
