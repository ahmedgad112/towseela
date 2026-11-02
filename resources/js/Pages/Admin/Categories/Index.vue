<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import { canDo } from '../../../workspace';

defineOptions({ layout: AdminLayout });
defineProps({ categories: { type: Array, default: () => [] } });
const page = usePage();
const icons = ['utensils', 'coffee', 'basket', 'bag', 'store', 'box', 'tag', 'star'];
const form = useForm({ name_ar: '', icon: 'utensils', is_active: true });
</script>

<template>
    <PageHeader title="تصنيفات المتاجر" text="هذه التصنيفات تظهر للعميل في الصفحة الرئيسية، مثل مطاعم أو صيدليات." />

    <div class="grid gap-4 xl:grid-cols-[minmax(0,22rem)_1fr]">
        <FormSection title="تصنيف جديد" text="اختر أيقونة واضحة حتى يميز العميل النوع بسرعة.">
            <form v-if="canDo(page.props.auth, 'categories.manage')" class="space-y-4" @submit.prevent="form.post('/admin/categories', { onSuccess: () => form.reset('name_ar') })">
                <UiInput v-model="form.name_ar" label="اسم التصنيف" placeholder="مطاعم" :error="form.errors.name_ar" />
                <div class="space-y-1.5">
                    <p class="text-sm font-semibold text-navy">الأيقونة</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="icon in icons"
                            :key="icon"
                            type="button"
                            class="grid size-11 place-items-center rounded-2xl border"
                            :class="form.icon === icon ? 'border-gold bg-gold/15 text-navy' : 'border-line text-muted hover:border-gold'"
                            @click="form.icon = icon"
                        >
                            <AppIcon :name="icon" />
                        </button>
                    </div>
                </div>
                <ToggleField v-model="form.is_active" label="ظاهر للعملاء" />
                <UiButton type="submit" :disabled="form.processing">إضافة التصنيف</UiButton>
            </form>
        </FormSection>

        <div v-if="categories.length" class="app-card-grid xl:grid-cols-2">
            <article v-for="category in categories" :key="category.id" class="app-item-card">
                <div class="flex items-start gap-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-canvas text-navy">
                        <AppIcon :name="category.icon || 'tag'" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-navy">{{ category.name_ar }}</p>
                        <p class="mt-1 text-sm text-muted">{{ category.is_active ? 'ظاهر' : 'مخفي' }}</p>
                    </div>
                </div>
                <p class="border-t border-line pt-3 text-sm text-muted">{{ category.businesses_count }} متجر</p>
            </article>
        </div>
        <EmptyState v-else title="لا توجد تصنيفات" text="أضف تصنيفاً مثل مطاعم أو صيدليات قبل إنشاء المتاجر." />
    </div>
</template>
