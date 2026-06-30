<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentType;
use App\Enums\DepositStatus;
use App\Enums\EstimateStatus;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\MaterialType;
use App\Http\Requests\StoreAssemblyRequest;
use App\Http\Requests\StoreEstimateRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\StoreMaterialRequest;
use App\Models\ActivityLog;
use App\Models\Assembly;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Job;
use App\Models\JobPacket;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Material;
use App\Services\CompanySettingsService;
use App\Services\EstimateCalculator;
use App\Services\EstimateSendService;
use App\Services\LeadStatusWorkflow;
use App\Services\MoneyCalculator;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BidscapeController extends Controller
{
    public function dashboard(ReportService $reports, LeadStatusWorkflow $leadStatuses): Response
    {
        $company = $this->company();
        $leadStatuses->syncCompany($company);

        return Inertia::render('Dashboard', [
            'metrics' => $reports->dashboard($company),
            'pipeline' => $this->pipeline($company),
            'activities' => $this->activities($company),
            'trend' => $this->contractTrend($company),
            'quickStats' => $this->quickStats($company),
        ]);
    }

    public function leads(Request $request, LeadStatusWorkflow $leadStatuses): Response
    {
        $company = $this->company();
        $leadStatuses->syncCompany($company);
        $query = Lead::with('source')->where('company_id', $company->id);

        $this->applySearch($query, $request, ['name', 'email', 'phone', 'site_address']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('source')) {
            $query->where('lead_source_id', $request->integer('source'));
        }

        $this->applySort($query, $request, [
            'name' => 'name',
            'contact' => 'phone',
            'source' => 'lead_source_id',
            'status' => 'status',
            'created' => 'created_at',
            'next_action' => 'site_visit_scheduled_at',
        ], 'created_at', 'desc');

        return Inertia::render('Leads/Index', [
            'leads' => $query->paginate(8)->withQueryString()->through(fn (Lead $lead) => $this->leadRow($lead)),
            'sources' => LeadSource::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'source', 'sort', 'direction']),
            'statuses' => collect(LeadStatus::cases())
                ->map(fn (LeadStatus $status) => ['value' => $status->value, 'label' => $status->label(), 'color' => $status->color()])
                ->values(),
            'kpis' => [
                ['label' => 'Pending Contact', 'value' => Lead::where('company_id', $company->id)->where('status', LeadStatus::PendingContact)->count(), 'trend' => 'Needs first contact'],
                ['label' => 'Site Visit', 'value' => Lead::where('company_id', $company->id)->where('status', LeadStatus::SiteVisit)->count(), 'trend' => 'Scheduled visits'],
                ['label' => 'Pending Estimate', 'value' => Lead::where('company_id', $company->id)->where('status', LeadStatus::PendingEstimate)->count(), 'trend' => 'Needs pricing'],
                ['label' => 'Estimate Sent', 'value' => Lead::where('company_id', $company->id)->where('status', LeadStatus::EstimateSent)->count(), 'trend' => 'Awaiting response'],
                ['label' => 'Approved', 'value' => Lead::where('company_id', $company->id)->where('status', LeadStatus::Approved)->count(), 'trend' => 'Converted to customers'],
                ['label' => 'Closed', 'value' => Lead::where('company_id', $company->id)->where('status', LeadStatus::Closed)->count(), 'trend' => 'No follow-up'],
            ],
        ]);
    }

    public function storeLead(StoreLeadRequest $request, LeadStatusWorkflow $leadStatuses): RedirectResponse
    {
        $validated = $request->safe()->except(['status']);

        Lead::create([
            ...$validated,
            'company_id' => $this->company()->id,
            'assigned_user_id' => $request->user()->id,
            'status' => $leadStatuses->preview($validated['site_visit_scheduled_at'] ?? null),
        ]);

        return back()->with('success', 'Lead created.');
    }

    public function updateLead(StoreLeadRequest $request, Lead $lead, LeadStatusWorkflow $leadStatuses): RedirectResponse
    {
        $this->ensureCompany($lead);
        $validated = $request->validated();

        $lead->forceFill([
            ...collect($validated)->except(['status'])->all(),
        ])->save();

        $leadStatuses->sync($lead->refresh());

        return back()->with('success', 'Lead updated.');
    }

    public function customers(Request $request): Response
    {
        $company = $this->company();
        $query = Customer::where('company_id', $company->id);
        $this->applySearch($query, $request, ['name', 'email', 'phone']);
        $this->applySort($query, $request, [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'last_activity' => 'last_activity_at',
        ], 'last_activity_at', 'desc');

        $customers = $query->paginate(8)->withQueryString()->through(fn (Customer $customer) => $this->customerRow($customer));
        $jobs = Job::where('company_id', $company->id);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'sources' => LeadSource::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'kpis' => [
                ['label' => 'Total Customers', 'value' => Customer::where('company_id', $company->id)->count(), 'trend' => 'All time'],
                ['label' => 'Active Customers', 'value' => Customer::where('company_id', $company->id)->whereHas('jobs')->count(), 'trend' => 'Signed work'],
                ['label' => 'Total Sold Jobs', 'value' => (clone $jobs)->count(), 'trend' => 'Across customers'],
                ['label' => 'Lifetime Value', 'value' => (clone $jobs)->sum('contract_value_cents'), 'trend' => 'All time', 'money' => true],
                ['label' => 'Avg. Job Value', 'value' => (int) round((clone $jobs)->avg('contract_value_cents') ?? 0), 'trend' => 'Per customer', 'money' => true],
            ],
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        Customer::create([
            ...$this->validateCustomer($request),
            'company_id' => $this->company()->id,
            'last_activity_at' => now(),
        ]);

        return back()->with('success', 'Customer created.');
    }

    public function updateCustomer(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureCompany($customer);
        $customer->forceFill($this->validateCustomer($request))->save();

        return back()->with('success', 'Customer updated.');
    }

    public function estimates(Request $request): Response
    {
        $company = $this->company();
        $query = Estimate::with(['customer', 'lead'])->where('company_id', $company->id);
        $this->applySearch($query, $request, ['estimate_number', 'project_name']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $this->applySort($query, $request, [
            'number' => 'estimate_number',
            'project' => 'project_name',
            'status' => 'status',
            'total_cents' => 'selling_price_cents',
            'created' => 'created_at',
            'updated' => 'updated_at',
        ], 'created_at', 'desc');

        return Inertia::render('Estimates/Index', [
            'estimates' => $query->paginate(8)->withQueryString()->through(fn (Estimate $estimate) => [
                'id' => $estimate->id,
                'number' => $estimate->estimate_number,
                'customer' => $this->estimateClientName($estimate),
                'project' => $estimate->project_name,
                'status' => $estimate->status->label(),
                'status_value' => $estimate->status->value,
                'total_cents' => $estimate->selling_price_cents,
                'created' => $estimate->created_at->format('M j, Y'),
                'updated' => $estimate->updated_at->format('M j, Y'),
                'next_action' => $this->estimateNextAction($estimate),
            ]),
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
            'statuses' => collect(EstimateStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]),
            'customers' => Customer::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'leads' => Lead::where('company_id', $company->id)->active()->orderBy('name')->get(['id', 'name']),
            'kpis' => [
                ['label' => 'Total Estimate Value', 'value' => Estimate::where('company_id', $company->id)->sum('selling_price_cents'), 'money' => true],
                ['label' => 'Emailed', 'value' => Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Emailed)->count()],
                ['label' => 'Awaiting Signature', 'value' => Estimate::where('company_id', $company->id)->whereIn('status', [EstimateStatus::Approved, EstimateStatus::SignaturePending])->count()],
                ['label' => 'Draft', 'value' => Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Draft)->count()],
                ['label' => 'Signed', 'value' => Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Signed)->count()],
            ],
        ]);
    }

    public function storeEstimate(StoreEstimateRequest $request, CompanySettingsService $settings): RedirectResponse
    {
        $company = $this->company();
        $settings->ensureDefaults($company);
        $validated = $request->validated();
        $next = Estimate::where('company_id', $company->id)->count() + 1;
        $estimateTemplate = $settings->templateFor($company, 'estimate');

        if (! empty($validated['lead_id'])) {
            $lead = Lead::findOrFail($validated['lead_id']);
            $this->ensureCompany($lead);
            $validated['scope_summary'] ??= $lead->requested_project_specifications ?: $lead->project_interest;
        }

        if (! empty($validated['customer_id'])) {
            $this->ensureCompany(Customer::findOrFail($validated['customer_id']));
        }

        $estimate = Estimate::create([
            ...$validated,
            'company_id' => $company->id,
            'estimate_number' => 'EST-'.now()->format('Y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT),
            'status' => EstimateStatus::Draft,
            'overhead_basis_points' => $company->default_overhead_basis_points,
            'target_margin_basis_points' => $company->default_target_margin_basis_points,
            'terms' => $estimateTemplate->terms_text ?: CompanySettingsService::DEFAULT_ESTIMATE_TERMS,
        ]);

        return to_route('estimates.builder', $estimate)->with('success', 'Estimate created.');
    }

    public function estimateBuilder(Estimate $estimate): Response
    {
        $this->ensureCompany($estimate);
        $estimate->load('customer', 'lead', 'items.assembly', 'items.material', 'paymentTerm', 'attachments');

        return Inertia::render('Estimates/Builder', [
            'estimate' => $this->estimateDetail($estimate),
            'assemblies' => Assembly::where('company_id', $estimate->company_id)->withCount('components')->orderBy('name')->get()->map(fn (Assembly $assembly) => $this->assemblyRow($assembly)),
            'materials' => Material::where('company_id', $estimate->company_id)->where('is_active', true)->orderBy('name')->get()->map(fn (Material $material) => $this->materialRow($material)),
            'itemTypes' => [
                ['value' => 'assembly', 'label' => 'Assembly'],
                ['value' => 'material', 'label' => 'Material / Component'],
                ['value' => 'labor', 'label' => 'Labor'],
                ['value' => 'equipment', 'label' => 'Equipment'],
                ['value' => 'custom', 'label' => 'Custom'],
            ],
        ]);
    }

    public function addEstimateItem(Request $request, Estimate $estimate, EstimateCalculator $calculator): RedirectResponse
    {
        $this->ensureCompany($estimate);

        $validated = $request->validate([
            'item_type' => ['required', 'in:assembly,material,labor,equipment,custom'],
            'assembly_id' => ['nullable', 'integer', 'exists:assemblies,id', 'required_if:item_type,assembly'],
            'material_id' => ['nullable', 'integer', 'exists:materials,id', 'required_if:item_type,material'],
            'name' => ['nullable', 'string', 'max:255', 'required_if:item_type,custom,labor,equipment'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:40', 'required_if:item_type,custom,labor,equipment'],
            'unit_price_cents' => ['nullable', 'integer', 'min:0', 'required_if:item_type,custom,labor,equipment'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = $this->makeEstimateItemPayload($estimate, $validated);

        EstimateItem::create([
            ...$item,
            'estimate_id' => $estimate->id,
            'quantity' => (string) $validated['quantity'],
            'notes' => $validated['notes'] ?? null,
            'sort_order' => $estimate->items()->count() + 1,
        ]);

        $calculator->recalculate($estimate->load('items'));

        if ($estimate->status === EstimateStatus::Draft) {
            $estimate->forceFill(['status' => EstimateStatus::InReview])->save();
        }

        return back()->with('success', 'Scope item added.');
    }

    public function sendEstimate(Request $request, Estimate $estimate, EstimateSendService $sender): RedirectResponse
    {
        $this->ensureCompany($estimate);

        $validated = $request->validate([
            'recipient' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $estimate->loadMissing('customer', 'lead');
        $recipient = $validated['recipient'] ?? $estimate->customer?->email ?? $estimate->lead?->email;

        if (! $recipient) {
            throw ValidationException::withMessages(['recipient' => 'Add a recipient email before sending.']);
        }

        $sender->send($estimate, $recipient, $validated['subject'] ?? null, $validated['message'] ?? null);

        return back()->with('success', 'Estimate PDF emailed with public review link.');
    }

    public function jobs(Request $request): Response
    {
        $company = $this->company();
        $query = Job::with('customer')->where('company_id', $company->id);
        $this->applySearch($query, $request, ['job_number', 'project_name']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $this->applySort($query, $request, [
            'number' => 'job_number',
            'project' => 'project_name',
            'status' => 'status',
            'contract_value_cents' => 'contract_value_cents',
            'signed' => 'contract_signed_at',
            'updated' => 'updated_at',
        ], 'contract_signed_at', 'desc');

        return Inertia::render('Jobs/Index', [
            'jobs' => $query->paginate(8)->withQueryString()->through(fn (Job $job) => [
                'id' => $job->id,
                'number' => $job->job_number,
                'customer' => $job->customer->name,
                'project' => $job->project_name,
                'status' => $job->status->label(),
                'status_value' => $job->status->value,
                'contract_value_cents' => $job->contract_value_cents,
                'signed' => $job->contract_signed_at?->format('M j, Y'),
                'deposit_status' => $job->deposit_status->value,
                'updated' => $job->updated_at->format('M j, Y'),
                'next_action' => $job->next_action ?: 'Review Job Packet',
            ]),
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
            'statuses' => collect(JobStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]),
            'kpis' => [
                ['label' => 'Sold Jobs', 'value' => Job::where('company_id', $company->id)->count(), 'trend' => 'Created by signed estimates'],
                ['label' => 'Signature Pending', 'value' => Estimate::where('company_id', $company->id)->whereIn('status', [EstimateStatus::Emailed, EstimateStatus::Approved, EstimateStatus::SignaturePending])->count(), 'trend' => 'Customer review links'],
                ['label' => 'Job Packets Ready', 'value' => Job::where('company_id', $company->id)->where('status', JobStatus::PacketReady)->count(), 'trend' => 'Ready for handoff'],
                ['label' => 'Contract Value This Month', 'value' => Job::where('company_id', $company->id)->whereMonth('contract_signed_at', now()->month)->sum('contract_value_cents'), 'money' => true],
                ['label' => 'Deposits Received', 'value' => Job::where('company_id', $company->id)->where('deposit_status', DepositStatus::Paid)->count(), 'trend' => 'Internally recorded'],
            ],
        ]);
    }

    public function jobPacket(Job $job): Response
    {
        $this->ensureCompany($job);
        $job->load('customer', 'packet.attachments', 'deposits', 'contract');
        $items = collect($job->accepted_snapshot['items'] ?? []);

        return Inertia::render('Jobs/Packet', [
            'job' => [
                'id' => $job->id,
                'number' => $job->job_number,
                'project' => $job->project_name,
                'status' => $job->status->label(),
                'status_value' => $job->status->value,
                'contract_value_cents' => $job->contract_value_cents,
                'contract_signed' => $job->contract_signed_at?->format('M j, Y'),
                'site_address' => $job->site_address,
                'site_notes' => $job->site_notes,
                'snapshot' => $job->accepted_snapshot,
                'materials' => $items->filter(fn ($item) => in_array($item['item_type'] ?? 'assembly', ['assembly', 'material', 'custom'], true))->values(),
                'labor' => $items->filter(fn ($item) => ($item['item_type'] ?? '') === 'labor')->values(),
                'equipment' => $items->filter(fn ($item) => ($item['item_type'] ?? '') === 'equipment')->values(),
                'customer' => $job->customer->only(['name', 'email', 'phone']),
                'deposit_paid_cents' => $job->deposits->where('status', DepositStatus::Paid)->sum('amount_cents'),
                'balance_due_cents' => max(0, $job->contract_value_cents - $job->deposits->where('status', DepositStatus::Paid)->sum('amount_cents')),
                'packet' => $job->packet,
                'attachments' => $job->packet?->attachments->map(fn (Attachment $attachment) => [
                    'id' => $attachment->id,
                    'name' => $attachment->display_name,
                    'type' => $attachment->type->value,
                    'size_bytes' => $attachment->size_bytes,
                ])->values() ?? [],
            ],
        ]);
    }

    public function assemblies(Request $request): Response
    {
        $company = $this->company();
        $query = Assembly::where('company_id', $company->id)->withCount('components');
        $this->applySearch($query, $request, ['name', 'category', 'description']);
        $this->applySort($query, $request, [
            'name' => 'name',
            'category' => 'category',
            'unit' => 'unit',
            'base_cost_cents' => 'base_cost_cents',
            'markup_basis_points' => 'markup_basis_points',
        ], 'name', 'asc');
        $assemblies = $query->paginate(8)->withQueryString();
        $selected = $assemblies->getCollection()->first();

        return Inertia::render('Assemblies/Index', [
            'assemblies' => $assemblies->through(fn (Assembly $assembly) => $this->assemblyRow($assembly)),
            'selected' => $selected ? $this->assemblyDetail($selected->load('components.material')) : null,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function storeAssembly(StoreAssemblyRequest $request): RedirectResponse
    {
        $company = $this->company();
        $assembly = Assembly::create([
            ...$request->validated(),
            'company_id' => $company->id,
            'overhead_basis_points' => $company->default_overhead_basis_points,
            'target_margin_basis_points' => $company->default_target_margin_basis_points,
        ]);

        $this->repriceAssembly($assembly);

        return back()->with('success', 'Assembly created.');
    }

    public function updateAssembly(StoreAssemblyRequest $request, Assembly $assembly): RedirectResponse
    {
        $this->ensureCompany($assembly);
        $assembly->forceFill($request->validated())->save();
        $this->repriceAssembly($assembly);

        return back()->with('success', 'Assembly updated.');
    }

    public function assemblyFormula(Assembly $assembly): Response
    {
        $this->ensureCompany($assembly);
        $assembly->load('components.material');

        return Inertia::render('Assemblies/Formula', [
            'assembly' => $this->assemblyDetail($assembly),
        ]);
    }

    public function materials(Request $request): Response
    {
        $company = $this->company();
        $query = Material::where('company_id', $company->id);
        $this->applySearch($query, $request, ['name', 'sku', 'category', 'vendor']);
        $this->applySort($query, $request, [
            'name' => 'name',
            'type_label' => 'type',
            'category' => 'category',
            'unit' => 'unit',
            'unit_cost_cents' => 'unit_cost_cents',
            'markup_basis_points' => 'markup_basis_points',
            'selling_price_cents' => 'selling_price_cents',
        ], 'name', 'asc');
        $materials = $query->paginate(8)->withQueryString();
        $selected = $materials->getCollection()->first();

        return Inertia::render('Materials/Index', [
            'materials' => $materials->through(fn (Material $material) => $this->materialRow($material)),
            'selected' => $selected ? $this->materialRow($selected) : null,
            'types' => collect(MaterialType::cases())->map(fn (MaterialType $type) => ['value' => $type->value, 'label' => $type->label()]),
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function storeMaterial(StoreMaterialRequest $request): RedirectResponse
    {
        Material::create([
            ...$this->materialPayload($request),
            'company_id' => $this->company()->id,
        ]);

        return back()->with('success', 'Material created.');
    }

    public function updateMaterial(StoreMaterialRequest $request, Material $material): RedirectResponse
    {
        $this->ensureCompany($material);
        $material->forceFill($this->materialPayload($request))->save();

        return back()->with('success', 'Material updated.');
    }

    public function reports(ReportService $reports, LeadStatusWorkflow $leadStatuses): Response
    {
        $company = $this->company();
        $leadStatuses->syncCompany($company);

        return Inertia::render('Reports/Index', [
            'sales' => $reports->salesSummary($company),
            'sources' => $reports->leadSourceReport($company),
            'conversion' => $reports->estimateConversion($company),
            'dashboard' => $reports->dashboard($company),
        ]);
    }

    public function storeAttachment(Request $request): RedirectResponse
    {
        $company = $this->company();
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'attachable_type' => ['required', 'in:lead,customer,estimate,job_packet,material,assembly'],
            'attachable_id' => ['required', 'integer'],
            'type' => ['nullable', new Enum(AttachmentType::class)],
            'display_name' => ['nullable', 'string', 'max:255'],
        ]);

        $model = $this->attachable($validated['attachable_type'], (int) $validated['attachable_id']);
        $this->ensureCompany($model);

        $file = $validated['file'];
        $path = $file->store('attachments/'.$validated['attachable_type'], 'local');

        Attachment::create([
            'company_id' => $company->id,
            'user_id' => $request->user()->id,
            'attachable_type' => $model::class,
            'attachable_id' => $model->id,
            'original_filename' => $file->getClientOriginalName(),
            'display_name' => $validated['display_name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'extension' => $file->extension(),
            'type' => $validated['type'] ?? AttachmentType::Document,
            'size_bytes' => Storage::disk('local')->size($path),
        ]);

        return back()->with('success', 'Attachment uploaded.');
    }

    private function company(): Company
    {
        return request()->user()?->company ?: Company::firstOrFail();
    }

    private function applySearch(Builder $query, Request $request, array $columns): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = '%'.$request->string('search')->toString().'%';
        $query->where(function (Builder $query) use ($columns, $search): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', $search);
            }
        });
    }

    /**
     * @param  array<string, string>  $allowed
     */
    private function applySort(Builder $query, Request $request, array $allowed, string $defaultColumn, string $defaultDirection = 'asc'): void
    {
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'desc' ? 'desc' : 'asc';

        if ($sort !== '' && array_key_exists($sort, $allowed)) {
            $query->orderBy($allowed[$sort], $direction);

            return;
        }

        $query->orderBy($defaultColumn, $defaultDirection);
    }

    private function pipeline(Company $company): array
    {
        return [
            ['label' => 'Leads', 'count' => Lead::where('company_id', $company->id)->active()->count(), 'value_cents' => Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Draft)->sum('selling_price_cents')],
            ['label' => 'Active Estimates', 'count' => Estimate::where('company_id', $company->id)->active()->count(), 'value_cents' => Estimate::where('company_id', $company->id)->active()->sum('selling_price_cents')],
            ['label' => 'Emailed', 'count' => Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Emailed)->count(), 'value_cents' => Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Emailed)->sum('selling_price_cents')],
            ['label' => 'Sold', 'count' => Job::where('company_id', $company->id)->count(), 'value_cents' => Job::where('company_id', $company->id)->sum('contract_value_cents')],
        ];
    }

    private function activities(Company $company): array
    {
        return ActivityLog::where('company_id', $company->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $activity) => [
                'event' => $activity->event->value,
                'description' => $activity->description,
                'time' => $activity->created_at->format('g:i A'),
            ])
            ->all();
    }

    private function contractTrend(Company $company): array
    {
        return collect(range(5, 0))->map(function (int $monthsAgo) use ($company) {
            $date = now()->subMonths($monthsAgo);

            return [
                'label' => $date->format('M'),
                'value_cents' => Job::where('company_id', $company->id)
                    ->whereMonth('contract_signed_at', $date->month)
                    ->whereYear('contract_signed_at', $date->year)
                    ->sum('contract_value_cents'),
            ];
        })->values()->all();
    }

    private function quickStats(Company $company): array
    {
        return [
            ['label' => 'Total Pipeline Value', 'value_cents' => Estimate::where('company_id', $company->id)->sum('selling_price_cents')],
            ['label' => 'Active Estimates', 'value' => Estimate::where('company_id', $company->id)->active()->count()],
            ['label' => 'Emailed Estimates', 'value' => Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Emailed)->count()],
            ['label' => 'Sold This Month', 'value_cents' => Job::where('company_id', $company->id)->whereMonth('contract_signed_at', now()->month)->sum('contract_value_cents')],
            ['label' => 'Avg. Sales Cycle', 'value' => '12 days'],
            ['label' => 'Win Rate', 'value' => '42%'],
        ];
    }

    private function leadRow(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'contact' => $lead->phone ?: $lead->email,
            'lead_source_id' => $lead->lead_source_id,
            'source' => $lead->source?->name,
            'status' => $lead->status->label(),
            'status_value' => $lead->status->value,
            'status_color' => $lead->status->color(),
            'site_address' => $lead->site_address,
            'city' => $lead->city,
            'state' => $lead->state,
            'postal_code' => $lead->postal_code,
            'contact_preference' => $lead->contact_preference,
            'project_interest' => $lead->project_interest,
            'requested_project_specifications' => $lead->requested_project_specifications,
            'site_notes' => $lead->site_notes,
            'internal_notes' => $lead->internal_notes,
            'gate_code' => $lead->gate_code,
            'site_visit_scheduled_at' => $lead->site_visit_scheduled_at?->format('Y-m-d\TH:i'),
            'next_follow_up_at' => $lead->next_follow_up_at?->format('Y-m-d\TH:i'),
            'lost_reason' => $lead->lost_reason,
            'created' => $lead->created_at->format('M j, Y'),
            'next_action' => $this->leadNextAction($lead),
            'next_action_at' => $lead->site_visit_scheduled_at?->format('M j, g:i A') ?? $lead->next_follow_up_at?->format('M j, g:i A'),
        ];
    }

    private function leadNextAction(Lead $lead): string
    {
        return match ($lead->status) {
            LeadStatus::PendingContact => 'Contact lead',
            LeadStatus::SiteVisit => 'Complete site visit',
            LeadStatus::PendingEstimate => 'Prepare estimate',
            LeadStatus::EstimateSent => 'Await response',
            LeadStatus::Approved => 'Review job packet',
            LeadStatus::Closed => 'Closed',
        };
    }

    private function customerRow(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'initials' => collect(explode(' ', $customer->name))->map(fn ($part) => mb_substr($part, 0, 1))->join(''),
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'lead_source_id' => $customer->lead_source_id,
            'site_address' => $customer->site_address,
            'city' => $customer->city,
            'state' => $customer->state,
            'postal_code' => $customer->postal_code,
            'notes' => $customer->notes,
            'total_jobs' => $customer->jobs()->count(),
            'lifetime_value_cents' => $customer->jobs()->sum('contract_value_cents'),
            'last_activity' => $customer->last_activity_at?->format('M j, Y') ?? 'No activity',
        ];
    }

    private function estimateNextAction(Estimate $estimate): string
    {
        return match ($estimate->status) {
            EstimateStatus::Draft => 'Complete scope',
            EstimateStatus::InReview => 'Review and email PDF',
            EstimateStatus::Emailed => 'Await customer response',
            EstimateStatus::Approved, EstimateStatus::SignaturePending => 'Request signature',
            EstimateStatus::Signed => 'Review job packet',
            EstimateStatus::Declined => 'Review decline reason',
            EstimateStatus::Expired => 'Refresh estimate',
        };
    }

    private function estimateDetail(Estimate $estimate): array
    {
        $reviewUrl = $estimate->attachments
            ->sortByDesc('created_at')
            ->first(fn (Attachment $attachment) => isset($attachment->metadata['review_url']))
            ?->metadata['review_url'];

        return [
            'id' => $estimate->id,
            'number' => $estimate->estimate_number,
            'project' => $estimate->project_name,
            'client' => $this->estimateClientName($estimate),
            'client_email' => $estimate->customer?->email ?: $estimate->lead?->email,
            'status' => $estimate->status->label(),
            'status_value' => $estimate->status->value,
            'builder_step' => $estimate->builder_step,
            'scope_summary' => $estimate->scope_summary,
            'review_url' => $reviewUrl,
            'summary' => $estimate->only(['material_cost_cents', 'labor_cost_cents', 'equipment_cost_cents', 'delivery_cost_cents', 'direct_cost_cents', 'overhead_cents', 'profit_cents', 'selling_price_cents', 'gross_margin_basis_points']),
            'items' => $estimate->items->map(fn (EstimateItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'subtitle' => $item->subtitle,
                'description' => $item->description,
                'item_type' => $item->item_type,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price_cents' => $item->unit_price_cents,
                'total_cents' => $item->total_cents,
                'thumbnail_path' => $item->thumbnail_path,
                'notes' => $item->notes,
            ]),
        ];
    }

    private function assemblyRow(Assembly $assembly): array
    {
        return [
            'id' => $assembly->id,
            'name' => $assembly->name,
            'category' => $assembly->category,
            'unit' => $assembly->unit,
            'items' => $assembly->components_count ?? $assembly->components()->count(),
            'base_cost_cents' => $assembly->base_cost_cents,
            'markup_basis_points' => $assembly->markup_basis_points,
            'selling_price_cents' => $assembly->selling_price_cents,
            'image_path' => $assembly->image_path,
            'description' => $assembly->description,
            'labor_hours_per_unit' => $assembly->labor_hours_per_unit,
            'waste_factor_basis_points' => $assembly->waste_factor_basis_points,
            'base_depth_inches' => $assembly->base_depth_inches,
            'default_minutes_per_unit' => $assembly->default_minutes_per_unit,
            'production_rate_per_day' => $assembly->production_rate_per_day,
        ];
    }

    private function assemblyDetail(Assembly $assembly): array
    {
        return [
            ...$this->assemblyRow($assembly),
            'components' => $assembly->components->map(fn ($component) => [
                'id' => $component->id,
                'name' => $component->name,
                'quantity_per_unit' => $component->quantity_per_unit,
                'unit' => $component->unit,
                'unit_cost_cents' => $component->unit_cost_cents,
                'formula' => $component->quantity_formula,
            ]),
        ];
    }

    private function materialRow(Material $material): array
    {
        return [
            'id' => $material->id,
            'name' => $material->name,
            'type' => $material->type->value,
            'type_label' => $material->type->label(),
            'sku' => $material->sku,
            'category' => $material->category,
            'unit' => $material->unit,
            'unit_cost_cents' => $material->unit_cost_cents,
            'markup_basis_points' => $material->markup_basis_points,
            'selling_price_cents' => $material->selling_price_cents,
            'hourly_rate_cents' => $material->hourly_rate_cents,
            'minimum_charge_cents' => $material->minimum_charge_cents,
            'pricing_method' => $material->pricing_method,
            'vendor' => $material->vendor,
            'description' => $material->description,
            'notes' => $material->notes,
            'is_active' => $material->is_active,
            'photo_path' => $material->photo_path,
        ];
    }

    private function estimateClientName(Estimate $estimate): string
    {
        return $estimate->customer?->name ?: $estimate->lead?->name ?: 'Unassigned';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function makeEstimateItemPayload(Estimate $estimate, array $validated): array
    {
        $quantity = (string) $validated['quantity'];

        if ($validated['item_type'] === 'assembly') {
            $assembly = Assembly::findOrFail($validated['assembly_id']);
            $this->ensureCompany($assembly);
            $materialCost = MoneyCalculator::multiplyDecimalByCents($quantity, $assembly->base_cost_cents);

            return [
                'assembly_id' => $assembly->id,
                'item_type' => 'assembly',
                'name' => $assembly->name,
                'subtitle' => $assembly->category,
                'description' => $assembly->description,
                'unit' => $assembly->unit,
                'unit_price_cents' => $assembly->selling_price_cents,
                'material_cost_cents' => $materialCost,
                'labor_cost_cents' => MoneyCalculator::multiplyDecimalByCents($quantity, $assembly->unit === 'sqft' ? 190 : 4500),
                'equipment_cost_cents' => MoneyCalculator::multiplyDecimalByCents($quantity, 45),
                'thumbnail_path' => $assembly->image_path,
                'source_snapshot' => $this->assemblyRow($assembly),
            ];
        }

        if ($validated['item_type'] === 'material') {
            $material = Material::findOrFail($validated['material_id']);
            $this->ensureCompany($material);
            $cost = MoneyCalculator::multiplyDecimalByCents($quantity, $material->unit_cost_cents);

            return [
                'material_id' => $material->id,
                'item_type' => $this->itemTypeForMaterial($material),
                'name' => $material->name,
                'subtitle' => $material->category,
                'description' => $material->description,
                'unit' => $material->unit,
                'unit_price_cents' => $material->selling_price_cents,
                'material_cost_cents' => $this->itemTypeForMaterial($material) === 'material' ? $cost : 0,
                'labor_cost_cents' => $this->itemTypeForMaterial($material) === 'labor' ? $cost : 0,
                'equipment_cost_cents' => $this->itemTypeForMaterial($material) === 'equipment' ? $cost : 0,
                'delivery_cost_cents' => $this->itemTypeForMaterial($material) === 'delivery' ? $cost : 0,
                'thumbnail_path' => $material->photo_path,
                'source_snapshot' => $this->materialRow($material),
            ];
        }

        $type = $validated['item_type'];
        $cost = MoneyCalculator::multiplyDecimalByCents($quantity, (int) $validated['unit_price_cents']);

        return [
            'item_type' => $type,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'unit' => $validated['unit'],
            'unit_price_cents' => (int) $validated['unit_price_cents'],
            'material_cost_cents' => in_array($type, ['custom'], true) ? $cost : 0,
            'labor_cost_cents' => $type === 'labor' ? $cost : 0,
            'equipment_cost_cents' => $type === 'equipment' ? $cost : 0,
            'source_snapshot' => ['source' => 'custom'],
        ];
    }

    private function itemTypeForMaterial(Material $material): string
    {
        return match ($material->type) {
            MaterialType::Labor, MaterialType::Service => 'labor',
            MaterialType::Equipment => 'equipment',
            MaterialType::Delivery => 'delivery',
            default => 'material',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'lead_source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'site_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function materialPayload(StoreMaterialRequest $request): array
    {
        $validated = $request->validated();
        $validated['selling_price_cents'] = MoneyCalculator::priceForMarkup((int) $validated['unit_cost_cents'], (int) $validated['markup_basis_points']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        return $validated;
    }

    private function repriceAssembly(Assembly $assembly): void
    {
        $base = $assembly->base_cost_cents;
        $assembly->forceFill([
            'selling_price_cents' => MoneyCalculator::priceForMargin($base, 0, $assembly->markup_basis_points),
        ])->save();
    }

    private function attachable(string $type, int $id): Lead|Customer|Estimate|JobPacket|Material|Assembly
    {
        return match ($type) {
            'lead' => Lead::findOrFail($id),
            'customer' => Customer::findOrFail($id),
            'estimate' => Estimate::findOrFail($id),
            'job_packet' => JobPacket::findOrFail($id),
            'material' => Material::findOrFail($id),
            'assembly' => Assembly::findOrFail($id),
        };
    }

    private function ensureCompany(object $model): void
    {
        abort_unless((int) $model->company_id === (int) $this->company()->id, 404);
    }

}
