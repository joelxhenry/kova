<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessSettingsRequest;
use App\Http\Requests\UpdateEmailSettingsRequest;
use App\Http\Requests\UpdateInvoiceSettingsRequest;
use App\Services\UserSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly UserSettingService $settingService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $setting = $this->settingService->getOrCreate($user);

        return Inertia::render('Settings/Index', [
            'business' => $setting->getGroup('business'),
            'invoicing' => $setting->getGroup('invoicing'),
            'email' => $setting->getGroup('email'),
            'invoiceNumberPreview' => $this->settingService->previewInvoiceNumber($user),
        ]);
    }

    public function updateBusiness(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $this->settingService->updateGroup($request->user(), 'business', $request->validated());

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
}
