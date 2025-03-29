<script setup lang="ts">
import ResourceForm from '@/components/ResourceForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

// Define structure for the resource being edited
interface Resource {
    resource_id: number;
    name: string;
    email: string;
    skills: string | null;
}

// Define props passed from ResourceController->edit
const props = defineProps<{
    resource: Resource;
}>();

const pageTitle = computed(() => `Edit Resource: ${props.resource.name}`);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Resources', href: route('resources.index') },
    { title: 'Edit', href: route('resources.edit', props.resource.resource_id) },
];

// Handle form submission from ResourceForm component
function handleSubmit(formData: any) {
    router.put(route('resources.update', props.resource.resource_id), formData, {
        onError: (errors) => {
            console.error('Error updating resource:', errors);
        },
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
}

// Handle cancellation
function handleCancel() {
    router.visit(route('resources.index'));
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <ResourceForm
                :resource="props.resource"
                @submit="handleSubmit"
                @cancel="handleCancel"
                :form-title="pageTitle"
                form-description="Update the resource details below."
                submit-button-text="Update Resource"
            />
        </div>
    </AppLayout>
</template>
