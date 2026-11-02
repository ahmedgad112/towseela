<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import StatusBadge from './StatusBadge.vue';
import UiButton from './UiButton.vue';
import { money } from '../workspace';

const props = defineProps({
    order: { type: Object, required: true },
    mapNote: { type: String, default: '' },
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const brand = computed(() => page.props.platform?.brand?.name_ar || page.props.appName || 'توسيلا');

const paymentMethodLabels = {
    cash_on_delivery: 'الدفع عند الاستلام',
};

const paymentStatusLabels = {
    pending: 'بانتظار التحصيل',
    paid: 'مدفوع',
    failed: 'فشل الدفع',
    refunded: 'مسترد',
};

const enumValue = (value) => (typeof value === 'object' && value !== null ? value.value || value : value);
const enumLabel = (value, labels) => {
    if (typeof value === 'object' && value !== null && value.label) {
        return value.label;
    }

    const key = enumValue(value);

    return labels[key] || key || '—';
};

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

const address = computed(() => props.order.address_snapshot || {});
const paymentMethod = computed(() => enumLabel(props.order.payment_method, paymentMethodLabels));
const paymentStatus = computed(() => enumLabel(props.order.payment_status, paymentStatusLabels));
const createdAt = computed(() => {
    if (!props.order.created_at) {
        return '—';
    }

    return new Date(props.order.created_at).toLocaleString('ar-EG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
});

const addressLines = computed(() => {
    const snapshot = address.value;

    return [
        snapshot.label_text,
        snapshot.line1,
        snapshot.line2,
        [snapshot.city, snapshot.zone].filter(Boolean).join(' · '),
        snapshot.notes ? `ملاحظة العنوان: ${snapshot.notes}` : null,
    ].filter(Boolean);
});

const amount = (value) => money(value, currency.value);

const buildReceiptHtml = () => {
    const items = (props.order.items || []).map((item) => `
        <tr>
            <td class="item">${escapeHtml(item.name)}</td>
            <td class="qty">${escapeHtml(item.quantity)}</td>
            <td class="price">${escapeHtml(amount(item.total))}</td>
        </tr>
    `).join('');

    const customerLine = [
        props.order.customer?.name,
        props.order.customer?.phone,
    ].filter(Boolean).join(' · ');

    const addressLine = addressLines.value.join(' · ');
    const discountRow = Number(props.order.discount) > 0
        ? `<div class="row"><span>خصم</span><span>- ${escapeHtml(amount(props.order.discount))}</span></div>`
        : '';
    const noteLine = props.order.customer_notes
        ? `<div class="note">ملاحظة: ${escapeHtml(props.order.customer_notes)}</div>`
        : '';

    return `<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>إيصال ${escapeHtml(props.order.number)}</title>
<style>
  @page { size: A4 portrait; margin: 10mm; }
  * { box-sizing: border-box; }
  html, body {
    margin: 0;
    padding: 0;
    color: #111;
    background: #fff;
    font-family: Tahoma, "Segoe UI", Arial, sans-serif;
  }
  body { font-size: 11px; line-height: 1.35; }
  .sheet {
    width: 78mm;
    max-width: 100%;
    margin: 0 auto;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .center { text-align: center; }
  .brand { margin: 0; font-size: 15px; font-weight: 800; }
  .meta { margin: 3px 0 0; font-size: 11px; font-weight: 700; }
  .date { margin: 2px 0 0; color: #555; font-size: 10px; }
  .rule {
    border: 0;
    border-top: 1px solid #111;
    margin: 6px 0;
  }
  .rule.dashed { border-top-style: dashed; border-top-color: #777; }
  .row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin: 2px 0;
  }
  .row span:first-child { color: #555; }
  .info { margin: 0; }
  .info div + div { margin-top: 2px; }
  .info .who { font-weight: 700; }
  .info .addr { color: #333; }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  th, td {
    padding: 3px 0;
    vertical-align: top;
  }
  th {
    color: #666;
    font-size: 9px;
    font-weight: 700;
    border-bottom: 1px solid #111;
  }
  td { border-bottom: 1px dashed #ddd; }
  td.item { padding-inline-end: 4px; }
  td.qty { width: 16px; text-align: center; }
  td.price { width: 58px; text-align: left; white-space: nowrap; }
  .total {
    margin-top: 4px;
    padding-top: 4px;
    border-top: 1px solid #111;
    font-size: 12px;
    font-weight: 800;
  }
  .note { margin-top: 5px; color: #333; font-size: 10px; }
  .footer {
    margin-top: 8px;
    text-align: center;
    font-size: 10px;
    color: #555;
  }
</style>
</head>
<body>
  <div class="sheet">
    <div class="center">
      <p class="brand">${escapeHtml(brand.value)}</p>
      <p class="meta">${escapeHtml(props.order.number)}</p>
      <p class="date">${escapeHtml(createdAt.value)}</p>
    </div>

    <hr class="rule">

    <div class="info">
      <div class="who">${escapeHtml(customerLine || '—')}</div>
      ${addressLine ? `<div class="addr">${escapeHtml(addressLine)}</div>` : ''}
      <div>المتجر: ${escapeHtml(props.order.business?.name_ar || '—')}</div>
      <div>الدفع: ${escapeHtml(paymentMethod.value)}</div>
    </div>

    <hr class="rule">

    <table>
      <thead>
        <tr>
          <th style="text-align:right">الصنف</th>
          <th>ك</th>
          <th style="text-align:left">السعر</th>
        </tr>
      </thead>
      <tbody>${items}</tbody>
    </table>

    <hr class="rule dashed">

    ${discountRow}
    <div class="row"><span>توصيل</span><span>${escapeHtml(amount(props.order.delivery_fee))}</span></div>
    <div class="row total"><span>الإجمالي</span><span>${escapeHtml(amount(props.order.grand_total))}</span></div>

    ${noteLine}

    <div class="footer">شكراً لاختياركم ${escapeHtml(brand.value)}</div>
  </div>
</body>
</html>`;
};

const printReceipt = () => {
    const existing = document.getElementById('towseela-order-receipt-frame');

    if (existing) {
        existing.remove();
    }

    const iframe = document.createElement('iframe');
    iframe.id = 'towseela-order-receipt-frame';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
    document.body.appendChild(iframe);

    const frameWindow = iframe.contentWindow;
    const frameDocument = frameWindow?.document;

    if (!frameWindow || !frameDocument) {
        iframe.remove();
        window.alert('تعذر تجهيز الإيصال للطباعة');

        return;
    }

    frameDocument.open();
    frameDocument.write(buildReceiptHtml());
    frameDocument.close();

    const triggerPrint = () => {
        frameWindow.focus();
        frameWindow.print();
        window.setTimeout(() => iframe.remove(), 1000);
    };

    window.setTimeout(triggerPrint, 250);
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="order.status" />
                <span class="text-sm text-muted">{{ createdAt }}</span>
            </div>
            <UiButton variant="outline" class="gap-2" @click="printReceipt">
                <AppIcon name="print" size="sm" />
                طباعة إيصال
            </UiButton>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="app-card space-y-4 p-5">
                <div class="flex items-center gap-2 font-bold text-navy">
                    <span class="grid size-9 place-items-center rounded-2xl bg-navy/5 text-navy">
                        <AppIcon name="user" size="sm" />
                    </span>
                    بيانات العميل
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-muted">الاسم</dt>
                        <dd class="font-semibold text-navy">{{ order.customer?.name || '—' }}</dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-muted">الهاتف</dt>
                        <dd>
                            <a
                                v-if="order.customer?.phone"
                                :href="`tel:${String(order.customer.phone).replace(/[^\d+]/g, '')}`"
                                class="inline-flex items-center gap-1.5 font-semibold text-navy hover:text-gold-hover"
                            >
                                <AppIcon name="phone" size="xs" />
                                {{ order.customer.phone }}
                            </a>
                            <span v-else class="font-semibold text-navy">—</span>
                        </dd>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <dt class="text-xs font-semibold text-muted">البريد</dt>
                        <dd class="font-medium text-ink">{{ order.customer?.email || '—' }}</dd>
                    </div>
                </dl>
                <div class="rounded-2xl border border-line/80 bg-canvas/70 p-4">
                    <div class="mb-2 flex items-center gap-2 text-sm font-bold text-navy">
                        <AppIcon name="pin" size="sm" />
                        عنوان التوصيل
                    </div>
                    <ul class="space-y-1 text-sm leading-6 text-ink">
                        <li v-for="(line, index) in addressLines" :key="index">{{ line }}</li>
                        <li v-if="!addressLines.length" class="text-muted">لا يوجد عنوان محفوظ</li>
                    </ul>
                    <p v-if="mapNote" class="mt-2 text-xs text-muted">{{ mapNote }}</p>
                </div>
                <p v-if="order.customer_notes" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    ملاحظة العميل: {{ order.customer_notes }}
                </p>
            </section>

            <section class="app-card space-y-4 p-5">
                <div class="flex items-center gap-2 font-bold text-navy">
                    <span class="grid size-9 place-items-center rounded-2xl bg-navy/5 text-navy">
                        <AppIcon name="orders" size="sm" />
                    </span>
                    بيانات الطلب
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-muted">رقم الطلب</dt>
                        <dd class="font-semibold text-navy">{{ order.number }}</dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-muted">المتجر</dt>
                        <dd class="font-semibold text-navy">{{ order.business?.name_ar || '—' }}</dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-muted">طريقة الدفع</dt>
                        <dd class="font-medium text-ink">{{ paymentMethod }}</dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-muted">حالة الدفع</dt>
                        <dd class="font-medium text-ink">{{ paymentStatus }}</dd>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <dt class="text-xs font-semibold text-muted">المندوب</dt>
                        <dd class="font-medium text-ink">
                            <template v-if="order.driver?.name">
                                {{ order.driver.name }}
                                <span v-if="order.driver.phone" class="text-muted"> · {{ order.driver.phone }}</span>
                            </template>
                            <template v-else>—</template>
                        </dd>
                    </div>
                </dl>
                <p v-if="order.cancel_reason" class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                    سبب الإلغاء: {{ order.cancel_reason }}
                </p>
                <p v-if="order.failure_reason" class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                    سبب الفشل: {{ order.failure_reason }}
                </p>
            </section>
        </div>

        <section class="app-card overflow-hidden">
            <div class="flex items-center gap-2 border-b border-line px-5 py-4 font-bold text-navy">
                <AppIcon name="bag" size="sm" />
                عناصر الطلب
            </div>
            <div class="divide-y divide-line">
                <article
                    v-for="item in order.items"
                    :key="item.id"
                    class="flex flex-wrap items-start justify-between gap-3 px-5 py-3.5 text-sm"
                >
                    <div class="min-w-0 space-y-1">
                        <p class="font-semibold text-navy">{{ item.quantity }}× {{ item.name }}</p>
                        <p v-if="item.notes" class="text-xs text-muted">{{ item.notes }}</p>
                    </div>
                    <div class="shrink-0 text-left">
                        <p class="font-bold text-navy">{{ money(item.total, currency) }}</p>
                        <p class="text-xs text-muted">{{ money(item.unit_price, currency) }} للوحدة</p>
                    </div>
                </article>
            </div>
            <div class="space-y-2 border-t border-line bg-canvas/50 px-5 py-4 text-sm">
                <div class="flex justify-between text-muted">
                    <span>المجموع الفرعي</span>
                    <span>{{ money(order.subtotal, currency) }}</span>
                </div>
                <div v-if="Number(order.discount) > 0" class="flex justify-between text-muted">
                    <span>الخصم</span>
                    <span>- {{ money(order.discount, currency) }}</span>
                </div>
                <div class="flex justify-between text-muted">
                    <span>رسوم التوصيل</span>
                    <span>{{ money(order.delivery_fee, currency) }}</span>
                </div>
                <div class="flex justify-between border-t border-line pt-2 text-base font-extrabold text-navy">
                    <span>الإجمالي</span>
                    <span>{{ money(order.grand_total, currency) }}</span>
                </div>
            </div>
        </section>
    </div>
</template>
