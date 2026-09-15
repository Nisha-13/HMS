<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Appointment;
use App\Models\OpdQueue;
use App\Jobs\ProcessBillingInvoiceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::with(['patient.patientProfile', 'appointment.doctor', 'items']);

        if ($user->role === 'patient') {
            $query->where('patient_id', $user->id);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
            'summary' => [
                'total_revenue' => Invoice::where('payment_status', 'paid')->sum('paid_amount'),
                'pending_invoices' => Invoice::where('payment_status', 'unpaid')->count(),
            ]
        ]);
    }

    public function markAsPaid(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->payment_status = 'paid';
        $invoice->paid_amount = $invoice->total_amount;
        $invoice->paid_at = now();
        $invoice->payment_method = $request->payment_method ?? 'cash';
        $invoice->save();

        // If invoice is attached to an appointment, activate appointment and queue token
        if ($invoice->appointment_id) {
            $appointment = Appointment::with('opdQueue')->find($invoice->appointment_id);
            if ($appointment) {
                $appointment->payment_status = 'paid';
                $appointment->save();

                if ($appointment->opdQueue && $appointment->opdQueue->status === 'pending_checkin') {
                    $appointment->opdQueue->status = 'waiting';
                    $appointment->opdQueue->save();
                }
            }
        }

        // Dispatch background Queue Job for invoice pdf / accounting log
        ProcessBillingInvoiceJob::dispatch($invoice);

        return response()->json([
            'success' => true,
            'message' => "Invoice #{$invoice->invoice_no} marked as PAID.",
            'invoice' => $invoice->load('items')
        ]);
    }
}

