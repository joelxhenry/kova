<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
    ) {}

    public function index(Request $request): Response
    {
        $clients = $request->user()
            ->clients()
            ->withCount('invoices')
            ->orderBy('name')
            ->get();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $contacts = $validated['contacts'] ?? [];
        unset($validated['contacts']);

        $client = $this->clientService->create($request->user(), $validated, $contacts);

        return redirect()->route('clients.show', $client)
            ->with('status', 'Client created.');
    }

    public function show(Client $client): Response
    {
        abort_unless($client->user_id === auth()->id(), 403);

        $client->load('contacts');

        $invoices = $client->invoices()
            ->orderByDesc('issue_date')
            ->get();

        $totalInvoiced = (float) $invoices->sum('total');
        $totalPaid = (float) $invoices->where('status', 'paid')->sum('total');
        $balanceDue = $totalInvoiced - $totalPaid;

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'invoices' => $invoices,
            'summary' => [
                'totalInvoiced' => round($totalInvoiced, 2),
                'totalPaid' => round($totalPaid, 2),
                'balanceDue' => round($balanceDue, 2),
            ],
        ]);
    }

    public function edit(Client $client): Response
    {
        abort_unless($client->user_id === auth()->id(), 403);

        $client->load('contacts');

        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(StoreClientRequest $request, Client $client): RedirectResponse
    {
        abort_unless($client->user_id === auth()->id(), 403);

        $validated = $request->validated();
        $contacts = $validated['contacts'] ?? [];
        unset($validated['contacts']);

        $this->clientService->update($client, $validated, $contacts);

        return redirect()->route('clients.show', $client)
            ->with('status', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        abort_unless($client->user_id === auth()->id(), 403);

        $this->clientService->delete($client);

        return redirect()->route('clients.index')
            ->with('status', 'Client deleted.');
    }
}
