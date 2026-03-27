<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\User;

class ClientService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Client
    {
        return $user->clients()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->fresh();
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }
}
