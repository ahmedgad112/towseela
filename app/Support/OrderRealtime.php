<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OrderRealtime
{
    public const OPS_KEY = 'realtime:orders:ops';

    /**
     * @return array{v: string, at: int, reason: string, order_id: int|null}
     */
    public function ops(): array
    {
        return $this->read(self::OPS_KEY);
    }

    /**
     * @return array{v: string, at: int, reason: string, order_id: int|null}
     */
    public function forDriver(int $driverId): array
    {
        return $this->read($this->driverKey($driverId));
    }

    /**
     * @return array{v: string, at: int, reason: string, order_id: int|null}
     */
    public function forCustomer(int $customerId): array
    {
        return $this->read($this->customerKey($customerId));
    }

    public function orderCreated(Order $order): void
    {
        $this->bumpOps('created', $order->id);
        $this->bumpCustomer((int) $order->user_id, 'created', $order->id);
    }

    public function orderUpdated(Order $order): void
    {
        $this->bumpOps('updated', $order->id);
        $this->bumpCustomer((int) $order->user_id, 'updated', $order->id);

        if ($order->driver_id) {
            $this->bumpDriver((int) $order->driver_id, 'updated', $order->id);
        }
    }

    public function orderAssigned(Order $order, int $driverId): void
    {
        $this->bumpOps('assigned', $order->id);
        $this->bumpDriver($driverId, 'assigned', $order->id);
        $this->bumpCustomer((int) $order->user_id, 'assigned', $order->id);
    }

    private function bumpOps(string $reason, ?int $orderId = null): void
    {
        $this->write(self::OPS_KEY, $reason, $orderId);
    }

    private function bumpDriver(int $driverId, string $reason, ?int $orderId = null): void
    {
        $this->write($this->driverKey($driverId), $reason, $orderId);
    }

    private function bumpCustomer(int $customerId, string $reason, ?int $orderId = null): void
    {
        if ($customerId <= 0) {
            return;
        }

        $this->write($this->customerKey($customerId), $reason, $orderId);
    }

    private function driverKey(int $driverId): string
    {
        return "realtime:orders:driver:{$driverId}";
    }

    private function customerKey(int $customerId): string
    {
        return "realtime:orders:customer:{$customerId}";
    }

    /**
     * @return array{v: string, at: int, reason: string, order_id: int|null}
     */
    private function read(string $key): array
    {
        /** @var array{v?: string, at?: int, reason?: string, order_id?: int|null}|null $payload */
        $payload = Cache::get($key);

        if (! is_array($payload) || blank($payload['v'] ?? null)) {
            return [
                'v' => '0',
                'at' => 0,
                'reason' => 'idle',
                'order_id' => null,
            ];
        }

        return [
            'v' => (string) $payload['v'],
            'at' => (int) ($payload['at'] ?? 0),
            'reason' => (string) ($payload['reason'] ?? 'updated'),
            'order_id' => isset($payload['order_id']) ? (int) $payload['order_id'] : null,
        ];
    }

    private function write(string $key, string $reason, ?int $orderId): void
    {
        Cache::forever($key, [
            'v' => (string) Str::uuid(),
            'at' => now()->getTimestamp(),
            'reason' => $reason,
            'order_id' => $orderId,
        ]);
    }
}
