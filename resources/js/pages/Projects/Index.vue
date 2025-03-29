<script setup lang="ts">
import { Badge } from '@/components/ui/badge'; // For Task/Project badge
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'; // For filtering
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, Edit, PlusCircle, Trash2 } from 'lucide-vue-next'; // Icons
import { computed, ref } from 'vue';

// Define structure for pagination links
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Define structure for Project data from controller
interface Project {
    project_id: number;
    project_name: string;
    start_date: string; // Assuming string format
    time_estimate_hours: number;
    is_task: boolean;
    created_at: string;
    deadline?: string | null; // Add optional deadline
    status: string; // Add status field
    // Add assignments if needed later
}

// Define structure for the paginated response
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

// Define props passed from ProjectController->index
const props = defineProps<{
    projects: PaginatedProjects;
    filters: {
        type: 'project' | 'task' | null;
        status: string; // Add status filter prop
    };
}>();

const currentFilter = ref<'project' | 'task' | null>(props.filters.type);
const currentStatusFilter = ref<string>(props.filters.status); // Add state for status filter
const pageTitle = computed(() => (currentFilter.value === 'task' ? 'Tasks' : 'Projects'));

// Define statuses for tabs
const statuses = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'archived', label: 'Archived' },
    { value: 'on_pause', label: 'On Pause' }, // Use snake_case for consistency if needed
];

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: route('dashboard') },
    { title: pageTitle.value, href: route('projects.index', { type: currentFilter.value }) },
]);

// Function to handle filter change
function applyFilter(type: 'project' | 'task' | null) {
    currentFilter.value = type;
    router.get(
        route('projects.index'),
        { type: type },
        {
            preserveState: true, // Keep local component state
            preserveScroll: true,
            replace: true, // Replace history entry instead of pushing
        },
    );
}

// Function to handle status filter change (tab click)
function applyStatusFilter(status: string) {
    currentStatusFilter.value = status;
    router.get(
        route('projects.index'),
        {
            type: currentFilter.value, // Keep existing type filter
            status: status === 'all' ? null : status, // Send null if 'all' is selected, otherwise send the status value
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

// Function to handle project deletion
function deleteProject(projectId: number, projectName: string) {
    if (confirm(`Are you sure you want to delete "${projectName}"? This action cannot be undone.`)) {
        router.delete(route('projects.destroy', projectId), {
            preserveScroll: true,
            // onSuccess: () => { // Optional: Show notification },
            // onError: () => { // Optional: Show error notification },
        });
    }
}

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

// Function to calculate estimated end date - NOW RETURNS Date | null
function calculateEstimatedEndDateObject(startDateString: string | null | undefined, estimatedHours: number): Date | null {
    // Ensure hours is a positive number
    if (!startDateString || !estimatedHours || estimatedHours <= 0) return null;

    try {
        const startDate = new Date(startDateString);
        // Check if startDate is valid
        if (isNaN(startDate.getTime())) return null; // Return null for invalid date

        // Adjust start date if it falls on a weekend
        let startDayOfWeek = startDate.getDay();
        while (startDayOfWeek === 0 || startDayOfWeek === 6) {
            // If Sunday (0) or Saturday (6)
            startDate.setDate(startDate.getDate() + 1);
            startDayOfWeek = startDate.getDay();
        }

        const workHoursPerDay = 7;
        // Calculate full work days needed. Use Math.ceil to round up.
        // Subtract 1 because the start date counts as the first day.
        const workDaysNeeded = Math.ceil(estimatedHours / workHoursPerDay) - 1;

        // If it takes less than a full day's work (<= 7 hours), the end date is the start date (after weekend adjustment)
        if (workDaysNeeded < 0) {
            return startDate; // Return the adjusted start date object
        }

        const endDate = addWorkDays(startDate, workDaysNeeded);
        return endDate; // Return the calculated end date object
    } catch (e) {
        console.error('Error calculating end date:', e);
        return null; // Return null on error
    }
}

// New function to format the Date object from calculateEstimatedEndDateObject
function getFormattedEstimatedEndDate(project: Project): string {
    const endDate = calculateEstimatedEndDateObject(project.start_date, project.time_estimate_hours);
    // Use the existing formatDate, but ensure we pass a string in the expected format or null
    return endDate ? formatDate(endDate.toISOString().split('T')[0]) : 'N/A';
}

// --- Row Styling Logic ---
function getRowClass(project: Project): string {
    const estimatedEndDate = calculateEstimatedEndDateObject(project.start_date, project.time_estimate_hours);
    const deadline = project.deadline ? new Date(project.deadline) : null;

    // Only apply styling if both dates are valid and project is not completed/archived
    if (!estimatedEndDate || !deadline || isNaN(deadline.getTime()) || ['completed', 'archived'].includes(project.status)) {
        return '';
    }

    // Clear time part for accurate day comparison
    estimatedEndDate.setHours(0, 0, 0, 0);
    deadline.setHours(0, 0, 0, 0);

    const timeDiff = deadline.getTime() - estimatedEndDate.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)); // Difference in days

    if (daysDiff < 0) {
        return 'bg-red-50 dark:bg-red-900/30'; // Past deadline
    } else if (daysDiff <= 3) {
        return 'bg-yellow-50 dark:bg-yellow-900/30'; // Nearing deadline (3 days or less)
    }

    return ''; // Default: no special background
}
// --- End Row Styling Logic ---
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
                <div class="flex items-center gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                Filter: {{ currentFilter ? (currentFilter === 'task' ? 'Tasks Only' : 'Projects Only') : 'All' }}
                                <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="applyFilter(null)">Show All</DropdownMenuItem>
                            <DropdownMenuItem @click="applyFilter('project')">Projects Only</DropdownMenuItem>
                            <DropdownMenuItem @click="applyFilter('task')">Tasks Only</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Link :href="route('projects.create')">
                        <Button>
                            <PlusCircle class="mr-2 h-4 w-4" />
                            Create New
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Status Filter Tabs -->
            <div class="mb-4 border-b border-border">
                <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                    <button
                        v-for="status in statuses"
                        :key="status.value"
                        @click="applyStatusFilter(status.value)"
                        :class="[
                            status.value === currentStatusFilter || (status.value === 'active' && !props.filters.status) // Highlight 'active' if no status filter is explicitly set in props
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors duration-150 ease-in-out focus:outline-none',
                        ]"
                        :aria-current="status.value === currentStatusFilter ? 'page' : undefined"
                    >
                        {{ status.label }}
                    </button>
                </nav>
            </div>

            <Card>
                <CardContent class="pt-6">
                    <Table v-if="projects.data.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Start Date</TableHead>
                                <TableHead>Est. Hours</TableHead>
                                <TableHead>Est. End Date</TableHead>
                                <TableHead>Deadline</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="project in projects.data" :key="project.project_id" :class="getRowClass(project)">
                                <TableCell class="font-medium">{{ project.project_name }}</TableCell>
                                <TableCell>
                                    <Badge :variant="project.is_task ? 'secondary' : 'default'">
                                        {{ project.is_task ? 'Task' : 'Project' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ formatDate(project.start_date) }}</TableCell>
                                <TableCell>{{ project.time_estimate_hours }}</TableCell>
                                <TableCell>{{ getFormattedEstimatedEndDate(project) }}</TableCell>
                                <TableCell>{{ formatDate(project.deadline) }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ project.status.replace('_', ' ') }}</Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('projects.edit', project.project_id)" class="mr-2">
                                        <Button variant="ghost" size="icon">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="icon" @click="deleteProject(project.project_id, project.project_name)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="py-8 text-center text-muted-foreground">No {{ pageTitle.toLowerCase() }} found.</div>
                </CardContent>
                <!-- Pagination -->
                <div v-if="projects.links.length > 3" class="flex items-center justify-center border-t p-4">
                    <div class="-mb-1 flex flex-wrap">
                        <template v-for="(link, key) in projects.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="mb-1 mr-1 rounded border px-3 py-2 text-sm leading-4 text-muted-foreground"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                class="mb-1 mr-1 rounded border px-3 py-2 text-sm leading-4 hover:bg-secondary focus:border-primary focus:text-primary"
                                :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                                :href="link.url"
                                v-html="link.label"
                                preserve-scroll
                                preserve-state
                            />
                        </template>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
