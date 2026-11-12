<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientQueue;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceptionController extends Controller
{
    public function dashboard()
    {
        $today = today();
        $queue = PatientQueue::with('patient')->whereDate('created_at', $today)->orderBy('created_at')->get();
        return view('reception.dashboard', [
            'queue' => $queue,
            'appointments' => Appointment::with('patient')->whereDate('scheduled_at', $today)->orderBy('scheduled_at')->get(),
            'stats' => [
                'expected' => Appointment::whereDate('scheduled_at', $today)->whereIn('status', ['scheduled', 'confirmed'])->count(),
                'waiting' => $queue->where('status', 'waiting')->count(),
                'in_service' => $queue->where('status', 'in_service')->count(),
                'completed' => $queue->where('status', 'completed')->count(),
                'walkins' => $queue->where('is_walkin', true)->count(),
                'no_shows' => $queue->where('status', 'no_show')->count(),
            ],
            'recentPatients' => Patient::latest()->limit(8)->get(),
        ]);
    }

    /** Check-in: appointment-based or walk-in. */
    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'service_label' => 'nullable|string|max:150',
        ]);

        $entry = DB::transaction(function () use ($data) {
            if (PatientQueue::where('patient_id', $data['patient_id'])->whereDate('created_at', today())->whereIn('status', ['waiting', 'called', 'in_service'])->exists()) {
                throw ValidationException::withMessages(['patient' => 'This patient is already in today\'s queue.']);
            }
            $isWalkin = empty($data['appointment_id']);
            if ($isWalkin) {
                $data['service_label'] = $data['service_label'] ?? 'Walk-in';
            }
            return PatientQueue::create([
                'queue_number' => PatientQueue::nextNumber(),
                'patient_id' => $data['patient_id'],
                'appointment_id' => $data['appointment_id'] ?? null,
                'service_label' => $data['service_label'] ?? null,
                'is_walkin' => $isWalkin,
                'status' => 'waiting',
                'checked_in_at' => now(),
                'created_by' => auth()->id(),
            ]);
        });
        if ($entry->appointment) {
            $entry->appointment->update(['status' => 'confirmed']);
        }
        AuditLogger::log('reception.checkin', 'patient_queue', $entry->id);
        return back()->with('success', "Checked in — {$entry->queue_number}");
    }

    /** Advance queue status: waiting→called→in_service→completed | cancelled|no_show. */
    public function updateQueue(Request $request, PatientQueue $patientQueue)
    {
        $data = $request->validate(['action' => 'required|in:call,start,complete,cancel,no_show']);
        $transitions = [
            'call' => ['from' => ['waiting'], 'to' => 'called', 'ts' => 'called_at'],
            'start' => ['from' => ['called', 'waiting'], 'to' => 'in_service', 'ts' => 'started_at'],
            'complete' => ['from' => ['in_service', 'called', 'waiting'], 'to' => 'completed', 'ts' => 'completed_at'],
            'cancel' => ['from' => ['waiting', 'called'], 'to' => 'cancelled', 'ts' => null],
            'no_show' => ['from' => ['waiting', 'called'], 'to' => 'no_show', 'ts' => null],
        ];
        $t = $transitions[$data['action']];
        if (! in_array($patientQueue->status, $t['from'], true)) {
            throw ValidationException::withMessages(['status' => "Cannot {$data['action']} from status {$patientQueue->status}."]);
        }
        $patientQueue->update(['status' => $t['to']] + ($t['ts'] ? [$t['ts'] => now()] : []));
        AuditLogger::log('reception.queue.'.$t['to'], 'patient_queue', $patientQueue->id);
        return back()->with('success', "Queue {$patientQueue->queue_number} → {$t['to']}");
    }
}
