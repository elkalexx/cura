<script setup lang="ts">
import { Card } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Message } from '@/types/mail';
import { Link, router } from '@inertiajs/vue3';
import { createColumnHelper, FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';

// Lucide icons
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { format, parseISO } from 'date-fns';
import { Forward, RotateCw } from 'lucide-vue-next';
import { h, ref } from 'vue';
import { toast } from 'vue-sonner';

// NEW: Import the reusable modal component
import ForwardMessageModal from '@/components/mails/ForwardMessageModal.vue';

const props = defineProps<{
    messages: Message[];
}>();

// --- Modal State & Data ---
const isForwardModalOpen = ref(false);
// NEW: A ref to hold the message that the user wants to forward
const selectedMessage = ref<Message | null>(null);

// NEW: Dummy data for predefined contacts. This could be passed as a prop from your controller.
const predefinedContacts = ref([{ id: 'sk', email: 'sk@whc-uetersen.de', name: 'SK' }]);

const isSyncing = ref(false);

const columnHelper = createColumnHelper<Message>();

const columns = [
    columnHelper.accessor('id', {
        header: 'ID',
        cell: (ctx) => {
            return ctx.getValue();
        },
    }),
    columnHelper.accessor('sender_participant.contact.email', {
        header: 'From',
    }),
    columnHelper.accessor('conversation.subject', {
        header: 'Subject',
        cell: (ctx) => {
            const subject = ctx.getValue() as string;
            const msg = ctx.row.original;
            return h(
                'div',
                {
                    class: 'flex items-center justify-between gap-x-4 text-[13px]',
                },
                [
                    h('span', { class: 'truncate min-w-0', title: subject }, subject),
                    h(
                        Link,
                        {
                            href: route('mail.show', msg.id),
                            class: 'flex-shrink-0 underline text-indigo-600 hover:text-indigo-800',
                        },
                        () => 'View',
                    ),
                ],
            );
        },
    }),
    columnHelper.accessor('body_summary', {
        header: 'Body',
    }),
    columnHelper.accessor('created_at', {
        header: 'Created At',
        cell: (ctx) => {
            const val = ctx.getValue();
            try {
                return h('span', { class: 'text-xs' }, format(parseISO(val), 'dd.MM.yyyy HH:mm'));
            } catch {
                return 'Invalid date';
            }
        },
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => row.original,
    }),
];

const table = useVueTable({
    get data() {
        return props.messages;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
});

const forwardTo = (message: Message) => {
    selectedMessage.value = message;
    isForwardModalOpen.value = true;
};

const syncInboxEmails = () => {
    router.post(
        route('mail.sync'),
        {},
        {
            preserveScroll: true,
            onStart: () => (isSyncing.value = true),
            onFinish: () => (isSyncing.value = false),
            onSuccess: () =>
                toast.success('Sync complete!', {
                    description: 'Inbox emails synced successfully.',
                }),
            onError: (err) =>
                toast.error('Error', {
                    description: err.service ?? 'Something went wrong',
                }),
        },
    );
};

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <div class="m-4 flex items-center justify-between">
        <Button @click="syncInboxEmails" :disabled="isSyncing">
            <RotateCw v-if="isSyncing" class="mr-3 -ml-1 h-5 w-5 animate-spin" />
            <span>{{ isSyncing ? 'Syncing...' : 'Sync Inbox Emails' }}</span>
        </Button>
    </div>
    <Card class="m-4 mt-0">
        <Table class="w-full [&_td]:px-1 [&_td]:py-0.5 [&_th]:px-1 [&_th]:py-0.5 [&_tr]:text-sm">
            <TableHeader>
                <TableRow v-for="hg in table.getHeaderGroups()" :key="hg.id">
                    <TableHead v-for="header in hg.headers" :key="header.id" :colspan="header.colSpan">
                        <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <template v-if="table.getRowModel().rows.length">
                    <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                            <template v-if="cell.column.id === 'actions'">
                                <div class="flex gap-2">
                                    <!-- Forward -->
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Forward class="h-4 w-4 cursor-pointer hover:text-green-500" @click="forwardTo(row.original)" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Forward to...</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </template>

                            <template v-else>
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </template>
                        </TableCell>
                    </TableRow>
                </template>
                <template v-else>
                    <TableRow>
                        <TableCell :colspan="columns.length" class="h-24 text-center"> No messages found. </TableCell>
                    </TableRow>
                </template>
            </TableBody>
        </Table>
    </Card>

    <!-- NEW: Add the reusable modal to the template -->
    <!-- We use v-if to ensure the modal only renders when we have a message to forward -->
    <ForwardMessageModal
        v-if="selectedMessage"
        v-model:open="isForwardModalOpen"
        :message-id="selectedMessage.id"
        :predefined-contacts="predefinedContacts"
        @forward-success="
            () => {
                // Optional: You could refresh data or perform another action on success
                console.log(`Successfully initiated forward for message ID: ${selectedMessage?.id}`);
            }
        "
    />
</template>
