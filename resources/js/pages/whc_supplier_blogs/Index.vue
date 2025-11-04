<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { WhcSupplierBlog } from '@/types/whc_supplier_blog';
import { Head, router } from '@inertiajs/vue3';
import { createColumnHelper, FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { Eye, Pencil, RotateCw, SendHorizontal, ToggleLeft, ToggleRight, View } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { toast } from 'vue-sonner';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { format, parseISO } from 'date-fns';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    whcSupplierBlogs: WhcSupplierBlog[];
    fileUrlPrefix: string | null;
}>();

const columnHelper = createColumnHelper<WhcSupplierBlog>();

const stripHtml = (html: string | null | undefined): string => {
    if (!html) return '';
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    return tempDiv.textContent || tempDiv.innerText || '';
};

const columns = [
    columnHelper.accessor('offer_no', {
        header: 'Offer',
        size: 50,
        cell: (ctx) => {
            return ctx.getValue();
        },
    }),
    columnHelper.accessor('title', {
        header: 'Title',
        size: 250,
        cell: (ctx) => {
            return h(
                'div',
                {
                    class: 'truncate text-[13px]',
                    title: ctx.getValue(),
                },
                ctx.getValue(),
            );
        },
    }),
    columnHelper.accessor('description', {
        header: 'Description Preview',
        size: 400,
        cell: (ctx) => {
            const htmlDescription = ctx.getValue();

            const plainText = stripHtml(htmlDescription);

            if (!plainText) {
                return h('span', { class: 'text-muted-foreground' }, '—');
            }
            if (plainText.length <= 50) {
                return plainText;
            }
            return `${plainText.substring(0, 85)}...`;
        },
    }),
    columnHelper.accessor('status', {
        size: 70,
        header: 'Status',
        cell: (ctx) => {
            const s = ctx.getValue();
            const baseClass = 'rounded-md font-medium text-[11px]';
            switch (s) {
                case 1:
                    return h(Badge, { class: `${baseClass} bg-green-100 text-green-800` }, { default: () => 'Active' });
                case 2:
                    return h(Badge, { class: `${baseClass} bg-gray-200 text-black` }, { default: () => 'Not Active' });
            }
        },
    }),
    columnHelper.display({
        id: 'actions',
        size: 50,
        header: () => 'Actions',
        cell: ({ row }) => {
            const r = row.original;
            const magentoBlog = r.whc_supplier_offer_blog_magento;
            const canViewMagentoBlog = r.whc_supplier_offer_blog_magento && r.whc_supplier_offer_blog_magento.status === 1;

            return h('div', { class: 'flex items-center gap-1' }, [
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        class: 'cursor-pointer',
                        onClick: () => openBlogModal(r),
                        title: 'View Details',
                    },
                    { default: () => h(Eye, { class: 'h-4 w-4' }) },
                ),

                canViewMagentoBlog &&
                    h(
                        Button,
                        {
                            as: 'a',
                            href: `https://germanfoodcorner.de/restanten/${magentoBlog.url_key}`,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            variant: 'ghost',
                            size: 'icon',
                            class: 'cursor-pointer',
                            title: 'View on Storefront',
                        },
                        { default: () => h(View, { class: 'h-4 w-4 text-blue-500' }) },
                    ),

                !magentoBlog &&
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'icon',
                            class: 'cursor-pointer',
                            onClick: () => openConfirmationDialog(r),
                            title: 'Create in Magento',
                        },
                        { default: () => h(SendHorizontal, { class: 'h-4 w-4 ' }) },
                    ),
            ]);
        },
    }),
    columnHelper.display({
        id: 'in_magento',
        size: 50,
        header: () => 'Created',
        cell: ({ row }) => {
            if (row.original.whc_supplier_offer_blog_magento) {
                return h(Badge, { class: 'bg-green-100 text-green-800 text-[11px]' }, { default: () => 'Yes' });
            } else {
                return h(Badge, { class: 'bg-red-200 text-black text-[11px]' }, { default: () => 'No' });
            }
        },
    }),
    columnHelper.display({
        id: 'toggle_magento',
        size: 50,
        header: () => 'Toggle',
        cell: ({ row }) => {
            const magentoBlog = row.original.whc_supplier_offer_blog_magento;

            if (magentoBlog) {
                const isInactive = magentoBlog.status === 0;

                return h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        class: 'cursor-pointer',
                        onClick: () => openToggleConfirmationDialog(row.original),
                        title: isInactive ? 'Activate in Magento' : 'Deactivate in Magento',
                    },
                    {
                        default: () =>
                            h(isInactive ? ToggleLeft : ToggleRight, { class: `h-4 w-4 ${isInactive ? 'text-red-500' : 'text-green-500'}` }),
                    },
                );
            }
        },
    }),

    columnHelper.display({
        id: 'update_magento',
        size: 50,
        header: () => 'Update',
        cell: ({ row }) => {
            const magentoBlog = row.original.whc_supplier_offer_blog_magento;

            if (magentoBlog) {
                return h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        class: 'cursor-pointer',
                        onClick: () => openUpdateConfirmationDialog(row.original),
                        title: 'Update blog in Magento',
                    },
                    { default: () => h(Pencil, { class: 'h-4 w-4' }) },
                );
            }
        },
    }),
];

const createBlogInMagento = (blog: WhcSupplierBlog) => {
    router.post(
        route('whc.supplier.blog.create-in-magento', blog.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success('Blog created in Magento!', {
                    description: 'The blog has been successfully created in Magento.',
                }),
            onError: (err) =>
                toast.error('Error', {
                    description: err.service ?? 'Something went wrong',
                }),
        },
    );
};

const selectedBlog = ref<WhcSupplierBlog | null>(null);

const imageUrl = computed(() => {
    if (selectedBlog.value?.has_file && selectedBlog.value.file_path && props.fileUrlPrefix) {
        const params = new URLSearchParams({
            path: selectedBlog.value.file_path,
        });

        return `${props.fileUrlPrefix}?${params.toString()}`;
    }
    return null;
});

const isModalOpen = ref(false);

const openBlogModal = (blog: WhcSupplierBlog) => {
    selectedBlog.value = blog;
    isModalOpen.value = true;
};

const formattedStatus = computed(() => {
    if (!selectedBlog.value) return 'N/A';
    switch (selectedBlog.value.status) {
        case 1:
            return 'Active';
        case 2:
            return 'Not Active';
        default:
            return 'Unknown';
    }
});

const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) {
        return 'N/A';
    }

    try {
        const date = parseISO(dateString);

        return format(date, "MMM d, yyyy 'at' h:mm a");
    } catch (error) {
        console.error('Failed to parse date:', dateString, error);
        return 'Invalid Date';
    }
};

const table = useVueTable({
    get data() {
        return props.whcSupplierBlogs;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
});

const isSyncing = ref(false);

const syncWhcSupplierOfferBlogTable = () => {
    router.post(
        route('whc.supplier.blog.sync'),
        {},
        {
            preserveScroll: true,
            onStart: () => (isSyncing.value = true),
            onFinish: () => (isSyncing.value = false),
            onSuccess: () =>
                toast.success('Sync complete!', {
                    description: 'Whc supplier blogs synced successfully.',
                }),
            onError: (err) =>
                toast.error('Error', {
                    description: err.service ?? 'Something went wrong',
                }),
        },
    );
};

const confirmCreate = ref(false);
const blogToCreate = ref<WhcSupplierBlog | null>(null);

const openConfirmationDialog = (blog: WhcSupplierBlog) => {
    blogToCreate.value = blog;
    confirmCreate.value = true;
};

const handleConfirmCreate = () => {
    if (blogToCreate.value) {
        createBlogInMagento(blogToCreate.value);
    }
};

const isToggleConfirmOpen = ref(false);
const blogToToggle = ref<WhcSupplierBlog | null>(null);

const isActivatingAction = computed(() => {
    return blogToToggle.value?.whc_supplier_offer_blog_magento?.status === 0;
});

const toggleDialogTitle = computed(() => {
    return isActivatingAction.value ? 'Activate this Blog in Magento?' : 'Deactivate this Blog in Magento?';
});

const toggleDialogDescription = computed(() => {
    return isActivatingAction.value
        ? 'This will make the blog post visible in Magento. Are you sure?'
        : 'This will hide the blog post in Magento. Are you sure?';
});

const openToggleConfirmationDialog = (blog: WhcSupplierBlog) => {
    blogToToggle.value = blog;
    isToggleConfirmOpen.value = true;
};

const toggleMagentoStatus = (blog: WhcSupplierBlog) => {
    const isActivating = blog.whc_supplier_offer_blog_magento?.status === 0;

    const routeName = isActivating ? 'whc.supplier.blog.activate-in-magento' : 'whc.supplier.blog.deactivate-in-magento';

    const successMessage = isActivating ? 'Blog activated in Magento!' : 'Blog deactivated in Magento!';

    router.post(
        route(routeName, blog.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(successMessage),
            onError: (err) =>
                toast.error('Error', {
                    description: err.service ?? 'Something went wrong',
                }),
        },
    );
};

const handleConfirmToggle = () => {
    if (blogToToggle.value) {
        toggleMagentoStatus(blogToToggle.value);
    }
};

const isUpdateConfirmOpen = ref(false);
const blogToUpdate = ref<WhcSupplierBlog | null>(null);

const openUpdateConfirmationDialog = (blog: WhcSupplierBlog) => {
    blogToUpdate.value = blog;
    isUpdateConfirmOpen.value = true;
};

const updateBlogInMagento = (blog: WhcSupplierBlog) => {
    router.post(
        route('whc.supplier.blog.update-in-magento', blog.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success('Blog updated in Magento!', {
                    description: 'The blog has been successfully updated.',
                }),
            onError: (err) =>
                toast.error('Error', {
                    description: err.service ?? 'Something went wrong while updating.',
                }),
        },
    );
};

const handleConfirmUpdate = () => {
    if (blogToUpdate.value) {
        updateBlogInMagento(blogToUpdate.value);
    }
};
</script>

<template>
    <Head title="Supplier Blogs" />

    <div class="m-4 flex items-center justify-between">
        <Button @click="syncWhcSupplierOfferBlogTable" :disabled="isSyncing">
            <RotateCw v-if="isSyncing" class="mr-3 -ml-1 h-5 w-5 animate-spin" />
            <span>{{ isSyncing ? 'Syncing...' : 'Sync Whc Supplier Blogs' }}</span>
        </Button>
    </div>

    <Card class="m-4 rounded-md pt-0 pb-2">
        <Table
            class="w-full table-fixed [&_td]:px-2 [&_td]:py-0.5 [&_td]:text-left [&_th]:h-auto [&_th]:border-b-2 [&_th]:bg-gray-50 [&_th]:px-2 [&_th]:py-2 [&_th]:text-left [&_th]:text-sm [&_th]:font-medium [&_th]:text-muted-foreground [&_tr]:text-sm"
        >
            <TableHeader>
                <TableRow v-for="hg in table.getHeaderGroups()" :key="hg.id">
                    <TableHead v-for="header in hg.headers" :key="header.id" :colspan="header.colSpan" :style="{ width: `${header.getSize()}px` }">
                        <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                    </TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <template v-if="table.getRowModel().rows.length">
                    <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" :style="{ width: `${cell.column.getSize()}px` }">
                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </TableCell>
                    </TableRow>
                </template>

                <template v-else>
                    <TableRow>
                        <TableCell :colspan="columns.length" class="h-24 text-center"> No blogs.</TableCell>
                    </TableRow>
                </template>
            </TableBody>
        </Table>
    </Card>

    <Dialog v-model:open="isModalOpen">
        <DialogContent class="flex max-h-[90vh] w-full max-w-3xl flex-col sm:max-w-7xl">
            <template v-if="selectedBlog">
                <DialogHeader>
                    <DialogTitle class="truncate pr-8">{{ selectedBlog.title }}</DialogTitle>
                    <DialogDescription> Details for offer number: {{ selectedBlog.offer_no }} </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 overflow-y-auto py-4 text-sm">
                    <div class="mb-4 grid grid-cols-3 gap-4 border-b pb-4">
                        <div>
                            <span class="font-semibold text-muted-foreground">Status</span>
                            <p>{{ formattedStatus }}</p>
                        </div>
                        <div>
                            <span class="font-semibold text-muted-foreground">Created (Source)</span>
                            <p>{{ formatDate(selectedBlog.created_at_whc) }}</p>
                        </div>
                        <div>
                            <span class="font-semibold text-muted-foreground">Updated (Source)</span>
                            <p>{{ formatDate(selectedBlog.updated_at_whc) }}</p>
                        </div>
                    </div>
                    <div v-if="imageUrl" class="border-b pb-4">
                        <span class="font-semibold text-muted-foreground">Attached File</span>
                        <div class="mt-2 rounded-md border p-2">
                            <a :href="imageUrl" target="_blank" rel="noopener noreferrer">
                                <img :src="imageUrl" :alt="selectedBlog.file_name ?? 'Blog Image'" class="max-h-80 w-auto rounded-md" />
                            </a>
                            <p class="mt-2 text-center text-xs text-muted-foreground">
                                {{ selectedBlog.file_name }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <span class="font-semibold text-muted-foreground">Description</span>
                        <div v-html="selectedBlog.description" class="prose mt-2 max-w-none dark:prose-invert"></div>
                    </div>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"> Close </Button>
                    </DialogClose>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
    <AlertDialog :open="confirmCreate" @update:open="(v) => (confirmCreate = v)">
        <AlertDialogContent class="w-[90vw] !max-w-md sm:w-auto">
            <AlertDialogHeader>
                <AlertDialogTitle>Create this blog in magento?</AlertDialogTitle>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="handleConfirmCreate">Create</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <AlertDialog :open="isToggleConfirmOpen" @update:open="(v) => (isToggleConfirmOpen = v)">
        <AlertDialogContent class="w-[90vw] !max-w-md sm:w-auto">
            <AlertDialogHeader>
                <AlertDialogTitle>{{ toggleDialogTitle }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ toggleDialogDescription }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="handleConfirmToggle" :class="['cursor-pointer', !isActivatingAction && 'bg-red-600 hover:bg-red-700']">
                    Confirm
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <AlertDialog :open="isUpdateConfirmOpen" @update:open="(v) => (isUpdateConfirmOpen = v)">
        <AlertDialogContent class="w-[90vw] !max-w-md sm:w-auto">
            <AlertDialogHeader>
                <AlertDialogTitle>Update this blog in Magento?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will overwrite the existing content in Magento with the latest data from the source. This action cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="handleConfirmUpdate">Update</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
