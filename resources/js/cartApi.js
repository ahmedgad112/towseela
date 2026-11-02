function csrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function cartRequest(url, { method = 'POST', body } = {}) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    const data = await response.json().catch(() => ({}));

    if (! response.ok) {
        const message = data.message
            || Object.values(data.errors || {}).flat()[0]
            || 'تعذر تحديث السلة.';

        throw new Error(message);
    }

    return data;
}

export function addCartItem({ productId, quantity = 1, options = [], notes = '' }) {
    return cartRequest('/cart', {
        method: 'POST',
        body: {
            product_id: productId,
            quantity,
            options,
            notes: notes || null,
        },
    });
}

export function updateCartItemQuantity(itemId, quantity) {
    return cartRequest(`/cart/items/${itemId}`, {
        method: 'PATCH',
        body: { quantity },
    });
}
