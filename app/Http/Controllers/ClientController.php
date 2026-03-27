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
        $this->clientService->create($request->user(), $request->validated());

        return redirect()->route('clients.index')
            ->with('status', 'Client created.');
    }

    public function edit(Client $client): Response
    {
        abort_unless($client->user_id === auth()->id(), 403);

        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(StoreClientRequest $request, Client $client): RedirectResponse
    {
        abort_unless($client->user_id === auth()->id(), 403);

        $this->clientService->update($client, $request->validated());

        return redirect()->route('clients.index')
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
