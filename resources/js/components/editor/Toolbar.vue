<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import type { Editor } from '@tiptap/core';
import type { Level } from '@tiptap/extension-heading';
import { Link as LinkIcon } from 'lucide-vue-next';
import { defineProps, nextTick, ref, watch } from 'vue';

const props = defineProps<{ editor: Editor }>();
const { editor } = props;

const isLinkPopoverOpen = ref(false);
const linkUrl = ref('');

const isActive = (name: string, attrs?: { level: number }) => editor.isActive(name, attrs);

const toggle = (mark: 'bold' | 'italic' | 'underline') => {
    const chain = editor.chain().focus();
    switch (mark) {
        case 'bold':
            chain.toggleBold().run();
            break;
        case 'italic':
            chain.toggleItalic().run();
            break;
        case 'underline':
            chain.toggleUnderline().run();
            break;
    }
};

const setHeading = (level: Level) => editor.chain().focus().toggleHeading({ level }).run();

const handleLinkButtonClick = () => {
    if (editor.isActive('link')) {
        linkUrl.value = editor.getAttributes('link').href;
    }
};

const applyLink = () => {
    if (linkUrl.value === '') {
        editor.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
        editor.chain().focus().extendMarkRange('link').setLink({ href: linkUrl.value }).run();
    }
    isLinkPopoverOpen.value = false;
    linkUrl.value = '';
};

const removeLink = () => {
    editor.chain().focus().extendMarkRange('link').unsetLink().run();
    isLinkPopoverOpen.value = false;
    linkUrl.value = '';
};

watch(isLinkPopoverOpen, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            const linkInput = document.getElementById('link-url');
            if (linkInput) {
                linkInput.focus();
            }
        });
    }
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-1 rounded-lg bg-muted/40 p-1 shadow-sm">
        <Button size="icon" :variant="isActive('bold') ? 'default' : 'ghost'" title="Bold" @click="toggle('bold')" type="button" tabindex="1">
            <span class="leading-none font-bold">B</span>
        </Button>
        <Button size="icon" :variant="isActive('italic') ? 'default' : 'ghost'" title="Italic" @click="toggle('italic')" type="button" tabindex="1">
            <span class="leading-none italic">I</span>
        </Button>
        <Button
            size="icon"
            :variant="isActive('underline') ? 'default' : 'ghost'"
            title="Underline"
            @click="toggle('underline')"
            type="button"
            tabindex="1"
        >
            <span class="leading-none underline">U</span>
        </Button>

        <Separator orientation="vertical" class="mx-1 h-5 self-center" />

        <Button
            size="icon"
            :variant="isActive('heading', { level: 2 }) ? 'default' : 'ghost'"
            title="Heading 2"
            @click="setHeading(2)"
            type="button"
            tabindex="1"
            >H2</Button
        >
        <Button
            size="icon"
            :variant="isActive('heading', { level: 3 }) ? 'default' : 'ghost'"
            title="Heading 3"
            @click="setHeading(3)"
            type="button"
            tabindex="1"
            >H3</Button
        >

        <Separator orientation="vertical" class="mx-1 h-5 self-center" />

        <Popover v-model:open="isLinkPopoverOpen">
            <PopoverTrigger as-child>
                <Button
                    size="icon"
                    :variant="isActive('link') ? 'default' : 'ghost'"
                    title="Insert / edit link"
                    type="button"
                    @click="handleLinkButtonClick"
                    tabindex="1"
                >
                    <LinkIcon class="h-4 w-4" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-80" align="start">
                <div class="grid gap-4">
                    <div class="space-y-2">
                        <h4 class="leading-none font-medium">Set Link</h4>
                        <p class="text-sm text-muted-foreground">Enter the full URL for the link.</p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <Input
                            id="link-url"
                            ref="linkInputRef"
                            v-model="linkUrl"
                            placeholder="https://example.com"
                            @keydown.enter.prevent="applyLink"
                        />
                        <div class="flex justify-end gap-2">
                            <Button v-if="editor.isActive('link')" type="button" variant="destructive" size="sm" @click="removeLink" tabindex="1">
                                Remove
                            </Button>
                            <Button type="button" size="sm" @click="applyLink" tabindex="1">Apply</Button>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>
