<?php
declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

test('recurring payment is listed under recurring with its credit destination', function () {
    $user = User::factory()->create();
    $checking = $user->accounts()->create(['name'=>'Checking','type'=>'debit','opening_balance'=>50000,'current_balance'=>50000,'is_active'=>true,'sort_order'=>0]);
    $card = $user->accounts()->create(['name'=>'Visa','type'=>'credit','opening_balance'=>0,'current_balance'=>30000,'is_active'=>true,'sort_order'=>0]);

    $this->actingAs($user)->post('/budget/payments', [
        'from_account_id'=>$checking->id,'to_account_id'=>$card->id,
        'amount'=>5000,'date'=>'2026-07-13','recurring'=>true,'frequency'=>'monthly',
    ])->assertRedirect('/budget/accounts');

    $this->actingAs($user)->get('/budget/recurring')
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Recurring/Index')
            ->has('recurring', 1, fn ($r) => $r
                ->where('type', 'transfer')
                ->where('description', 'Credit card payment')
                ->where('transfer_account.type', 'credit')
                ->where('transfer_account.name', 'Visa')
                ->etc()
            )
        );
});
