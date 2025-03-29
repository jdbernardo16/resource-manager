<script setup lang="ts">
import ResourceForm from '@/components/ResourceForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Resources', href: route('resources.index') },
    { title: 'Create', href: route('resources.create') },
];

// Handle form submission from ResourceForm component
function handleSubmit(formData: any) {
    const dataToSubmit = { ...formData };
    delete dataToSubmit._method; // Remove _method for store action

    router.post(route('resources.store'), dataToSubmit, {
        onError: (errors) => {
            console.error('Error creating resource:', errors);
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
    <Head title="Add Resource" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <ResourceForm
                @submit="handleSubmit"
                @cancel="handleCancel"
                form-title="Add New Resource"
                form-description="Enter the details for the new resource."
                submit-button-text="Add Resource"
            />
        </div>
    </AppLayout>
</template>
