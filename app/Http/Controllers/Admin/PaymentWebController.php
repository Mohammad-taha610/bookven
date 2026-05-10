<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentWebController extends Controller
{
    public function index()
    {
        $payments = Payment::query()
            ->with(['booking.court', 'booking.user'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.payments.index', compact('payments'));
    }

    public function edit(Payment $payment)
    {
        $payment->load(['booking.court.branch', 'booking.user', 'booking.slot']);
        $methods = PaymentMethod::cases();
        $statuses = PaymentStatus::cases();

        return view('admin.payments.edit', compact('payment', 'methods', 'statuses'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $status = PaymentStatus::from($data['status']);
        if ($status === PaymentStatus::Completed) {
            $data['paid_at'] = ! empty($data['paid_at']) ? $data['paid_at'] : now();
        } else {
            $data['paid_at'] = null;
        }

        $payment->update($data);

        return redirect()->route('admin.payments.index')->with('status', 'Payment updated.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()->route('admin.payments.index')->with('status', 'Payment deleted.');
    }
}
