<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxProfileRequest;
use App\Http\Requests\UpdateBusinessSettingsRequest;
use App\Http\Requests\UpdateEmailSettingsRequest;
use App\Http\Requests\UpdateInvoiceSettingsRequest;
use App\Models\StatutoryRate;
use App\Services\TaxProfileService;
use App\Services\UserSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly UserSettingService $settingService,
        private readonly TaxProfileService $taxProfileService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $setting = $this->settingService->getOrCreate($user);

        $statutoryRates = StatutoryRate::where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->get(['key', 'label', 'value', 'effective_from'])
            ->unique('key')
            ->keyBy('key');

        return Inertia::render('Settings/Index', [
            'business' => $setting->getGroup('business'),
            'invoicing' => $setting->getGroup('invoicing'),
            'email' => $setting->getGroup('email'),
            'invoiceNumberPreview' => $this->settingService->previewInvoiceNumber($user),
            'taxProfile' => $user->taxProfile,
            'statutoryRates' => $statutoryRates,
        ]);
    }

    public function updateBusiness(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            $this->settingService->uploadLogo($request->user(), $request->file('logo'));
            unset($validated['logo']);
        }

        $this->settingService->updateGroup($request->user(), 'business', $validated);

        return redirect()->route('settings.index')
            ->with('status', 'Business profile updated.');
    }

    public function updateInvoicing(UpdateInvoiceSettingsRequest $request): RedirectResponse
    {
        $this->settingService->updateGroup($request->user(), 'invoicing', $request->validated());

        return redirect()->route('settings.index')
            ->with('status', 'Invoice settings updated.');
    }

    public function updateEmail(UpdateEmailSettingsRequest $request): RedirectResponse
    {
        $this->settingService->updateGroup($request->user(), 'email', $request->validated());

        return redirect()->route('settings.index')
            ->with('status', 'Email templates updated.');
    }

    public function updateTaxProfile(StoreTaxProfileRequest $request): RedirectResponse
    {
        $this->taxProfileService->upsert(
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('settings.index')
            ->with('status', 'Tax profile updated.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $this->settingService->removeLogo($request->user());

        return redirect()->route('settings.index')
            ->with('status', 'Logo removed.');
    }
}
