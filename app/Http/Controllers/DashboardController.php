<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GctMonitorService;
use App\Services\TaxCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TaxCalculationService $taxCalculationService,
        private readonly GctMonitorService $gctMonitorService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $year = (int) ($request->input('year') ?? date('Y'));
        $user = $request->user();

        $taxBreakdown = $this->taxCalculationService->calculateAnnualTax($user, $year);
        $quarterlyEstimates = $this->taxCalculationService->calculateQuarterlyEstimates($user, $year);
        $gctStatus = $this->gctMonitorService->getStatus($user, $year);

        $monthlyIncome = $user->invoices()
            ->where('status', 'paid')
            ->whereYear('issue_date', $year)
            ->select(DB::raw('MONTH(issue_date) as month'), DB::raw('SUM(subtotal) as total'))
            ->groupBy(DB::raw('MONTH(issue_date)'))
            ->pluck('total', 'month');

        $monthlyOtherIncome = $user->incomeEntries()
            ->whereYear('date_received', $year)
            ->select(DB::raw('MONTH(date_received) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date_received)'))
            ->pluck('total', 'month');

        $monthlyExpenses = $user->expenses()
            ->whereYear('date_incurred', $year)
            ->select(DB::raw('MONTH(date_incurred) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date_incurred)'))
            ->pluck('total', 'month');

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[] = [
                'month' => $m,
                'income' => round((float) ($monthlyIncome[$m] ?? 0) + (float) ($monthlyOtherIncome[$m] ?? 0), 2),
                'expenses' => round((float) ($monthlyExpenses[$m] ?? 0), 2),
            ];
        }

        return Inertia::render('Dashboard', [
            'year' => $year,
            'taxBreakdown' => $taxBreakdown->toArray(),
            'quarterlyEstimates' => array_map(fn ($e) => $e->toArray(), $quarterlyEstimates),
            'gctStatus' => $gctStatus,
            'monthlyData' => $monthlyData,
        ]);
    }
}
