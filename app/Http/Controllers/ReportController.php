<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Services\BudgetTargetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly BudgetTargetService $budgetTargetService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $from = $request->input('from');
        $to = $request->input('to');
        $clientId = $request->input('client_id');

        // Invoice stats
        $invoiceQuery = $user->invoices()
            ->when($from, fn ($q) => $q->where('issue_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('issue_date', '<=', $to))
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId));

        $totalInvoiced = (float) (clone $invoiceQuery)->sum('total');
        $totalPaid = (float) (clone $invoiceQuery)->where('status', 'paid')->sum('total');
        $totalPending = (float) (clone $invoiceQuery)->whereIn('status', ['sent', 'overdue'])->sum('total');
        $invoiceCount = (clone $invoiceQuery)->count();
        $paidCount = (clone $invoiceQuery)->where('status', 'paid')->count();
        $overdueCount = (clone $invoiceQuery)->where('status', 'overdue')->count();

        // Invoices by status
        $byStatus = (clone $invoiceQuery)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => $row->count,
                'total' => round((float) $row->total, 2),
            ]);

        // Invoices by client (top 10)
        $byClient = $user->invoices()
            ->when($from, fn ($q) => $q->where('issue_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('issue_date', '<=', $to))
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->select('clients.name as client_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(invoices.total) as total'))
            ->groupBy('clients.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'client' => $row->client_name,
                'count' => $row->count,
                'total' => round((float) $row->total, 2),
            ]);

        // Expense stats
        $expenseQuery = $user->expenses()
            ->when($from, fn ($q) => $q->where('date_incurred', '>=', $from))
            ->when($to, fn ($q) => $q->where('date_incurred', '<=', $to));

        $totalExpenses = (float) (clone $expenseQuery)->sum('amount');
        $expenseCount = (clone $expenseQuery)->count();

        // Expenses by category
        $byCategory = (clone $expenseQuery)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.name as category', DB::raw('COUNT(*) as count'), DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'count' => $row->count,
                'total' => round((float) $row->total, 2),
            ]);

        // Monthly breakdown (invoiced vs spent)
        $monthlyInvoiced = $user->invoices()
            ->where('status', 'paid')
            ->when($from, fn ($q) => $q->where('issue_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('issue_date', '<=', $to))
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->select(DB::raw('MONTH(issue_date) as month'), DB::raw('YEAR(issue_date) as year'), DB::raw('SUM(total) as total'))
            ->groupBy(DB::raw('YEAR(issue_date)'), DB::raw('MONTH(issue_date)'))
            ->orderBy(DB::raw('YEAR(issue_date)'))
            ->orderBy(DB::raw('MONTH(issue_date)'))
            ->get();

        $monthlyExpenses = $user->expenses()
            ->when($from, fn ($q) => $q->where('date_incurred', '>=', $from))
            ->when($to, fn ($q) => $q->where('date_incurred', '<=', $to))
            ->select(DB::raw('MONTH(date_incurred) as month'), DB::raw('YEAR(date_incurred) as year'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('YEAR(date_incurred)'), DB::raw('MONTH(date_incurred)'))
            ->orderBy(DB::raw('YEAR(date_incurred)'))
            ->orderBy(DB::raw('MONTH(date_incurred)'))
            ->get();

        $months = collect();
        $allMonthKeys = $monthlyInvoiced->map(fn ($r) => "{$r->year}-{$r->month}")
            ->merge($monthlyExpenses->map(fn ($r) => "{$r->year}-{$r->month}"))
            ->unique()
            ->sort();

        foreach ($allMonthKeys as $key) {
            [$y, $m] = explode('-', $key);
            $inv = $monthlyInvoiced->first(fn ($r) => $r->year == $y && $r->month == $m);
            $exp = $monthlyExpenses->first(fn ($r) => $r->year == $y && $r->month == $m);
            $months->push([
                'label' => date('M Y', mktime(0, 0, 0, (int) $m, 1, (int) $y)),
                'invoiced' => round((float) ($inv?->total ?? 0), 2),
                'expenses' => round((float) ($exp?->total ?? 0), 2),
            ]);
        }

        $clients = $user->clients()->orderBy('name')->get(['id', 'name']);

        // Current-month budget adherence — surfaced only once the user has set
        // targets, limited to targeted categories for an at-a-glance summary.
        $budgetAdherence = null;
        if ($user->budgetTargets()->exists()) {
            $report = $this->budgetTargetService->report($user, Carbon::now()->startOfMonth());
            $budgetAdherence = [
                'month' => $report['month'],
                'rows' => array_values(array_filter($report['rows'], fn (array $row): bool => $row['targeted'])),
                'totals' => $report['totals'],
            ];
        }

        return Inertia::render('Reports/Index', [
            'summary' => [
                'totalInvoiced' => round($totalInvoiced, 2),
                'totalPaid' => round($totalPaid, 2),
                'totalPending' => round($totalPending, 2),
                'invoiceCount' => $invoiceCount,
                'paidCount' => $paidCount,
                'overdueCount' => $overdueCount,
                'totalExpenses' => round($totalExpenses, 2),
                'expenseCount' => $expenseCount,
                'netIncome' => round($totalPaid - $totalExpenses, 2),
            ],
            'byStatus' => $byStatus,
            'byClient' => $byClient,
            'byCategory' => $byCategory,
            'monthly' => $months,
            'clients' => $clients,
            'budgetAdherence' => $budgetAdherence,
            'filters' => $request->only(['from', 'to', 'client_id']),
        ]);
    }
}
