<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', '1'));
        $limit = min(100, max(1, (int) $request->query('limit', '50')));

        $query = Product::query();

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper($request->query('currency')));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->query('max_price'));
        }

        $paginator = $query->orderBy('id')->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function summary(): JsonResponse
    {
        $count = Product::count();
        $totalPrice = (float) Product::sum('price');
        $averagePrice = $count > 0 ? round($totalPrice / $count, 2) : 0.0;

        $currencies = Product::query()
            ->select([ 'currency', DB::raw('COUNT(*) as count') ])
            ->groupBy('currency')
            ->pluck('count', 'currency')
            ->toArray();

        return response()->json([
            'count' => $count,
            'total_price' => round($totalPrice, 2),
            'average_price' => $averagePrice,
            'currencies' => $currencies,
        ]);
    }

    public function duplicates(): JsonResponse
    {
        $duplicateNames = Product::query()
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        $duplicateLinks = Product::query()
            ->select('link')
            ->groupBy('link')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('link');

        $products = Product::query()
            ->whereIn('name', $duplicateNames)
            ->orWhereIn('link', $duplicateLinks)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $products,
            'meta' => [
                'total' => $products->count(),
            ],
        ]);
    }
}
