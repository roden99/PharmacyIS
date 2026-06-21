<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCustomers  = Customer::where('status', 'active')->count();
        $totalDrugstores = Customer::where('status', 'active')->where('is_drugstore', true)->count();
        $totalDoctors    = Customer::where('status', 'active')->where('is_drugstore', false)->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_customers'  => $totalCustomers,
                'total_drugstores' => $totalDrugstores,
                'total_doctors'    => $totalDoctors,
            ],
        ]);
    }
}
