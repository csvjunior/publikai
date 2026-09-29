<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            // Contagem real (sem métricas fabricadas); acoplamento mínimo e intencional.
            'productsCount' => Product::count(),
        ]);
    }
}
