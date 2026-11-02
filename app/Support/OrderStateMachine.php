<?php

namespace App\Support;

use App\Enums\OrderStatus;
use Illuminate\Validation\ValidationException;

class OrderStateMachine
{
    public function assertCanTransition(OrderStatus $from, OrderStatus $to): void
    {
        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => __('لا يمكن تحويل الطلب من :from إلى :to.', [
                    'from' => $from->label(),
                    'to' => $to->label(),
                ]),
            ]);
        }
    }

    /**
     * @return list<OrderStatus>
     */
    public function allowedTargets(OrderStatus $from): array
    {
        return $from->allowedTransitions();
    }
}
