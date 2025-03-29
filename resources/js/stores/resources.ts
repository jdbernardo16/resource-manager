import { defineStore } from 'pinia';
import { ref } from 'vue';

// Define the structure of a Resource based on the backend model/API response
interface Resource {
    resource_id: number;
    name: string;
    email: string;
    skills: string | null;
    created_at: string;
    updated_at: string;
    // Add active assignment relation if needed/included later
    active_assignment?: any; // Define more strictly later
}

// Re-use pagination interfaces from projects.ts or define separately if needed
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedResources {
    current_page: number;
    data: Resource[];
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

export const useResourcesStore = defineStore('resources', () => {
    // State refs
    const resources = ref<Resource[]>([]);
    const paginationData = ref<Omit<PaginatedResources, 'data'> | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Actions
    function setResourcesData(paginatedResponse: PaginatedResources) {
        resources.value = paginatedResponse.data;
        const { data, ...meta } = paginatedResponse;
        paginationData.value = meta;
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

    // Add actions for CRUD operations later (e.g., using Axios)
    // async function fetchResources(page = 1) { ... }
    // async function createResource(resourceData) { ... }
    // async function updateResource(resourceId, resourceData) { ... }
    // async function deleteResource(resourceId) { ... }

    return {
        resources,
        paginationData,
        isLoading,
        error,
        setResourcesData,
        setLoading,
        setError,
    };
});
