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
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { WhcSupplierBlog } from '@/types/whc_supplier_blog';
import { Head, router } from '@inertiajs/vue3';
import { createColumnHelper, FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { differenceInHours, format, isToday, isYesterday, parseISO } from 'date-fns';
import { Check, ChevronsUpDown, DollarSign, Eye, Pencil, RotateCw, SendHorizontal, ToggleLeft, ToggleRight, View, X } from 'lucide-vue-next';
import { computed, h, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import { Command, CommandEmpty, CommandGroup, CommandItem, CommandList } from '@/components/ui/command';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    whcSupplierBlogs: WhcSupplierBlog[];
    fileUrlPrefix: string | null;
    filters?: {
        whc_not_active_but_magento_active: boolean;
        whc_active_but_whc_not_approved: boolean;
        whc_active_and_whc_approved: boolean;
        statuses: number[];
    };
    filterOptions: {
        statuses: number[];
    };
    lastSynced: string | null;
    whcSupplierUrl: string;
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
            const offerNo = ctx.getValue();
            const whcSupplierUrl = props.whcSupplierUrl;

            return h(
                'a',
                {
                    href: whcSupplierUrl + '/offers/list?offerno=' + offerNo,
                    target: '_blank',
                    rel: 'noopener noreferrer',
                    class: 'text-blue-600 cursor-pointer',
                },
                offerNo,
            );
        },
    }),
    columnHelper.accessor('offer_title', {
        header: 'Offer Title',
        size: 200,
        cell: (ctx) => {
            return h(
                'div',
                {
                    class: 'truncate',
                    title: ctx.getValue(),
                },
                ctx.getValue(),
            );
        },
    }),
    columnHelper.accessor('supplier', {
        header: 'Supplier',
        size: 150,
        cell: (ctx) => {
            return h(
                'div',
                {
                    class: 'truncate',
                    title: ctx.getValue(),
                },
                ctx.getValue(),
            );
        },
    }),
    columnHelper.accessor('title', {
        header: 'Blog Title',
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
            return `${plainText.substring(0, 50)}...`;
        },
    }),
    columnHelper.accessor('offer_ext_status', {
        size: 70,
        header: 'Status',
        cell: (ctx) => {
            const s = ctx.getValue();
            const baseClass = 'rounded-md font-medium text-[11px]';
            switch (s) {
                case 0:
                    return h(Badge, { class: `${baseClass} bg-gray-200 text-black` }, { default: () => 'Closed' });
                case 1:
                    return h(Badge, { class: `${baseClass} bg-green-100 text-green-800` }, { default: () => 'Active' });
                case 2:
                    return h(Badge, { class: `${baseClass} bg-blue-200 text-blue-800` }, { default: () => 'In Progress' });
                default:
                    return h(Badge, { class: `${baseClass} bg-gray-200 text-black` }, { default: () => '' });
            }
        },
    }),
    columnHelper.display({
        id: 'actions',
        size: 100,
        header: () => 'Actions',
        cell: ({ row }) => {
            const r = row.original;
            const magentoBlog = r.whc_supplier_offer_blog_magento;
            const canViewMagentoBlog = magentoBlog && magentoBlog.status === 1;

            const children = [];

            if (r.is_sold) {
                children.push(h(Badge, { class: 'border-red-600 bg-red-100 font-bold text-red-700' }, { default: () => 'SOLD' }));
            }

            children.push(
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
            );

            if (canViewMagentoBlog) {
                children.push(
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
                );
            }

            if (!magentoBlog && !r.is_sold) {
                children.push(
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
                );
            }

            return h('div', { class: 'flex items-center gap-1' }, children);
        },
    }),
    columnHelper.display({
        id: 'in_magento',
        size: 50,
        header: () => 'C',
        cell: ({ row }) => {
            if (row.original.whc_supplier_offer_blog_magento) {
                return h(Badge, { class: 'bg-green-100 text-green-800 text-[11px]' }, { default: () => 'Y' });
            } else {
                return h(Badge, { class: 'bg-red-200 text-black text-[11px]' }, { default: () => 'N' });
            }
        },
    }),
    columnHelper.display({
        id: 'toggle_magento',
        size: 50,
        header: () => 'T',
        cell: ({ row }) => {
            if (row.original.is_sold) {
                return null;
            }
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
        header: () => 'U',
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

    columnHelper.display({
        id: 'sell',
        size: 50,
        header: () => 'S',
        cell: ({ row }) => {
            if (row.original.is_sold) {
                return null;
            }
            const magentoBlog = row.original.whc_supplier_offer_blog_magento;

            if (magentoBlog) {
                return h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        class: 'cursor-pointer',
                        onClick: () => openSellConfirmationDialog(row.original),
                        title: 'Mark as sold',
                    },
                    { default: () => h(DollarSign, { class: 'h-4 w-4' }) },
                );
            }
        },
    }),
];

const isSellConfirmOpen = ref(false);
const blogToSell = ref<WhcSupplierBlog | null>(null);
const openSellConfirmationDialog = (blog: WhcSupplierBlog) => {
    blogToSell.value = blog;
    isSellConfirmOpen.value = true;
};

const updateSellBlog = (blog: WhcSupplierBlog) => {
    router.post(
        route('whc.supplier.blog.update-as-sold', blog.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success('Blog updated as sold!', {
                    description: 'The blog has been successfully updated as sold.',
                }),
            onError: (err) =>
                toast.error('Error', {
                    description: err.service ?? 'Something went wrong while updating.',
                }),
        },
    );
};

const handleConfirmSell = () => {
    if (blogToSell.value) {
        updateSellBlog(blogToSell.value);
    }
};

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
    switch (selectedBlog.value.offer_ext_status) {
        case 0:
            return 'Closed';
        case 1:
            return 'Active';
        case 2:
            return 'In Progress';
        default:
            return '';
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

const STATUS_OPTIONS = [
    { value: 0, label: 'Closed' },
    { value: 1, label: 'Active' },
    { value: 2, label: 'In Progress' },
] as const;

const statusMap = Object.fromEntries(STATUS_OPTIONS.map((opt) => [opt.value, opt.label]));

const filterForm = reactive({
    statuses: props.filters?.statuses ?? [],
});

const onlyWhcNotActiveButMagentoActive = ref(!!props.filters?.whc_not_active_but_magento_active);
const statusPopoverOpen = ref(false);
const stagedStatuses = ref<number[]>([...filterForm.statuses]);

watch(
    () => statusPopoverOpen.value,
    (open) => {
        if (open) stagedStatuses.value = [...filterForm.statuses];
    },
);

const applyFilters = () => {
    const f: Record<string, any> = {};
    const anyToggleActive = onlyWhcNotActiveButMagentoActive.value || onlyWhcActiveButWhcNotApproved.value || onlyWhcActiveAndWhcApproved.value;
    if (!anyToggleActive && filterForm.statuses.length) {
        f.statuses = filterForm.statuses;
    }

    if (onlyWhcNotActiveButMagentoActive.value) f.whc_not_active_but_magento_active = 1;
    if (onlyWhcActiveButWhcNotApproved.value) f.whc_active_but_whc_not_approved = 1;
    if (onlyWhcActiveAndWhcApproved.value) f.whc_active_and_whc_approved = 1;

    router.get(route('whc.supplier.blog.index'), f, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(onlyWhcNotActiveButMagentoActive, (isToggledOn) => {
    if (isToggledOn) {
        onlyWhcActiveButWhcNotApproved.value = false;
        onlyWhcActiveAndWhcApproved.value = false;
        filterForm.statuses = [];
    }
    applyFilters();
});

const toggleStagedStatus = (status: number) => {
    const arr = stagedStatuses.value;
    const i = arr.indexOf(status);
    if (i > -1) arr.splice(i, 1);
    else arr.push(status);
};

const applyStatuses = () => {
    filterForm.statuses = [...stagedStatuses.value];
    statusPopoverOpen.value = false;
    applyFilters();
};

const clearStagedStatuses = () => {
    stagedStatuses.value = [];
};

const cancelStatuses = () => {
    statusPopoverOpen.value = false;
};

const removeCommittedStatus = (status: number) => {
    const i = filterForm.statuses.indexOf(status);
    if (i > -1) {
        filterForm.statuses.splice(i, 1);
        applyFilters();
    }
};

const sortedSelectedStatuses = computed(() => {
    return [...filterForm.statuses].sort((a, b) => a - b);
});

const onlyWhcActiveButWhcNotApproved = ref(!!props.filters?.whc_active_but_whc_not_approved);

const onlyWhcActiveAndWhcApproved = ref(!!props.filters?.whc_active_and_whc_approved);

watch(onlyWhcActiveButWhcNotApproved, (isToggledOn) => {
    if (isToggledOn) {
        onlyWhcNotActiveButMagentoActive.value = false;
        onlyWhcActiveAndWhcApproved.value = false;
        filterForm.statuses = [];
    }
    applyFilters();
});

// Watcher for the THIRD toggle
watch(onlyWhcActiveAndWhcApproved, (isToggledOn) => {
    if (isToggledOn) {
        onlyWhcNotActiveButMagentoActive.value = false;
        onlyWhcActiveButWhcNotApproved.value = false;
        filterForm.statuses = [];
    }
    applyFilters();
});

const lastSyncedInfo = computed(() => {
    if (!props.lastSynced) {
        return {
            displayText: null,
            colorClass: 'bg-gray-100 text-gray-600',
        };
    }

    const syncDate = parseISO(props.lastSynced);
    const now = new Date();

    let colorClass = '';

    if (differenceInHours(now, syncDate) < 1) {
        colorClass = 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20';
    } else if (isToday(syncDate)) {
        colorClass = 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20';
    } else if (isYesterday(syncDate)) {
        colorClass = 'bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20';
    } else {
        colorClass = 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10';
    }

    return {
        displayText: format(syncDate, "dd.MM.yyyy 'at' HH:mm "),
        colorClass: colorClass,
    };
});
</script>

<template>
    <Head title="Supplier Blogs" />

    <div class="m-4 flex items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="grid w-64 gap-1.5">
                <Popover v-model:open="statusPopoverOpen">
                    <PopoverTrigger as-child>
                        <Button variant="outline" role="combobox" class="w-full justify-between font-normal">
                            {{ filterForm.statuses.length > 0 ? `${filterForm.statuses.length} selected` : 'Select status...' }}
                            <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                    </PopoverTrigger>

                    <PopoverContent class="w-64 p-0">
                        <Command>
                            <CommandList>
                                <CommandEmpty>No status found.</CommandEmpty>
                                <CommandGroup>
                                    <CommandItem
                                        v-for="status in STATUS_OPTIONS"
                                        :key="status.value"
                                        :value="status.label"
                                        @select="() => toggleStagedStatus(status.value)"
                                    >
                                        <Check :class="['mr-2 h-4 w-4', stagedStatuses.includes(status.value) ? 'opacity-100' : 'opacity-0']" />
                                        {{ status.label }}
                                    </CommandItem>
                                </CommandGroup>
                            </CommandList>
                        </Command>

                        <div class="flex items-center justify-between gap-2 border-t p-2">
                            <Button variant="ghost" size="sm" @click="clearStagedStatuses">Clear</Button>
                            <div class="space-x-2">
                                <Button variant="outline" size="sm" @click="cancelStatuses">Cancel</Button>
                                <Button size="sm" @click="applyStatuses">Apply</Button>
                            </div>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>
            <div v-if="sortedSelectedStatuses.length > 0" class="flex flex-wrap items-center gap-2">
                <Badge v-for="status in sortedSelectedStatuses" :key="`badge-${status}`" variant="secondary" class="flex items-center gap-x-1.5">
                    {{ statusMap[status] }}
                    <button
                        type="button"
                        @click.prevent="removeCommittedStatus(status)"
                        class="rounded-full hover:bg-background/50"
                        aria-label="Remove filter"
                        title="Remove"
                    >
                        <X class="h-3 w-3" />
                    </button>
                </Badge>
            </div>

            <div
                v-if="lastSyncedInfo.displayText"
                :class="lastSyncedInfo.colorClass"
                class="rounded-md px-2 py-1 text-xs font-medium whitespace-nowrap"
                title="Last sync time (Europe/Berlin)"
            >
                Last Sync: {{ lastSyncedInfo.displayText }}
            </div>
            <div v-else class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Never synced</div>

            <div class="flex items-center space-x-2">
                <Switch id="quick-filter-whc-not-active-but-magento-active-toggle" v-model="onlyWhcNotActiveButMagentoActive" />
                <Label
                    for="quick-filter-whc-not-active-but-magento-active-toggle"
                    class="cursor-pointer font-semibold"
                    title="Show active offers that are not sold and are active in Magento"
                >
                    WHC not active but Magento active
                </Label>
            </div>
            <div class="flex items-center space-x-2">
                <Switch id="quick-filter-whc-active-but-whc-not-approved-toggle" v-model="onlyWhcActiveButWhcNotApproved" />
                <Label
                    for="quick-filter-whc-active-but-whc-not-approved-toggle"
                    class="cursor-pointer font-semibold"
                    title="Show active offers that are not sold and are active in Magento"
                >
                    WHC active but WHC not approved
                </Label>
            </div>
            <div class="flex items-center space-x-2">
                <Switch id="quick-filter-whc-active-and-whc-active-toggle" v-model="onlyWhcActiveAndWhcApproved" />
                <Label
                    for="quick-filter-whc-active-and-whc-active-toggle"
                    class="cursor-pointer font-semibold"
                    title="Show active offers that are not sold and are active in Magento"
                >
                    WHC active and WHC approved
                </Label>
            </div>
        </div>

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
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        :class="{ 'bg-red-50 text-muted-foreground': row.original.is_sold }"
                    >
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" :style="{ width: `${cell.column.getSize()}px` }">
                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </TableCell>
                    </TableRow>
                </template>

                <template v-else>
                    <TableRow>
                        <TableCell :colspan="columns.length" class="h-24 text-center"> No blogs found matching your filters.</TableCell>
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
                    <div class="mb-4 grid grid-cols-6 gap-4 border-b pb-4">
                        <div>
                            <span class="font-semibold text-muted-foreground">Status</span>
                            <p>{{ formattedStatus }}</p>
                        </div>
                        <div>
                            <span class="font-semibold text-muted-foreground">Is Brand</span>
                            <p>
                                <span
                                    :class="
                                        selectedBlog.is_brand == true
                                            ? 'inline-flex items-center rounded-md bg-green-200 px-2 py-1 text-xs font-medium text-green-800'
                                            : 'inline-flex items-center rounded-md bg-red-200 px-2 py-1 text-xs font-medium text-red-800'
                                    "
                                >
                                    {{ selectedBlog.is_brand == true ? 'true' : 'false' }}
                                </span>
                            </p>
                        </div>

                        <div>
                            <span class="font-semibold text-muted-foreground">Is B Group</span>
                            <p>
                                <span
                                    :class="
                                        selectedBlog.is_b_group_appr == true
                                            ? 'inline-flex items-center rounded-md bg-green-200 px-2 py-1 text-xs font-medium text-green-800'
                                            : 'inline-flex items-center rounded-md bg-red-200 px-2 py-1 text-xs font-medium text-red-800'
                                    "
                                >
                                    {{ selectedBlog.is_b_group_appr == true ? 'true' : 'false' }}
                                </span>
                            </p>
                        </div>

                        <div>
                            <span class="font-semibold text-muted-foreground">Is Approved</span>
                            <p>
                                <span
                                    :class="
                                        selectedBlog.is_approved == true
                                            ? 'inline-flex items-center rounded-md bg-green-200 px-2 py-1 text-xs font-medium text-green-800'
                                            : 'inline-flex items-center rounded-md bg-red-200 px-2 py-1 text-xs font-medium text-red-800'
                                    "
                                >
                                    {{ selectedBlog.is_approved == true ? 'true' : 'false' }}
                                </span>
                            </p>
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
                    <div>
                        <span class="font-semibold text-muted-foreground">Description</span>
                        <div v-html="selectedBlog.description" class="prose mt-2 max-w-none dark:prose-invert"></div>
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

    <AlertDialog :open="isSellConfirmOpen" @update:open="(v) => (isSellConfirmOpen = v)">
        <AlertDialogContent class="w-[90vw] !max-w-md sm:w-auto">
            <AlertDialogHeader>
                <AlertDialogTitle>Mark this as sold?</AlertDialogTitle>
                <AlertDialogDescription> This will update the Magento blog as sold. </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="handleConfirmSell">Update</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
