<?php

declare(strict_types=1);

use App\Models\ExpectedTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProjectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param array<string, mixed> $attributes
 */
function expectedAccount(User $user, array $attributes = []): App\Models\Account
{
    return $user->accounts()->create(array_merge([
        'name' => 'Account',
        'type' => 'debit',
        'opening_balance' => 5000,
        'current_balance' => 5000,
        'is_active' => true,
        'sort_order' => 0,
    ], $attributes));
}

test('an expected payment can be created and never moves a balance until realized', function () {
    $user = User::factory()->create();
    $checking = expectedAccount($user, ['name' => 'Checking', 'type' => 'debit', 'current_balance' => 5000]);
    $card = expectedAccount($user, ['name' => 'Visa', 'type' => 'credit', 'current_balance' => 2000]);

    $this->actingAs($user)
        ->post('/budget/expected', [
            'account_id' => $checking->id,
            'transfer_account_id' => $card->id,
            'type' => 'transfer',
            'amount' => 600,
            'expected_date' => '2026-08-01',
            'description' => 'Card payment',
        ])
        ->assertRedirect('/budget/expected');

    $this->assertDatabaseHas('expected_transactions', [
        'account_id' => $checking->id,
        'transfer_account_id' => $card->id,
        'type' => 'transfer',
        'status' => 'pending',
    ]);
    expect((float) $checking->fresh()->current_balance)->toBe(5000.0);
    expect((float) $card->fresh()->current_balance)->toBe(2000.0);
});

test('an expected payment must run from a debit account to a credit account', function () {
    $user = User::factory()->create();
    $cardA = expectedAccount($user, ['name' => 'Card A', 'type' => 'credit', 'current_balance' => 500]);
    $checking = expectedAccount($user, ['name' => 'Checking', 'type' => 'debit']);

    // Funding from a credit account is rejected.
    $this->actingAs($user)
        ->post('/budget/expected', [
            'account_id' => $cardA->id,
            'transfer_account_id' => $checking->id,
            'type' => 'transfer',
            'amount' => 100,
            'expected_date' => '2026-08-01',
            'description' => 'Bad',
        ])
        ->assertSessionHasErrors(['account_id', 'transfer_account_id']);
});

test('realizing an expected payment posts the real payment and moves both balances', function () {
    $user = User::factory()->create();
    $checking = expectedAccount($user, ['name' => 'Checking', 'type' => 'debit', 'current_balance' => 5000]);
    $card = expectedAccount($user, ['name' => 'Visa', 'type' => 'credit', 'current_balance' => 2000]);

    $expected = ExpectedTransaction::factory()->for($user)->payment()->create([
        'account_id' => $checking->id,
        'transfer_account_id' => $card->id,
        'amount' => 600,
        'expected_date' => '2026-08-01',
    ]);

    $this->actingAs($user)
        ->post("/budget/expected/{$expected->id}/realize", [
            'account_id' => $checking->id,
            'date' => '2026-08-01',
            'amount' => 600,
        ])
        ->assertRedirect('/budget/expected');

    expect((float) $checking->fresh()->current_balance)->toBe(4400.0);
    expect((float) $card->fresh()->current_balance)->toBe(1400.0);

    $expected->refresh();
    expect($expected->status)->toBe('realized');
    expect($expected->realized_transaction_id)->not->toBeNull();

    $this->assertDatabaseHas('transactions', [
        'id' => $expected->realized_transaction_id,
        'account_id' => $checking->id,
        'transfer_account_id' => $card->id,
        'type' => 'transfer',
    ]);
});

test('a pending expected payment lowers both cash and card in the projection and is net-worth neutral', function () {
    Carbon::setTestNow('2026-06-13');
    $user = User::factory()->create();
    $checking = expectedAccount($user, ['name' => 'Checking', 'type' => 'debit', 'current_balance' => 5000]);
    $card = expectedAccount($user, ['name' => 'Visa', 'type' => 'credit', 'current_balance' => 2000]);

    ExpectedTransaction::factory()->for($user)->payment()->create([
        'account_id' => $checking->id,
        'transfer_account_id' => $card->id,
        'amount' => 600,
        'expected_date' => '2026-06-20',
    ]);

    $result = app(ProjectionService::class)->project($user, Carbon::parse('2026-06-30'));

    $byName = collect($result['datasets'])->keyBy('name');
    $checkingPoints = $byName['Checking']['points'];
    $cardPoints = $byName['Visa']['points'];
    $aggregate = $result['aggregate'];
    // Cash drops 600; card debt drops 600.
    expect(end($checkingPoints))->toBe(4400.0);
    expect(end($cardPoints))->toBe(1400.0);

    // Net worth is unchanged: starts 5000 − 2000 = 3000 and ends the same.
    expect(end($aggregate))->toBe(3000.0);

    // No transactions were written by the read-only projection.
    expect(Transaction::count())->toBe(0);
    Carbon::setTestNow();
});
