<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TaxFormSnapshot;
use App\Services\TajFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxFormController extends Controller
{
    public function __construct(
        private readonly TajFormService $tajFormService,
    ) {}

    public function show(Request $request): Response
    {
        $year = (int) ($request->input('year') ?? date('Y'));
        $user = $request->user();

        $formData = $this->tajFormService->buildFormData($user, $year);

        $snapshots = $user->taxFormSnapshots()
            ->where('tax_year', $year)
            ->orderByDesc('generated_at')
            ->get(['id', 'tax_year', 'form_type', 'generated_at']);

        return Inertia::render('Tax/FormPreview', [
            'formData' => $formData,
            'year' => $year,
            'snapshots' => $snapshots,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $year = (int) $request->input('year', date('Y'));

        $this->tajFormService->generateSnapshot($request->user(), $year);

        return redirect()->route('tax-form.show', ['year' => $year])
            ->with('status', 'Tax form snapshot generated.');
    }

    public function snapshot(TaxFormSnapshot $snapshot): Response
    {
        abort_unless($snapshot->user_id === auth()->id(), 403);

        return Inertia::render('Tax/FormPreview', [
            'formData' => $snapshot->data,
            'year' => $snapshot->tax_year,
            'snapshots' => auth()->user()->taxFormSnapshots()
                ->where('tax_year', $snapshot->tax_year)
                ->orderByDesc('generated_at')
                ->get(['id', 'tax_year', 'form_type', 'generated_at']),
            'viewingSnapshot' => $snapshot->id,
        ]);
    }
}
