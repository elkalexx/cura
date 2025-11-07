<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, MailOpen, Menu, Package } from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, ref } from 'vue';

export interface NavItem {
    title: string;
    href?: string;
    icon?: Component;
    children?: NavItem[];
    description?: string;
}

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const openMobileSubmenu = ref<string | null>(null);

const toggleMobileSubmenu = (title: string) => {
    openMobileSubmenu.value = openMobileSubmenu.value === title ? null : title;
};

const isActive = (item: NavItem): boolean => {
    const checkUrl = (url?: string): boolean => {
        if (!url) {
            return false;
        }

        const cleanItemUrl = url.split('?')[0];

        if (cleanItemUrl === '/') {
            return page.url === '/';
        }

        return page.url.startsWith(cleanItemUrl);
    };

    if (checkUrl(item.href)) {
        return true;
    }

    if (item.children) {
        return item.children.some((child) => checkUrl(child.href));
    }

    return false;
};

const activeItemStyles = computed(
    () => (item: NavItem) => (isActive(item) ? 'text-neutral-900 bg-accent dark:bg-neutral-800 dark:text-neutral-100' : ''),
);
const activeDesktopIndicator = computed(
    () => (item: NavItem) => (isActive(item) ? 'absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white' : ''),
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Offer Blog',
            href: '/whc-supplier-blog',
            icon: Package,
        },
    ];

    if (auth.value.user?.email === 'sk@whc-ueteren.de') {
        items.unshift({
            title: 'Mail',
            href: '/email',
            icon: MailOpen,
        });
    }

    return items;
});
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="flex h-12 w-full items-center px-1">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon class="size-6 fill-current text-black dark:text-white" />
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                <!-- 5. UPDATED MOBILE NAV -->
                                <nav class="-mx-3 space-y-1">
                                    <template v-for="item in mainNavItems" :key="item.title">
                                        <!-- Item without children -->
                                        <Link
                                            v-if="!item.children"
                                            :href="item.href!"
                                            class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                            :class="activeItemStyles(item)"
                                        >
                                            <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                            {{ item.title }}
                                        </Link>
                                        <!-- Item with children (Accordion) -->
                                        <div v-else>
                                            <button
                                                class="flex w-full items-center justify-between gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                                :class="activeItemStyles(item)"
                                                @click="toggleMobileSubmenu(item.title)"
                                            >
                                                <span class="flex items-center gap-x-3">
                                                    <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                                    {{ item.title }}
                                                </span>
                                                <ChevronDown
                                                    class="h-4 w-4 shrink-0 transition-transform duration-200"
                                                    :class="openMobileSubmenu === item.title && 'rotate-180'"
                                                />
                                            </button>
                                            <div v-if="openMobileSubmenu === item.title" class="mt-1 space-y-1 pl-8">
                                                <Link
                                                    v-for="child in item.children"
                                                    :key="child.title"
                                                    :href="child.href!"
                                                    class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                                    :class="activeItemStyles(child)"
                                                >
                                                    {{ child.title }}
                                                </Link>
                                            </div>
                                        </div>
                                    </template>
                                </nav>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <AppLogo />

                <Separator orientation="vertical" class="ml-3" />

                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-1 flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-2">
                            <template v-for="(item, index) in mainNavItems" :key="index">
                                <NavigationMenuItem v-if="!item.children" class="relative flex h-full items-center">
                                    <Link
                                        :class="[navigationMenuTriggerStyle(), activeItemStyles(item), 'h-9 cursor-pointer px-3']"
                                        :href="item.href!"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="mr-2 h-4 w-4" />
                                        {{ item.title }}
                                    </Link>
                                    <div :class="activeDesktopIndicator(item)"></div>
                                </NavigationMenuItem>
                                <NavigationMenuItem v-else class="relative flex h-full items-center">
                                    <NavigationMenuTrigger :class="[navigationMenuTriggerStyle(), activeItemStyles(item), 'h-9 cursor-pointer px-3']">
                                        <component v-if="item.icon" :is="item.icon" class="mr-2 h-4 w-4" />
                                        {{ item.title }}
                                    </NavigationMenuTrigger>
                                    <NavigationMenuContent>
                                        <ul class="grid w-[300px] gap-3 p-4">
                                            <li v-for="child in item.children" :key="child.title">
                                                <Link
                                                    :href="child.href!"
                                                    class="block space-y-1 rounded-md p-3 leading-none no-underline transition-colors outline-none select-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                                                    :class="activeItemStyles(child)"
                                                >
                                                    <div class="text-sm leading-none font-medium">{{ child.title }}</div>
                                                    <p class="line-clamp-2 text-sm leading-snug text-muted-foreground">
                                                        {{ child.description }}
                                                    </p>
                                                </Link>
                                            </li>
                                        </ul>
                                    </NavigationMenuContent>
                                    <div :class="activeDesktopIndicator(item)"></div>
                                </NavigationMenuItem>
                            </template>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-sidebar-border/70">
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
