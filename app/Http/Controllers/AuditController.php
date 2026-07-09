<?php

namespace App\Http\Controllers;

use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): Response
    {
        $period = $request->string('period', 'monthly')->toString();
        $year = (int) $request->input('year', now()->year);
        $currentMonth = now()->month;
        $defaultMonth = $currentMonth === 1 ? 12 : $currentMonth - 1;
        $defaultYear = $currentMonth === 1 && !$request->has('year') ? now()->year - 1 : $year;
        $month = $request->has('month') ? (int) $request->input('month') : $defaultMonth;
        $year = $defaultYear;
        $quarter = $request->has('quarter') ? (int) $request->input('quarter') : 1;
        $tab = $request->string('tab', 'sales')->toString();
        $range = $this->audit->getDateRange($period, $year, $month, $quarter);

        return Inertia::render('Audit', [
            'title' => 'Audit & Reports',
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'tab' => $tab,
            'dateLabel' => $range['label'],
            'summary' => $this->audit->getSummary($range['from'], $range['to']),
            'guestStats' => $this->audit->getGuestStats($range['from'], $range['to']),
            'auditLogs' => $this->audit->getAuditLogs($range['from'], $range['to'])->toArray(),
            'archivedRows' => $this->audit->getArchivedInRange($range['from'], $range['to'])->toArray(),
        ]);
    }
}