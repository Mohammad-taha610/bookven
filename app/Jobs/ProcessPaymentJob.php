<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $paymentId)
    {
        $this->onQueue('payments');
    }

    public function handle(): void
    {
        $payment = Payment::query()->find($this->paymentId);

        if (! $payment || $payment->status !== PaymentStatus::Pending) {
            return;
        }

        // Placeholder for gateway integration: mark completed when webhook succeeds.
        $payment->update([
            'status' => PaymentStatus::Completed,
            'paid_at' => now(),
        ]);
    }
}
