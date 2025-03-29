import { defineStore } from 'pinia';
import { ref } from 'vue';

// Define the structure for a Resource on the dashboard
// It might include the active assignment details if occupied
interface DashboardResource {
    resource_id: number;
    name: string;
    email: string;
    skills: string | null;
    // Include assignment details if the resource is occupied
    active_assignment?: {
        assignment_id: number;
        project_id: number;
        assignment_start_date: string;
        assignment_end_date: string | null;
        // Include project details if eager loaded
        project?: {
            project_id: number;
            project_name: string;
            is_task: boolean;
        };
    } | null;
}

export const useDashboardStore = defineStore('dashboard', () => {
    // State refs
    const availableResources = ref<DashboardResource[]>([]);
    const occupiedResources = ref<DashboardResource[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Actions
    function setDashboardData(data: { availableResources: DashboardResource[]; occupiedResources: DashboardResource[] }) {
        availableResources.value = data.availableResources;
        occupiedResources.value = data.occupiedResources;
        isLoading.value = false;
        error.value = null;
    }

    function setLoading(loadingState: boolean) {
        isLoading.value = loadingState;
    }

    function setError(errorMessage: string | null) {
        error.value = errorMessage;
        isLoading.value = false;
    }

    // Action to potentially move a resource from occupied to available after completion
    // This might be triggered after the 'complete' action succeeds
    function markResourceAsAvailable(resourceId: number) {
        const index = occupiedResources.value.findIndex((r) => r.resource_id === resourceId);
        if (index !== -1) {
            const [resource] = occupiedResources.value.splice(index, 1);
            resource.active_assignment = null; // Clear assignment details
            availableResources.value.push(resource);
            // Optional: Sort availableResources again
            availableResources.value.sort((a, b) => a.name.localeCompare(b.name));
        }
    }

    // Action to move a resource from available to occupied after assignment
    function markResourceAsOccupied(resourceId: number, assignmentDetails: any) {
        const index = availableResources.value.findIndex((r) => r.resource_id === resourceId);
        if (index !== -1) {
            const [resource] = availableResources.value.splice(index, 1);
            resource.active_assignment = assignmentDetails; // Add assignment details
            occupiedResources.value.push(resource);
            // Optional: Sort occupiedResources again
            occupiedResources.value.sort((a, b) => a.name.localeCompare(b.name));
        }
    }

    return {
        availableResources,
        occupiedResources,
        isLoading,
        error,
        setDashboardData,
        setLoading,
        setError,
        markResourceAsAvailable,
        markResourceAsOccupied,
    };
});
