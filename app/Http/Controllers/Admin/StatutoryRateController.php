<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatutoryRateRequest;
use App\Models\StatutoryRate;
use App\Models\StatutoryRateAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatutoryRateController extends Controller
{
    public function index(): Response
    {
        // Group all versions by key, show current value + version count
        $allRates = StatutoryRate::orderBy('key')->orderByDesc('effective_from')->get();

        $grouped = $allRates->groupBy('key')->map(function ($versions) {
            $current = $versions->first();

            return [
                'key' => $current->key,
                'label' => $current->label,
                'description' => $current->description,
                'current_value' => $current->value,
                'effective_from' => $current->effective_from,
                'version_count' => $versions->count(),
            ];
        })->values();

        return Inertia::render('Admin/StatutoryRates/Index', [
            'rates' => $grouped,
        ]);
    }

    public function show(string $key): Response
    {
        $versions = StatutoryRate::where('key', $key)
            ->orderByDesc('effective_from')
            ->get();

        if ($versions->isEmpty()) {
            abort(404);
        }

        $current = $versions->first();

        // Get audit logs for all versions of this key
        $auditLogs = StatutoryRateAuditLog::whereIn('statutory_rate_id', $versions->pluck('id'))
            ->with('user:id,name')
            ->orderByDesc('changed_at')
            ->get();

        return Inertia::render('Admin/StatutoryRates/Show', [
            'rateKey' => $key,
            'label' => $current->label,
            'description' => $current->description,
            'versions' => $versions,
            'auditLogs' => $auditLogs,
        ]);
    }

    public function store(UpdateStatutoryRateRequest $request, string $key): RedirectResponse
    {
        $validated = $request->validated();

        $current = StatutoryRate::current($key);

        if (! $current) {
            abort(404);
        }

        // Prevent duplicate effective_from for the same key
        $exists = StatutoryRate::where('key', $key)
            ->where('effective_from', $validated['effective_from'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['effective_from' => 'A rate version already exists for this effective date.']);
        }

        $newVersion = StatutoryRate::create([
            'key' => $key,
            'label' => $current->label,
            'description' => $current->description,
            'value' => $validated['value'],
            'effective_from' => $validated['effective_from'],
        ]);

        StatutoryRateAuditLog::create([
            'statutory_rate_id' => $newVersion->id,
            'user_id' => $request->user()->id,
            'old_value' => $current->value,
            'new_value' => $validated['value'],
            'old_effective_from' => $current->effective_from,
            'new_effective_from' => $validated['effective_from'],
            'changed_at' => now(),
        ]);

        return redirect()->route('admin.statutory-rates.show', $key)
            ->with('status', "New version added for {$current->label}.");
    }
}
