<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

// Layout & Types
import AppLayout from "@/layouts/AppLayout.vue";
import { Message } from "@/types/mail";

// TipTap Editor
import { EditorContent, useEditor } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";
import Link from "@tiptap/extension-link";
import Toolbar from '@/components/editor/Toolbar.vue';

// UI Components
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import ForwardMessageModal from "@/components/mails/ForwardMessageModal.vue";

// NEW: Import the reusable modal component



defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    message: Message;
}>();

// --- Modal State ---
const isForwardModalOpen = ref(false);

const predefinedContacts = ref([
    { id: 'sk', email: 'sk@whc-uetersen.de', name: 'SK' },
]);

// --- Action Handlers ---
// UPDATED: This function now just opens the modal. The modal handles the rest.
const forwardMessage = () => {
    isForwardModalOpen.value = true;
};

// --- Email Viewer Logic ---
const iframeRef = ref<HTMLIFrameElement | null>(null);
const styledContent = computed(() => {
    const htmlContent = props.message.body ?? '<p>No content available.</p>';
    return `<!DOCTYPE html><html><head><style>body { font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.6; margin: 0; padding: 1rem; } img { max-width: 100%; height: auto; }</style></head><body>${htmlContent}</body></html>`;
});

const resizeIframe = () => {
    const iframe = iframeRef.value;
    if (iframe && iframe.contentWindow?.document?.body) {
        iframe.style.height = 'auto';
        iframe.style.height = `${iframe.contentWindow.document.body.scrollHeight + 20}px`;
    }
};

onMounted(() => {
    const iframe = iframeRef.value;
    if (iframe) {
        iframe.addEventListener('load', resizeIframe);
    }
});

// --- Reply Composer Logic ---
const form = useForm({ body: '' });
const editor = useEditor({
    extensions: [
        StarterKit.configure({ heading: { levels: [2, 3] } }),
        Underline,
        Link.configure({ autolink: true, openOnClick: false, HTMLAttributes: { class: 'text-blue-500 underline' } }),
    ],
    editorProps: {
        attributes: {
            class: 'prose max-w-none h-full focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => form.body = editor.getHTML(),
});

const replyEmail = () => {
    if (editor.value) form.body = editor.value.getHTML();
    form.post(route('mail.reply', props.message.id), {
        onSuccess: () => {
            toast.success('Reply sent successfully');
            editor.value?.commands.clearContent();
            form.reset();
        },
        onError: (errors) => toast.error(errors.service ?? 'Failed to send reply'),
    });
};
</script>

<template>
    <Head :title="`View: ${props.message.conversation.subject}`" />

    <div class="h-[calc(100vh-4rem)] flex flex-col p-4 sm:p-6 lg:p-8">
        <div class="flex flex-1 flex-col md:flex-row gap-6 overflow-hidden">
            <!-- Left Column: Email Viewer -->
            <div class="w-full md:w-1/2 flex flex-col">
                <Card class="flex-1 flex flex-col">
                    <CardHeader class="border-b">
                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <CardTitle class="text-xl">{{ props.message.conversation.subject }}</CardTitle>
                                <p class="text-sm text-muted-foreground">
                                    From: {{ props.message.sender_participant.contact.display_name }} &lt;{{ props.message.sender_participant.contact.email }}&gt;
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <Button variant="outline" size="sm" @click="forwardMessage">Forward</Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="flex-1 overflow-y-auto p-0">
                        <iframe
                            ref="iframeRef"
                            sandbox="allow-same-origin"
                            :srcdoc="styledContent"
                            class="w-full h-full border-0"
                            title="Email Content"
                        ></iframe>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Reply Composer -->
            <div class="w-full md:w-1/2 flex flex-col">
                <form @submit.prevent="replyEmail" class="flex-1 flex flex-col">
                    <Card class="flex-1 flex flex-col">
                        <CardHeader class="border-b">
                            <CardTitle>Your Reply</CardTitle>
                        </CardHeader>
                        <CardContent class="flex-1 flex flex-col p-0">
                            <Toolbar v-if="editor" :editor="editor" />
                            <div class="flex-1 flex overflow-y-auto p-4">
                                <EditorContent :editor="editor" class="w-full" />
                            </div>
                            <InputError :message="form.errors.body" class="px-4 pb-2 text-sm" />
                        </CardContent>
                        <CardFooter class="border-t pt-4 flex justify-end">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Sending...' : 'Send Reply' }}
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW: Use the reusable modal component -->
    <ForwardMessageModal
        v-model:open="isForwardModalOpen"
        :message-id="props.message.id"
        :predefined-contacts="predefinedContacts"
        @forward-success="() => console.log('Forward succeeded!')"
    />
</template>
