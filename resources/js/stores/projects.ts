import { defineStore } from 'pinia';
import { ref } from 'vue';

// Define the structure of a Project based on the backend model/API response
// Adjust based on what ProjectController->index returns via Inertia props
interface Project {
    project_id: number;
    project_name: string;
    project_description: string | null;
    start_date: string; // Dates are often strings in JSON
    time_estimate_hours: number;
    is_task: boolean;
    created_at: string;
    updated_at: string;
    // Add assignments relation if needed/included in the future
    assignments?: any[]; // Define more strictly later
}

// Define the structure for pagination links if using Laravel pagination
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Define the structure for the paginated response from Laravel
interface PaginatedProjects {
    current_page: number;
    data: Project[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export const useProjectsStore = defineStore('projects', () => {
    // State refs
    const projects = ref<Project[]>([]);
    const paginationData = ref<Omit<PaginatedProjects, 'data'> | null>(null); // Store pagination meta, excluding data array
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const currentFilter = ref<'project' | 'task' | null>(null); // To store the current filter type

    // Actions
    // Function to update the store state from Inertia props (called from the page component)
    function setProjectsData(paginatedResponse: PaginatedProjects) {
        projects.value = paginatedResponse.data;
        const { data, ...meta } = paginatedResponse; // Separate data from meta
        paginationData.value = meta;
        isLoading.value = false;
        error.value = null;
    }

    function setLoading(loadingState: boolean) {
        isLoading.value = loadingState;
    }

    function setError(errorMessage: string | null) {
        error.value = errorMessage;
        isLoading.value = false; // Stop loading if error occurs
    }

    function setFilter(type: 'project' | 'task' | null) {
        currentFilter.value = type;
        // Optionally trigger a fetch here if not using Inertia props directly
    }

    // Add actions for CRUD operations later (e.g., using Axios)
    // async function fetchProjects(page = 1, type: string | null = null) { ... }
    // async function createProject(projectData) { ... }
    // async function updateProject(projectId, projectData) { ... }
    // async function deleteProject(projectId) { ... }

    return {
        projects,
        paginationData,
        isLoading,
        error,
        currentFilter,
        setProjectsData,
        setLoading,
        setError,
        setFilter,
    };
});
