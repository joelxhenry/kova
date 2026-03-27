<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxProfileRequest;
use App\Services\TaxProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxProfileController extends Controller
{
    public function __construct(
        private readonly TaxProfileService $taxProfileService,
    ) {}

    public function edit(Request $request): Response
    {
        $taxProfile = $request->user()->taxProfile;

        return Inertia::render('Profile/TaxProfile', [
            'taxProfile' => $taxProfile,
        ]);
    }

    public function update(StoreTaxProfileRequest $request): RedirectResponse
    {
        $this->taxProfileService->upsert(
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('tax-profile.edit')
            ->with('status', 'Tax profile updated.');
    }
}
