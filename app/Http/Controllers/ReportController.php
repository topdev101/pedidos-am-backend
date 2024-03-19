<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\CustomAgent;
use App\Models\Customer;
use App\Models\Supplier;

use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

class ReportController extends Controller
{
    public function purchasesReport(Request $request)
    {
        $mod = new Purchase();
        $mod = $mod->with('supplier', 'product');

        if ($request->get('supplier_id') != "") {
            $supplier_id = $request->get('supplier_id');
            $mod = $mod->where('supplier_id', $supplier_id);
        }

        if ($request->get('custom_agent_id') != "") {
            $custom_agent_id = $request->get('custom_agent_id');
            $mod = $mod->where('custom_agent_id', $custom_agent_id);
        }

        if ($request->get('keyword') != "") {
            $keyword = $request->keyword;
            $mod = $mod->where(function ($query) use ($keyword) {
                return $query->where('reference_no', 'like', "%$keyword%")
                    ->orWhere('purchased_at', 'like', "%$keyword%")
                    ->orWhere('bl_number', 'like', "%$keyword%")
                    ->orWhere('total_cost', 'like', "%$keyword%")
                    ->orWhereHas('supplier', function($query) use ($keyword) {
                        $query->where('company', 'like', "%$keyword%")
                            ->orWhere('name', 'like', "%$keyword%");
                    })
                    ->orWhereHas('product', function($query) use ($keyword) {
                        $query->where('reference', 'like', "%$keyword%")
                                ->orWhere('brand', 'like', "%$keyword%")
                                ->orWhere('vin', 'like', "%$keyword%")
                                ->orWhere('engine_number', 'like', "%$keyword%");
                    });
            });
        }

        if ($request->get('startDate') != '' && $request->get('endDate') != '') {
            if ($request->get('startDate') === $request->get('endDate')) {
                $mod = $mod->whereDate('purchased_at', $request->get('startDate'));
            } else {
                $mod = $mod->whereBetween('purchased_at', [$request->get('startDate'), $request->get('endDate')]);
            }
        }

        $sort_by_date = $request->sort_by_date ?? 'desc';

        $per_page = $request->get('per_page');
        $data = $mod->orderBy('purchased_at', $sort_by_date)->paginate($per_page);
        return $this->sendResponse($data);
    }

    public function suppliersReport(Request $request)
    {
        $mod = new Supplier();

        if ($request->get('keyword') != "") {
            $keyword = $request->get('keyword');
            $mod = $mod->where(function ($query) use ($keyword) {
                return $query->where('name', 'like', "%$keyword%")
                    ->orWhere('company', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%")
                    ->orWhere('phone_number', 'like', "%$keyword%")
                    ->orWhere('city', 'like', "%$keyword%")
                    ->orWhere('address', 'like', "%$keyword%");
            });
        }

        $per_page = $request->get('per_page');
        $data = $mod->orderBy('created_at', 'desc')->paginate($per_page);
        return $this->sendResponse($data);
    }

    public function expiredPurchasesReport(Request $request)
    {
        $mod = new Purchase();
        $mod = $mod->with(['product']);
        if ($request->get('keyword') != "") {
            $keyword = $request->keyword;
            $product_array = Product::where('reference', 'LIKE', "%$keyword%")
                    ->orWhere('brand', 'LIKE', "%$keyword%")
                    ->orWhere('vin', 'LIKE', "%$keyword%")
                    ->orWhere('engine_number', 'LIKE', "%$keyword%")
                    ->pluck('id');
            $supplier_array = Supplier::where('company', 'LIKE', "%$keyword%")
                    ->orWhere('name', 'LIKE', "%$keyword%")
                    ->pluck('id');

            $mod = $mod->where(function ($query) use ($keyword, $product_array, $supplier_array) {
                return $query->where('reference_no', 'LIKE', "%$keyword%")
                    ->orWhereIn('product_id', $product_array)
                    ->orWhereIn('supplier_id', $supplier_array)
                    ->orWhere('purchased_at', 'LIKE', "%$keyword%")
                    ->orWhere('total_cost', 'LIKE', "%$keyword%");
            });
        }
        $notArrivedProducts = Product::whereIn('status', ['in_the_city_of_purchase', 'on_the_way'])->pluck('id');
        $mod = $mod->whereNotNull('estimated_date')->whereDate('estimated_date', '<', now())->whereIn('product_id', $notArrivedProducts);

        if ($request->get('startDate') != '' && $request->get('endDate') != '') {
            if ($request->get('startDate') == $request->get('endDate')) {
                $mod = $mod->whereDate('estimated_date', $request->get('startDate'));
            } else {
                $mod = $mod->whereBetween('estimated_date', [$request->get('startDate'), $request->get('endDate')]);
            }
        } else {
            $from = "1970-01-01";
            $to = date('Y-m-d');
            $mod = $mod->whereBetween('estimated_date', [$from, $to]);
        }

        $data = $mod->orderBy('purchased_at', 'desc')->paginate($request->get('per_page'));
        // $data = $data->filter(function($item) {
        //     return $item->total_cost > $item->paid_amount;
        // })->values()->all();
        return $this->sendResponse($data);
    }
}
