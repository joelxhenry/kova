<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
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

    public function show(Invoice $invoice): Response
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $invoice->load('items', 'client');

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
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

    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
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
}
