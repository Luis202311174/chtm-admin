<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function __invoke(Request $request): Response
    {
        $data = $this->dashboard->forUser($request->user());
        unset($data['user']);

        return Inertia::render('Dashboard', array_merge(
            $data,
            ['title' => 'Dashboard']
        ));
    }
}