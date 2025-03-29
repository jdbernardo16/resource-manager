<script setup lang="ts">
import { Button } from '@/components/ui/button'; // Import Button component
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'; // Import Card components
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3'; // Import router for actions
import { computed } from 'vue';

// Define props passed from DashboardController
const props = defineProps<{
    availableResources: Array<{
        resource_id: number;
        name: string;
        email: string;
        skills: string | null;
    }>;
    occupiedResources: Array<{
        resource_id: number;
        name: string;
        email: string;
        skills: string | null;
        active_assignment: {
            assignment_id: number;
            project_id: number;
            assignment_start_date: string;
            assignment_end_date: string | null;
            project?: {
                // Project details might be included if eager loaded
                project_id: number;
                project_name: string;
                is_task: boolean;
            };
        } | null;
    }>;
}>();

// Optional: Use the store to sync props (or manage local state if needed)
// const dashboardStore = useDashboardStore();
// dashboardStore.setDashboardData({
//   availableResources: props.availableResources,
//   occupiedResources: props.occupiedResources,
// });
// const available = computed(() => dashboardStore.availableResources);
// const occupied = computed(() => dashboardStore.occupiedResources);

// Use props directly for simplicity
const available = computed(() => props.availableResources);
const occupied = computed(() => props.occupiedResources);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'), // Use route helper
    },
];

// Function to handle marking an assignment as complete
function markComplete(assignmentId: number) {
    if (!assignmentId) return;

    router.post(
        route('project-assignments.complete', assignmentId),
        {},
        {
            preserveScroll: true, // Keep scroll position after redirect
            onSuccess: () => {
                // Optional: Show a success notification
                // Optional: Update local store state if not relying solely on next page load
                // dashboardStore.markResourceAsAvailable(resourceId); // Need resourceId here too
            },
            onError: (errors) => {
                // Handle errors, maybe show a notification
                console.error('Error completing assignment:', errors);
            },
        },
    );
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-6 p-4 md:grid-cols-2">
            <!-- Occupied Resources Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Occupied Resources ({{ occupied.length }})</CardTitle>
                    <CardDescription>Resources currently assigned to projects or tasks.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="occupied.length === 0" class="text-muted-foreground">No resources are currently occupied.</div>
                    <ul v-else class="space-y-4">
                        <li
                            v-for="resource in occupied"
                            :key="resource.resource_id"
                            class="flex flex-col gap-2 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="font-semibold">{{ resource.name }}</p>
                                <p v-if="resource.active_assignment?.project" class="text-sm text-muted-foreground">
                                    Assigned to: {{ resource.active_assignment.project.project_name }} ({{
                                        resource.active_assignment.project.is_task ? 'Task' : 'Project'
                                    }})
                                </p>
                                <p v-else class="text-sm italic text-muted-foreground">Assignment details unavailable.</p>
                            </div>
                            <Button
                                v-if="resource.active_assignment"
                                size="sm"
                                variant="outline"
                                @click="markComplete(resource.active_assignment.assignment_id)"
                            >
                                Mark as Complete
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <!-- Available Resources Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Available Resources ({{ available.length }})</CardTitle>
                    <CardDescription>Resources ready for new assignments.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="available.length === 0" class="text-muted-foreground">No resources are currently available.</div>
                    <ul v-else class="space-y-2">
                        <li v-for="resource in available" :key="resource.resource_id" class="rounded-md border p-3">
                            {{ resource.name }}
                            <span class="text-sm text-muted-foreground"> ({{ resource.email }})</span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
