<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Hospital, User } from 'lucide-vue-next';

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
  <AppLayout>
    <div class="flex flex-1 flex-col gap-4 p-4">

      <div class="grid gap-4 md:grid-cols-3">

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Customers</CardTitle>
            <Users class="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ stats.total_customers }}</div>
            <p class="text-xs text-muted-foreground mt-1">Active customers</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Drugstores</CardTitle>
            <Hospital class="h-5 w-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ stats.total_drugstores }}</div>
            <p class="text-xs text-muted-foreground mt-1">Active drugstore accounts</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Doctors</CardTitle>
            <User class="h-5 w-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ stats.total_doctors }}</div>
            <p class="text-xs text-muted-foreground mt-1">Active doctor accounts</p>
          </CardContent>
        </Card>

      </div>

      <div class="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min" />
    </div>
  </AppLayout>
</template>
