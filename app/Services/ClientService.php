<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\User;

class ClientService
{
    /**
     * @param array<string, mixed> $data
     * @param list<array{first_name: string, last_name: string, email?: string, phone?: string}> $contacts
     */
    public function create(User $user, array $data, array $contacts = []): Client
    {
        $client = $user->clients()->create($data);

        $this->syncContacts($client, $contacts);

        return $client->load('contacts');
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array{id?: int, first_name: string, last_name: string, email?: string, phone?: string}> $contacts
     */
    public function update(Client $client, array $data, array $contacts = []): Client
    {
        $client->update($data);

        $this->syncContacts($client, $contacts);

        return $client->fresh('contacts');
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }

    /**
     * Sync contacts: keep existing by id, create new, delete removed.
     *
     * @param list<array{id?: int, first_name: string, last_name: string, email?: string, phone?: string}> $contacts
     */
    private function syncContacts(Client $client, array $contacts): void
    {
        $incomingIds = collect($contacts)->pluck('id')->filter()->toArray();

        // Delete contacts not in the incoming list
        $client->contacts()->whereNotIn('id', $incomingIds)->delete();

        foreach ($contacts as $contact) {
            if (isset($contact['id']) && $contact['id']) {
                $client->contacts()->where('id', $contact['id'])->update([
                    'first_name' => $contact['first_name'],
                    'last_name' => $contact['last_name'],
                    'email' => $contact['email'] ?? null,
                    'phone' => $contact['phone'] ?? null,
                ]);
            } else {
                $client->contacts()->create([
                    'first_name' => $contact['first_name'],
                    'last_name' => $contact['last_name'],
                    'email' => $contact['email'] ?? null,
                    'phone' => $contact['phone'] ?? null,
                ]);
            }
        }
    }
}
