<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CallCenterLayout from '../../Layouts/CallCenterLayout.vue';
import AccountProfileForm from '../../Components/AccountProfileForm.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { primaryRole, roleLabel } from '../../workspace';

defineOptions({ layout: CallCenterLayout });
defineProps({
    user: { type: Object, required: true },
});

const page = usePage();
const role = computed(() => primaryRole(page.props.auth.roles || []));
</script>

<template>
    <PageHeader title="حسابي" :text="`دورك الحالي: ${roleLabel(role)}.`" />
    <AccountProfileForm :user="user" profile-url="/profile" password-url="/profile/password" />
</template>
