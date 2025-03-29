<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, PlusCircle, Trash2 } from 'lucide-vue-next'; // Icons

// Define structure for pagination links
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Define structure for Resource data from controller
interface Resource {
    resource_id: number;
    name: string;
    email: string;
    skills: string | null;
    created_at: string;
    // Add active assignment if needed later
}

// Define structure for the paginated response
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

// Define props passed from ResourceController->index
const props = defineProps<{
    resources: PaginatedResources;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Resources', href: route('resources.index') },
];

// Function to handle resource deletion
function deleteResource(resourceId: number, resourceName: string) {
    // Add check: Cannot delete resource if currently assigned?
    // This logic should ideally be in the backend controller (ResourceController@destroy)
    // For now, just confirm deletion.
    if (confirm(`Are you sure you want to delete "${resourceName}"? This action cannot be undone.`)) {
        router.delete(route('resources.destroy', resourceId), {
            preserveScroll: true,
            // onSuccess: () => { // Optional: Show notification },
            // onError: (errors) => { // Optional: Show error notification, e.g., if deletion is restricted },
        });
    }
}
</script>

<template>
    <Head title="Resources" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Manage Resources</h1>
                <Link :href="route('resources.create')">
                    <Button>
                        <PlusCircle class="mr-2 h-4 w-4" />
                        Add Resource
                    </Button>
                </Link>
            </div>

            <Card>
                <CardContent class="pt-6">
                    <Table v-if="resources.data.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Skills</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="resource in resources.data" :key="resource.resource_id">
                                <TableCell class="font-medium">{{ resource.name }}</TableCell>
                                <TableCell>{{ resource.email }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ resource.skills || '-' }}</TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('resources.edit', resource.resource_id)" class="mr-2">
                                        <Button variant="ghost" size="icon">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="icon" @click="deleteResource(resource.resource_id, resource.name)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="py-8 text-center text-muted-foreground">No resources found.</div>
                </CardContent>
                <!-- Pagination -->
                <div v-if="resources.links.length > 3" class="flex items-center justify-center border-t p-4">
                    <div class="-mb-1 flex flex-wrap">
                        <template v-for="(link, key) in resources.links" :key="key">
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
