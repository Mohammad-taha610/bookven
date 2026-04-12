<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function show(Request $request, Payment $payment)
    {
        $payment->load('booking.court');
        $booking = $payment->booking;
        $user = $request->user();

        if ((int) $booking->user_id === (int) $user->id) {
            return $this->jsonSuccess(new PaymentResource($payment));
        }

        if (! $user->canManageVenues()) {
            return $this->jsonError('Forbidden.', 403);
        }

        if (! $user->canAccessCourt($booking->court)) {
            return $this->jsonError('Forbidden.', 403);
        }

        return $this->jsonSuccess(new PaymentResource($payment));
    }
}
