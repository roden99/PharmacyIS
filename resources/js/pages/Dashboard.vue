<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Hospital, User, Quote, CalendarDays } from 'lucide-vue-next';
import type { AppPageProps } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const page = usePage<AppPageProps>();
const user = page.props.auth.user;
const quote = page.props.quote;

const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

const dayNum = new Date().getDate();
const dayName = new Date().toLocaleDateString('en-US', { weekday: 'long' });
const monthYear = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

defineProps<{
    stats: {
        total_customers: number;
        total_drugstores: number;
        total_doctors: number;
    };
}>();
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

            <!-- Welcome Hero Banner -->
            <div class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground">
                <!-- decorative circles -->
                <div class="pointer-events-none absolute -top-8 -right-8 h-40 w-40 rounded-full bg-white/10" />
                <div class="pointer-events-none absolute -bottom-10 -right-24 h-56 w-56 rounded-full bg-white/5" />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <!-- Left: greeting + quote -->
                    <div class="flex flex-col gap-2">
                        <p class="text-sm font-medium text-primary-foreground/70 uppercase tracking-widest">Good day</p>
                        <h1 class="text-3xl font-bold">{{ user.name }}</h1>
                        <div class="flex items-start gap-2 text-primary-foreground/80 mt-1 max-w-xl">
                            <Quote class="h-4 w-4 mt-0.5 shrink-0 opacity-70" />
                            <p class="text-sm italic leading-relaxed">
                                "{{ quote.message }}"
                                <span class="not-italic font-semibold text-primary-foreground ml-1">— {{ quote.author
                                    }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Right: date block -->
                    <div class="flex items-center gap-3 shrink-0 bg-white/10 rounded-xl px-4 py-3 backdrop-blur-sm">
                        <div class="flex flex-col items-center justify-center bg-white/20 rounded-lg w-14 h-14">
                            <span class="text-3xl font-bold leading-none">{{ dayNum }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-base font-semibold">{{ dayName }}</span>
                            <span class="text-sm text-primary-foreground/70">{{ monthYear }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <CalendarDays class="h-3 w-3 opacity-60" />
                                <span class="text-xs text-primary-foreground/60">Today</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid gap-4 md:grid-cols-3">

                <Card class="border-l-4 border-l-primary">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Customers</CardTitle>
                        <div class="rounded-lg bg-primary/10 p-2">
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-primary">{{ stats.total_customers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Active customers</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-green-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Drugstores</CardTitle>
                        <div class="rounded-lg bg-green-500/10 p-2">
                            <Hospital class="h-4 w-4 text-green-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">{{ stats.total_drugstores }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Active drugstore accounts</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-blue-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Doctors</CardTitle>
                        <div class="rounded-lg bg-blue-500/10 p-2">
                            <User class="h-4 w-4 text-blue-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-600">{{ stats.total_doctors }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Active doctor accounts</p>
                    </CardContent>
                </Card>

            </div>

        </div>
    </AppLayout>
</template>
