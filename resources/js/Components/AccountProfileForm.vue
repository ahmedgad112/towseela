<script setup>
import { useForm } from '@inertiajs/vue3';
import UiButton from './UiButton.vue';
import UiInput from './UiInput.vue';
import FormSection from './FormSection.vue';
import PasswordRequirements from './PasswordRequirements.vue';

const props = defineProps({
    user: { type: Object, required: true },
    profileUrl: { type: String, required: true },
    passwordUrl: { type: String, required: true },
});

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <div class="space-y-4">
        <FormSection title="البيانات الشخصية" text="حدّث اسمك وبريدك ورقم هاتفك.">
            <form class="space-y-4" @submit.prevent="profileForm.patch(profileUrl)">
                <UiInput v-model="profileForm.name" label="الاسم" autocomplete="name" :error="profileForm.errors.name" />
                <UiInput v-model="profileForm.email" type="email" label="البريد" autocomplete="email" :error="profileForm.errors.email" />
                <UiInput v-model="profileForm.phone" label="الهاتف" autocomplete="tel" :error="profileForm.errors.phone" />
                <UiButton type="submit" :disabled="profileForm.processing">حفظ التغييرات</UiButton>
            </form>
        </FormSection>

        <FormSection title="كلمة المرور" text="أدخل كلمة المرور الحالية ثم كلمة مرور جديدة وفق المعاير المطلوبة.">
            <form
                class="space-y-4"
                @submit.prevent="passwordForm.put(passwordUrl, { onSuccess: () => passwordForm.reset() })"
            >
                <UiInput
                    v-model="passwordForm.current_password"
                    type="password"
                    label="كلمة المرور الحالية"
                    autocomplete="current-password"
                    :error="passwordForm.errors.current_password"
                />
                <UiInput
                    v-model="passwordForm.password"
                    type="password"
                    label="كلمة المرور الجديدة"
                    autocomplete="new-password"
                    :error="passwordForm.errors.password"
                />
                <PasswordRequirements :password="passwordForm.password" />
                <UiInput
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    label="تأكيد كلمة المرور"
                    autocomplete="new-password"
                    :error="passwordForm.errors.password_confirmation"
                />
                <UiButton type="submit" :disabled="passwordForm.processing">تغيير كلمة المرور</UiButton>
            </form>
        </FormSection>
    </div>
</template>
