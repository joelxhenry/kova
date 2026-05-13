<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceEmailService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\UserSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly UserSettingService $userSettingService,
        private readonly InvoiceEmailService $invoiceEmailService,
        private readonly InvoicePdfService $invoicePdfService,
    ) {}

    public function index(Request $request): Response
    {
        $query = $request->user()
            ->invoices()
            ->with('client:id,name')
            ->orderByDesc('issue_date');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->filled('from')) {
            $query->where('issue_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('issue_date', '<=', $request->input('to'));
        }

        $invoices = $query->paginate(20)->withQueryString();

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'clients' => $clients,
            'filters' => $request->only(['status', 'client_id', 'from', 'to']),
        ]);
    }

    public function create(Request $request): Response
    {
        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name', 'is_designated_entity']);

        return Inertia::render('Invoices/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $this->invoiceService->create($request->user(), $validated, $items);

        return redirect()->route('invoices.index')
            ->with('status', 'Invoice created.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $invoice->load('items', 'client.contacts');

        $user = $request->user();
        $settings = $this->userSettingService->getOrCreate($user);
        $businessSettings = $settings->getGroup('business');

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'business' => $businessSettings,
            'availableRecipients' => $this->invoiceEmailService->getAvailableRecipients($invoice),
        ]);
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $invoice->load('items', 'client');
        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name', 'is_designated_entity']);

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => $clients,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $this->invoiceService->update($invoice, $validated, $items);

        return redirect()->route('invoices.show', $invoice)
            ->with('status', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $this->invoiceService->delete($invoice);

        return redirect()->route('invoices.index')
            ->with('status', 'Invoice deleted.');
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
        ]);

        $this->invoiceService->updateStatus($invoice, $validated['status']);

        return redirect()->route('invoices.show', $invoice)
            ->with('status', 'Invoice status updated.');
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $invoice->load('items');

        $newInvoice = $this->invoiceService->duplicate($invoice);

        return redirect()->route('invoices.edit', $newInvoice)
            ->with('status', 'Invoice duplicated as draft.');
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $request->validate([
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['required', 'email'],
        ]);

        $invoice->load('items', 'client.contacts');
        $user = $request->user();

        $this->invoiceEmailService->sendTo($invoice, $user, $request->input('recipients'));

        if ($invoice->status === 'draft') {
            $this->invoiceService->updateStatus($invoice, 'sent');
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('status', 'Invoice sent successfully.');
    }

    public function download(Request $request, Invoice $invoice): HttpResponse
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $pdf = $this->invoicePdfService->generate($invoice, $request->user());
        $filename = $this->invoicePdfService->filename($invoice);

        return $pdf->download($filename);
    }
}
