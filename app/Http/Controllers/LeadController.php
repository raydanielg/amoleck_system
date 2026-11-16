<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    /** Can this user see/manage ALL leads (not just their assigned ones)? */
    private function managesAll(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('leads.assign') || $user->hasPermission('leads.reports');
    }

    /** Scope a query to what the current user may see. */
    private function scoped($query, User $user)
    {
        if (! $this->managesAll($user)) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    private function authorizeLead(Lead $lead, User $user): void
    {
        abort_unless($this->managesAll($user) || $lead->assigned_to === $user->id, 403);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        [$from, $to] = $this->dateRange($request);
        $base = $this->scoped(Lead::query(), $user)->whereBetween('received_at', [$from, $to]);

        $stats = [
            'total' => (clone $base)->count(),
            'contacted' => (clone $base)->where('contact_status', Lead::CONTACT_CONTACTED)->count(),
            'not_contacted' => (clone $base)->where('contact_status', '!=', Lead::CONTACT_CONTACTED)->count(),
            'interested' => (clone $base)->where('interest_level', Lead::INTEREST_INTERESTED)->count(),
            'not_interested' => (clone $base)->where('interest_level', Lead::INTEREST_NOT_INTERESTED)->count(),
            'needs_info' => (clone $base)->where('interest_level', Lead::INTEREST_NEEDS_INFO)->count(),
            'expected' => (clone $base)->where('visit_status', Lead::VISIT_EXPECTED)->count(),
            'confirmed' => (clone $base)->where('visit_status', Lead::VISIT_CONFIRMED)->count(),
            'visited' => (clone $base)->whereIn('visit_status', [Lead::VISIT_VISITED, Lead::VISIT_SERVICE_RECEIVED])->count(),
            'converted' => (clone $base)->whereNotNull('converted_patient_id')->count(),
            'assigned' => (clone $base)->whereNotNull('assigned_to')->count(),
            'unassigned' => (clone $base)->whereNull('assigned_to')->count(),
        ];
        $stats['conversion_rate'] = $stats['total'] > 0 ? round($stats['converted'] / $stats['total'] * 100, 1) : 0;

        $dueToday = $this->scoped(Lead::query(), $user)
            ->where('next_followup_at', '<=', now()->toDateString())
            ->whereNotIn('visit_status', [Lead::VISIT_SERVICE_RECEIVED, Lead::VISIT_NOT_CONVERTED])
            ->orderBy('next_followup_at')->limit(10)->get();

        return view('leads.dashboard', [
            'stats' => $stats, 'dueToday' => $dueToday,
            'from' => $from, 'to' => $to, 'range' => $request->query('range', '30d'),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = $this->scoped(Lead::with('source', 'assignee', 'patient'), $user)->latest('received_at');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('lead_reference', 'like', "%{$search}%"));
        }
        foreach (['visit_status' => 'visit_status', 'contact_status' => 'contact_status', 'lead_source_id' => 'source'] as $param => $col) {
            if ($v = $request->query($param)) {
                $query->where($col === 'source' ? 'lead_source_id' : $col, $v);
            }
        }
        if ($assignee = $request->query('assigned_to')) {
            $query->where('assigned_to', $assignee);
        }

        return view('leads.index', [
            'leads' => $query->paginate(20)->withQueryString(),
            'sources' => LeadSource::where('is_active', true)->orderBy('sort_order')->get(),
            'managers' => $this->salesManagers(),
            'managesAll' => $this->managesAll($user),
        ]);
    }

    public function create()
    {
        abort_unless(request()->user()->hasPermission('leads.create'), 403);

        return view('leads.form', $this->formData());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('leads.create'), 403);
        $data = $this->validateLead($request);

        $lead = DB::transaction(function () use ($data, $user) {
            if (! empty($data['assigned_to'])) {
                $data['assigned_by'] = $user->id;
                $data['assigned_at'] = now();
            }
            $lead = Lead::create([
                ...$data,
                'lead_reference' => Settings::nextNumber('lead_number_prefix', 'lead_number_next', 'LD'),
                'created_by' => $user->id,
            ]);
            $lead->recordActivity('created', 'Lead registered', $user->id);
            if ($lead->assigned_to) {
                $lead->recordActivity('assigned', $lead->assignee->name.' assigned as sales manager', $user->id);
            }

            return $lead;
        });

        if ($lead->assignee) {
            NotificationService::notify($lead->assignee, 'lead.assigned',
                'Lead assigned to you', "{$lead->full_name} ({$lead->lead_reference})", route('leads.show', $lead));
        }

        AuditLogger::log('lead.created', 'lead', $lead->id, null, $lead->only(['lead_reference', 'full_name', 'phone']));

        // Warn about possible duplicates by phone
        $duplicates = Lead::where('phone', $lead->phone)->where('id', '!=', $lead->id)->count()
            + Patient::where('phone', $lead->phone)->count();

        return redirect()->route('leads.show', $lead)
            ->with('success', "Lead {$lead->lead_reference} registered.")
            ->with('warning', $duplicates ? "Note: {$duplicates} existing record(s) share this phone number." : null);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead, $request->user());
        $lead->load(['source', 'assignee', 'assigner', 'patient', 'interestedService', 'followUps.user', 'activities.user']);

        return view('leads.show', [
            'lead' => $lead,
            'managers' => $this->salesManagers(),
            'managesAll' => $this->managesAll($request->user()),
            'patientMatches' => $this->patientMatches($lead),
            'transitions' => Lead::VISIT_TRANSITIONS[$lead->visit_status] ?? [],
            'visitLabels' => Lead::VISIT_LABELS,
        ]);
    }

    public function edit(Request $request, Lead $lead)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('leads.update'), 403);
        $this->authorizeLead($lead, $user);

        return view('leads.form', $this->formData() + ['lead' => $lead]);
    }

    public function update(Request $request, Lead $lead)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('leads.update'), 403);
        $this->authorizeLead($lead, $user);
        $data = $this->validateLead($request);

        $old = $lead->only(array_keys($data));
        $lead->update($data);
        $lead->recordActivity('edited', 'Lead details updated', $user->id);
        AuditLogger::log('lead.updated', 'lead', $lead->id, $old, $lead->only(array_keys($data)));

        return back()->with('success', 'Lead updated.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('leads.assign'), 403);
        $data = $request->validate(['assigned_to' => ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 'active'))]]);

        $old = $lead->assigned_to;
        $lead->update(['assigned_to' => $data['assigned_to'], 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $lead->recordActivity('assigned', $lead->assignee->name.' assigned as sales manager', $user->id, ['from' => $old, 'to' => $data['assigned_to']]);
        AuditLogger::log('lead.assigned', 'lead', $lead->id, ['assigned_to' => $old], ['assigned_to' => $data['assigned_to']]);

        NotificationService::notify($lead->assignee, 'lead.assigned',
            'Lead assigned to you', "{$lead->full_name} ({$lead->lead_reference})", route('leads.show', $lead));

        return back()->with('success', 'Lead assigned to '.$lead->assignee->name.'.');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $user = $request->user();
        $this->authorizeLead($lead, $user);
        $data = $request->validate([
            'visit_status' => ['required', Rule::in(array_keys(Lead::VISIT_LABELS))],
            'notes' => 'nullable|string|max:1000',
        ]);
        abort_unless($lead->canTransitionTo($data['visit_status']), 422, 'Invalid status transition.');

        $from = $lead->visit_status;
        $lead->update(['visit_status' => $data['visit_status']]);
        $lead->recordActivity('status_change', 'Status: '.Lead::VISIT_LABELS[$from].' → '.Lead::VISIT_LABELS[$data['visit_status']], $user->id, ['notes' => $data['notes'] ?? null]);
        AuditLogger::log('lead.status_changed', 'lead', $lead->id, ['visit_status' => $from], ['visit_status' => $data['visit_status']]);

        return back()->with('success', 'Lead status updated.');
    }

    public function storeFollowUp(Request $request, Lead $lead)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('leads.followup'), 403);
        $this->authorizeLead($lead, $user);

        $data = $request->validate([
            'reached' => 'required|boolean',
            'interest_level' => ['nullable', Rule::in([Lead::INTEREST_INTERESTED, Lead::INTEREST_NOT_INTERESTED, Lead::INTEREST_NEEDS_INFO])],
            'outcome' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'next_followup_at' => 'nullable|date',
        ]);

        DB::transaction(function () use ($data, $lead, $user) {
            $lead->followUps()->create([...$data, 'user_id' => $user->id, 'contacted_at' => now()]);

            $updates = [
                'contact_status' => $data['reached'] ? Lead::CONTACT_CONTACTED : Lead::CONTACT_NOT_CONTACTED,
                'next_followup_at' => $data['next_followup_at'] ?? null,
            ];
            if ($data['interest_level']) {
                $updates['interest_level'] = $data['interest_level'];
            }
            $lead->update($updates);

            $lead->recordActivity('follow_up', 'Follow-up recorded: '.($data['reached'] ? 'reached' : 'not reached'), $user->id);
        });

        return back()->with('success', 'Follow-up recorded.');
    }

    public function convert(Request $request, Lead $lead)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('leads.convert'), 403);
        $this->authorizeLead($lead, $user);
        abort_if($lead->isConverted(), 422, 'This lead is already linked to a patient.');

        $data = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'create_patient' => 'nullable|boolean',
            'sex' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
        ]);

        $patient = DB::transaction(function () use ($data, $lead, $user) {
            if (! empty($data['patient_id'])) {
                $patient = Patient::findOrFail($data['patient_id']);
            } else {
                abort_unless($data['create_patient'] ?? false, 422, 'Select an existing patient or choose to create a new one.');
                $patient = Patient::create([
                    'patient_number' => Settings::nextNumber('patient_number_prefix', 'patient_number_next', 'PT'),
                    'full_name' => $lead->full_name,
                    'phone' => $lead->phone,
                    'sex' => $data['sex'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'status' => Patient::STATUS_ACTIVE,
                    'created_by' => $user->id,
                ]);
                AuditLogger::log('patient.created', 'patient', $patient->id, null, $patient->only(['patient_number', 'full_name', 'phone']));
            }

            $lead->update([
                'converted_patient_id' => $patient->id,
                'converted_at' => now(),
                'visit_status' => Lead::VISIT_VISITED,
            ]);
            $lead->recordActivity('converted', 'Converted to patient '.$patient->patient_number, $user->id);
            AuditLogger::log('lead.converted', 'lead', $lead->id, null, ['patient_id' => $patient->id]);

            return $patient;
        });

        NotificationService::notifyAdmins('lead.converted',
            'Lead converted to patient', "{$lead->full_name} → {$patient->patient_number}", route('leads.show', $lead));

        return redirect()->route('leads.show', $lead)->with('success', 'Lead converted and linked to patient '.$patient->patient_number.'.');
    }

    public function destroy(Request $request, Lead $lead)
    {
        abort_unless($request->user()->hasPermission('leads.delete'), 403);
        $ref = $lead->lead_reference;
        $lead->delete();
        AuditLogger::log('lead.deleted', 'lead', $lead->id, ['reference' => $ref, 'name' => $lead->full_name], null);

        return redirect()->route('leads.index')->with('success', "Lead {$ref} deleted.");
    }

    public function reports(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('leads.reports'), 403);
        [$from, $to] = $this->dateRange($request);

        $base = Lead::query()->whereBetween('received_at', [$from, $to]);
        $bySource = (clone $base)->selectRaw('lead_source_id, count(*) as total, sum(converted_patient_id is not null) as converted')
            ->groupBy('lead_source_id')->with('source')->get();

        $managerPerf = User::whereIn('id', (clone $base)->whereNotNull('assigned_to')->select('assigned_to')->distinct())
            ->get()->map(function ($m) use ($base) {
                $leads = (clone $base)->where('assigned_to', $m->id);
                $total = (clone $leads)->count();
                $converted = (clone $leads)->whereNotNull('converted_patient_id')->count();

                return [
                    'manager' => $m, 'total' => $total,
                    'contacted' => (clone $leads)->where('contact_status', Lead::CONTACT_CONTACTED)->count(),
                    'interested' => (clone $leads)->where('interest_level', Lead::INTEREST_INTERESTED)->count(),
                    'visited' => (clone $leads)->whereIn('visit_status', [Lead::VISIT_VISITED, Lead::VISIT_SERVICE_RECEIVED])->count(),
                    'converted' => $converted,
                    'overdue' => (clone $leads)->where('next_followup_at', '<', now()->toDateString())
                        ->whereNotIn('visit_status', [Lead::VISIT_SERVICE_RECEIVED, Lead::VISIT_NOT_CONVERTED])->count(),
                    'rate' => $total > 0 ? round($converted / $total * 100, 1) : 0,
                ];
            });

        return view('leads.reports', [
            'bySource' => $bySource, 'managerPerf' => $managerPerf,
            'from' => $from, 'to' => $to, 'range' => $request->query('range', '30d'),
        ]);
    }

    public function storeSource(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:100|unique:lead_sources,name']);
        LeadSource::create([...$data, 'sort_order' => LeadSource::max('sort_order') + 1]);

        return back()->with('success', 'Lead source added.');
    }

    public function toggleSource(Request $request, LeadSource $leadSource)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $leadSource->update(['is_active' => ! $leadSource->is_active]);

        return back()->with('success', 'Lead source '.($leadSource->is_active ? 'enabled' : 'disabled').'.');
    }

    // ─── helpers ─────────────────────────────────────────────────────────

    private function dateRange(Request $request): array
    {
        $range = $request->query('range', '30d');

        return match ($range) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'yesterday' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            '7d' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'last_month' => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
            'custom' => [$request->query('from', now()->subDays(29)->toDateString()), $request->query('to', now()->toDateString())],
            default => [now()->subDays(29)->toDateString(), now()->toDateString()],
        };
    }

    private function salesManagers()
    {
        return User::where('status', 'active')
            ->whereIn('role', [User::ROLE_SALES_MANAGER, User::ROLE_ADMIN])
            ->orderBy('name')->get();
    }

    private function patientMatches(Lead $lead)
    {
        return Patient::where('phone', $lead->phone)
            ->orWhere('full_name', 'like', '%'.str_replace(' ', '%', $lead->full_name).'%')
            ->limit(10)->get();
    }

    private function validateLead(Request $request): array
    {
        return $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'campaign_name' => 'nullable|string|max:255',
            'ad_name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:100',
            'interested_service_id' => 'nullable|exists:services,id',
            'description' => 'nullable|string|max:2000',
            'initial_notes' => 'nullable|string|max:2000',
            'received_at' => 'required|date',
            'expected_visit_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
    }

    private function formData(): array
    {
        return [
            'lead' => null,
            'sources' => LeadSource::where('is_active', true)->orderBy('sort_order')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'managers' => $this->salesManagers(),
        ];
    }
}
