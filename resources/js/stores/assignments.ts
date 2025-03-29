import { defineStore } from 'pinia';
import { ref } from 'vue';

// Define the structure of a ProjectAssignment
interface ProjectAssignment {
    assignment_id: number;
    project_id: number;
    resource_id: number;
    assignment_start_date: string;
    assignment_end_date: string | null;
    assignment_is_active: boolean;
    created_at: string;
    updated_at: string;
    // Include project/resource details if needed/loaded
    project?: any; // Define more strictly later
    resource?: any; // Define more strictly later
}

export const useAssignmentsStore = defineStore('assignments', () => {
    // State refs
    // This store might hold all assignments, or perhaps assignments related to a specific view/context.
    // For now, keep it simple.
    const assignments = ref<ProjectAssignment[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Actions
    function setAssignments(assignmentData: ProjectAssignment[]) {
        assignments.value = assignmentData;
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

    // Action to mark an assignment as complete in the local state
    function markAssignmentComplete(assignmentId: number) {
        const assignment = assignments.value.find((a) => a.assignment_id === assignmentId);
        if (assignment) {
            assignment.assignment_is_active = false;
            // Optionally set end date if not already set
            // assignment.assignment_end_date = new Date().toISOString().split('T')[0];
        }
    }

    // Add other actions as needed (e.g., fetching assignments by resource/project)

    return {
        assignments,
        isLoading,
        error,
        setAssignments,
        setLoading,
        setError,
        markAssignmentComplete,
    };
});
