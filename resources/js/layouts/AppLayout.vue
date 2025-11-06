<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const migrationPending = computed(() => page.props.app_status.migrations_pending);

const form = useForm({});

const submit = () => {
    form.post(route('system.run-migrations'));
};

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (msg) {
            toast.success(msg);
        }
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (msg) => {
        if (msg) toast.error(msg);
    },
    { immediate: true },
);
</script>

<template>
    <template v-if="migrationPending">
        <div class="flex min-h-screen items-center justify-center bg-gray-100">
            <div class="max-w-md space-y-6 rounded-lg bg-white p-8 text-center shadow-xl">
                <h1 class="text-2xl font-bold text-gray-800">Database Setup Required</h1>
                <p class="text-gray-600">The application's database has not been set up yet. Please run the migrations to continue.</p>

                <div v-if="$page.props.errors.migration_error" class="rounded-lg bg-red-100 p-4 text-sm text-red-700" role="alert">
                    <span class="font-medium">Error!</span>
                    {{ $page.props.errors.migration_error }}
                </div>

                <Button @click="submit" :disabled="form.processing" class="w-full">
                    <span v-if="form.processing">Running Migrations...</span>
                    <span v-else>Run Migrations Now</span>
                </Button>
            </div>
        </div>
    </template>

    <template v-else>
        <AppLayout :breadcrumbs="breadcrumbs">
            <div class="min-h-screen bg-gray-50">
                <slot />
            </div>
        </AppLayout>
    </template>
    <Toaster rich-colors position="top-right" />
</template>
