<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWithholdingCreditRequest;
use App\Models\WithholdingCredit;
use App\Services\WithholdingCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WithholdingCreditController extends Controller
{
    public function __construct(
        private readonly WithholdingCreditService $withholdingCreditService,
    ) {}

    public function index(Request $request): Response
    {
        $year = (int) ($request->input('year') ?? date('Y'));

        $credits = $request->user()
            ->withholdingCredits()
            ->where('tax_year', $year)
            ->orderByDesc('date_withheld')
            ->get();

        $totalCredits = $credits->sum('amount');

        // Also include invoice + income withholding for the full picture
        $invoiceCredits = (float) $request->user()->invoices()
            ->where('status', 'paid')
            ->whereYear('issue_date', $year)
            ->sum('withholding_tax_amount');

        $incomeCredits = (float) $request->user()->incomeEntries()
            ->whereYear('date_received', $year)
            ->sum('withholding_tax_applied');

        return Inertia::render('Tax/WithholdingCredits', [
            'credits' => $credits,
            'year' => $year,
            'summary' => [
                'invoiceCredits' => round($invoiceCredits, 2),
                'incomeCredits' => round($incomeCredits, 2),
                'manualCredits' => round((float) $totalCredits, 2),
                'totalCredits' => round($invoiceCredits + $incomeCredits + (float) $totalCredits, 2),
            ],
        ]);
    }

    public function store(StoreWithholdingCreditRequest $request): RedirectResponse
    {
        $this->withholdingCreditService->createManual(
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('withholding-credits.index', ['year' => $request->input('tax_year')])
            ->with('status', 'Withholding credit added.');
    }

    public function destroy(WithholdingCredit $withholding_credit): RedirectResponse
    {
        abort_unless($withholding_credit->user_id === auth()->id(), 403);

        $year = $withholding_credit->tax_year;
        $this->withholdingCreditService->delete($withholding_credit);

        return redirect()->route('withholding-credits.index', ['year' => $year])
            ->with('status', 'Withholding credit deleted.');
    }
}
