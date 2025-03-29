import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

// Define the structure of the user object based on Laravel's User model
// Adjust properties as needed based on what's shared via HandleInertiaRequests
interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    // Add other relevant user properties if shared
}

export const useAuthStore = defineStore('auth', () => {
    // Use Inertia's shared page props to initialize user state
    // Note: Accessing page props directly here might be tricky initially.
    // It's often better to initialize/update this from the AppLayout or main entry point (app.ts)
    // where page props are readily available.
    // For now, we define the structure and refs.
    const user = ref<User | null>(null);

    const isAuthenticated = computed(() => !!user.value);
    const userName = computed(() => user.value?.name ?? 'Guest');
    const userId = computed(() => user.value?.id);

    // Function to set/update the user data (e.g., called from AppLayout)
    function setUser(userData: User | null) {
        user.value = userData;
    }

    return {
        user,
        isAuthenticated,
        userName,
        userId,
        setUser,
    };
});
