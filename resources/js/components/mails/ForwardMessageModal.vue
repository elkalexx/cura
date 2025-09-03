<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

// UI Components
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface PredefinedContact {
    id: string | number;
    email: string;
    name: string;
}

const props = defineProps<{
    open: boolean;
    messageId: number | string;
    predefinedContacts: PredefinedContact[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'forwardSuccess'): void;
}>();

const form = useForm({
    recipients: [] as string[],
    customEmail: '',
});

const submitForward = () => {
    form.post(route('mail.forward', props.messageId), {
        onSuccess: () => {
            toast.success('Message forwarded successfully.');
            emit('forwardSuccess');
            closeModal();
        },
        onError: (errors) => {
            toast.error(errors.service ?? 'Failed to forward message. Please check your input.');
        },
    });
};

const closeModal = () => {
    emit('update:open', false);
    // Use a timeout to reset the form after the closing animation finishes
    setTimeout(() => form.reset(), 300);
};
</script>

<template>
    <!--
        The Dialog's open state is controlled by the `open` prop.
        When the user closes it (e.g., by clicking outside), it emits `update:open`,
        which we propagate to the parent component to update its state.
    -->
    <Dialog :open="props.open" @update:open="closeModal">
        <DialogContent class="sm:max-w-[425px]">
            <form @submit.prevent="submitForward">
                <DialogHeader>
                    <DialogTitle>Forward Message</DialogTitle>
                    <DialogDescription>
                        Select recipients from the list or enter a custom email address.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <!-- Predefined Contacts Checkboxes -->
                    <div class="flex flex-col gap-2">
                        <Label>Predefined Recipients</Label>
                        <div
                            v-for="contact in props.predefinedContacts"
                            :key="contact.id"
                            class="flex items-center space-x-2"
                        >
                            <Checkbox
                                :id="`contact-modal-${contact.id}`"
                                :checked="form.recipients.includes(contact.email)"
                                @update:model-value="(checked) => {
                                    if (checked) {
                                        form.recipients.push(contact.email);
                                    } else {
                                        form.recipients = form.recipients.filter(r => r !== contact.email);
                                    }
                                }"
                            />
                            <Label :for="`contact-modal-${contact.id}`" class="font-normal cursor-pointer">
                                {{ contact.name }} &lt;{{ contact.email }}&gt;
                            </Label>
                        </div>
                        <InputError :message="form.errors.recipients" class="mt-1 text-sm" />
                    </div>

                    <!-- OR Separator -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <span class="w-full border-t" />
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="bg-background px-2 text-muted-foreground">Or</span>
                        </div>
                    </div>

                    <!-- Custom Email Input Field -->
                    <div class="grid w-full items-center gap-1.5">
                        <Label for="customEmail">Custom E-Mail</Label>
                        <Input
                            id="customEmail"
                            type="email"
                            placeholder="recipient@example.com"
                            v-model="form.customEmail"
                        />
                        <InputError :message="form.errors.customEmail" class="mt-1 text-sm" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Forwarding...' : 'Forward' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
