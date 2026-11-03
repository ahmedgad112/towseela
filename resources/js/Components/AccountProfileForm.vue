<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiButton from './UiButton.vue';
import UiInput from './UiInput.vue';
import FormSection from './FormSection.vue';
import PasswordRequirements from './PasswordRequirements.vue';

const props = defineProps({
    user: { type: Object, required: true },
    profileUrl: { type: String, required: true },
    passwordUrl: { type: String, required: true },
});

const page = usePage();
const googleAuthEnabled = computed(() => page.props.platform?.google_auth_enabled === true);
const googleLinked = computed(() => props.user.google_linked === true);
const hasPassword = computed(() => props.user.has_password === true);

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

const unlinkGoogle = useForm({});
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

        <FormSection
            v-if="googleAuthEnabled"
            title="حساب Google"
            text="اربط بريدك بحساب Google لتسجيل الدخول بسرعة. لازم يكون بريد Google نفس بريد حسابك."
        >
            <div v-if="googleLinked" class="space-y-4">
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    حساب Google مرتبط بهذا البريد.
                </p>
                <UiButton
                    type="button"
                    variant="outline"
                    :disabled="unlinkGoogle.processing || !hasPassword"
                    @click="unlinkGoogle.delete('/auth/google')"
                >
                    فصل حساب Google
                </UiButton>
                <p v-if="!hasPassword" class="text-sm text-muted">
                    أضف كلمة مرور أولاً قبل فصل حساب Google حتى لا تفقد الدخول للحساب.
                </p>
            </div>
            <UiButton v-else href="/auth/google/redirect" variant="outline" class="w-full gap-2 sm:w-auto">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M12 10.2v3.6h5.1c-.2 1.2-.9 2.3-1.9 3l3.1 2.4c1.8-1.7 2.9-4.1 2.9-7 0-.7-.1-1.3-.2-1.9H12z" />
                    <path fill="#34A853" d="M6.6 14.3 5.8 14.9l-2.7 2.1C4.8 20.1 8.1 22 12 22c2.7 0 5-.9 6.7-2.4l-3.1-2.4c-.9.6-2 1-3.6 1-2.8 0-5.1-1.9-5.9-4.4z" />
                    <path fill="#4A90E2" d="M3.1 7.1C2.4 8.5 2 10.2 2 12s.4 3.5 1.1 4.9l3.5-2.7C6.2 13.4 6 12.7 6 12s.2-1.4.6-2.1L3.1 7.1z" />
                    <path fill="#FBBC05" d="M12 5.8c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 2.9 14.7 2 12 2 8.1 2 4.8 3.9 3.1 7.1l3.5 2.7C7 7.7 9.2 5.8 12 5.8z" />
                </svg>
                ربط حساب Google
            </UiButton>
        </FormSection>

        <FormSection
            title="كلمة المرور"
            :text="hasPassword
                ? 'أدخل كلمة المرور الحالية ثم كلمة مرور جديدة وفق المعاير المطلوبة.'
                : 'أنشئ كلمة مرور لحسابك حتى تقدر تفصل Google أو تدخل بدونها.'"
        >
            <form
                class="space-y-4"
                @submit.prevent="passwordForm.put(passwordUrl, { onSuccess: () => passwordForm.reset() })"
            >
                <UiInput
                    v-if="hasPassword"
                    v-model="passwordForm.current_password"
                    type="password"
                    label="كلمة المرور الحالية"
                    autocomplete="current-password"
                    :error="passwordForm.errors.current_password"
                />
                <UiInput
                    v-model="passwordForm.password"
                    type="password"
                    :label="hasPassword ? 'كلمة المرور الجديدة' : 'كلمة المرور'"
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
                <UiButton type="submit" :disabled="passwordForm.processing">
                    {{ hasPassword ? 'تغيير كلمة المرور' : 'إنشاء كلمة مرور' }}
                </UiButton>
            </form>
        </FormSection>
    </div>
</template>
