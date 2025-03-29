<script setup lang="ts">
import ProjectForm from '@/components/ProjectForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

// Define structure for available resources passed from controller
interface AvailableResource {
    resource_id: number;
    name: string;
    is_assigned_elsewhere?: boolean; // Make optional for create page, align with ProjectForm
}

// Define props passed from ProjectController->create
const props = defineProps<{
    availableResources: AvailableResource[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Projects', href: route('projects.index') }, // Link back to main projects index
    { title: 'Create', href: route('projects.create') },
];

// Handle form submission from ProjectForm component
function handleSubmit(formData: any) {
    // Type formData more strictly if needed
    // The form object from useForm includes _method, which we don't want here for POST
    const dataToSubmit = { ...formData };
    delete dataToSubmit._method; // Remove _method for store action

    router.post(route('projects.store'), dataToSubmit, {
        onError: (errors) => {
            // Errors are automatically handled by useForm, but you can add notifications here
            console.error('Error creating project:', errors);
        },
        onSuccess: () => {
            // Redirect is handled by the controller, but you could show a notification
        },
    });
}

// Handle cancellation
function handleCancel() {
    router.visit(route('projects.index')); // Go back to the index page
}
</script>

<template>
    <Head title="Create Project/Task" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <ProjectForm
                :available-resources="props.availableResources"
                @submit="handleSubmit"
                @cancel="handleCancel"
                form-title="Create New Project/Task"
                form-description="Fill in the details for the new project or task."
                submit-button-text="Create"
            />
        </div>
    </AppLayout>
</template>
