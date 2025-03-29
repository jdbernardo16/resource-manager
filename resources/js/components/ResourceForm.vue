<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

// Define the structure for resource data (optional, for editing)
interface ResourceData {
    resource_id?: number; // Optional for create
    name: string;
    email: string;
    skills: string | null;
}

const props = defineProps<{
    resource?: ResourceData | null; // Resource data for editing, null for create
    submitButtonText?: string;
    formTitle?: string;
    formDescription?: string;
}>();

const emit = defineEmits<{
    (e: 'submit', formData: typeof form): void;
    (e: 'cancel'): void;
}>();

// Initialize form state using useForm
const form = useForm({
    name: props.resource?.name ?? '',
    email: props.resource?.email ?? '',
    skills: props.resource?.skills ?? '',
    _method: props.resource ? 'PUT' : 'POST', // Set method for update or store
});

// Handle form submission
function submitForm() {
    emit('submit', form);
}

// Handle cancellation
function cancelForm() {
    emit('cancel');
}

// Computed property for the title
const cardTitle = computed(() => props.formTitle ?? (props.resource ? 'Edit Resource' : 'Create New Resource'));
const cardDescription = computed(
    () => props.formDescription ?? (props.resource ? 'Update the resource details below.' : 'Fill in the resource details below.'),
);
const submitText = computed(() => props.submitButtonText ?? (props.resource ? 'Update' : 'Create'));

// Watch for resource prop changes if the component might be reused without unmounting
watch(
    () => props.resource,
    (newResource) => {
        form.reset(); // Reset form state and errors
        form.name = newResource?.name ?? '';
        form.email = newResource?.email ?? '';
        form.skills = newResource?.skills ?? '';
        form._method = newResource ? 'PUT' : 'POST';
    },
    { deep: true },
);
</script>

<template>
    <Card>
        <form @submit.prevent="submitForm">
            <CardHeader>
                <CardTitle>{{ cardTitle }}</CardTitle>
                <CardDescription>{{ cardDescription }}</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6">
                <!-- Name -->
                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" v-model="form.email" required />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Skills -->
                <div class="space-y-2">
                    <Label for="skills">Skills</Label>
                    <Textarea id="skills" v-model="form.skills" placeholder="Optional: List relevant skills (e.g., PHP, Vue, Design)" />
                    <InputError :message="form.errors.skills" />
                </div>
            </CardContent>
            <CardFooter class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="cancelForm" :disabled="form.processing">Cancel</Button>
                <Button type="submit" :disabled="form.processing">
                    <span v-if="form.processing">Processing...</span>
                    <span v-else>{{ submitText }}</span>
                </Button>
            </CardFooter>
        </form>
    </Card>
</template>
