<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Image;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Traits\UploadAble;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    use UploadAble;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getDashboardData(Request $request)
    {
        $data = [];
        if (auth()->user()->company_id != null) {
            $data['total_ordered'] = PurchaseOrder::where('company_id', auth()->user()->company_id)->sum('total_amount');
            $data['total_received'] = Purchase::where('company_id', auth()->user()->company_id)->sum('total_amount');
        } else {
            $data['total_ordered'] = PurchaseOrder::sum('total_amount');
            $data['total_received'] = Purchase::sum('total_amount');
        }

        return $this->sendResponse($data);
    }

    public function getCategoryChartData(Request $request)
    {
        $keys = [];
        $ordered_quantity = [];
        $received_quantity = [];
        foreach (Category::all() as $category) {
            $keys[] = $category->name;
            $mod_ordered = $category->ordered_items();
            $mod_received = $category->received_items();

            if ($request->get('startDate') != '' && $request->get('endDate') != '') {
                $startDate = Carbon::parse($request->get('startDate'));
                $endDate = Carbon::parse($request->get('endDate'));
                $mod_ordered = $mod_ordered->whereHas('purchase_order', function ($query) use($startDate, $endDate) {
                    if ($startDate === $endDate) {
                        $query = $query->whereDate('ordered_at', $startDate);
                    } else {
                        $query = $query->whereBetween('ordered_at', [$startDate, $endDate]);
                    }
                });
                $mod_received = $mod_received->whereHas('purchase', function ($query) use($startDate, $endDate) {
                    if ($startDate === $endDate) {
                        $query = $query->whereDate('purchased_at', $startDate);
                    } else {
                        $query = $query->whereBetween('purchased_at', [$startDate, $endDate]);
                    }
                });
            }

            if ($request->get('company_id') != '') {
                $company_id = $request->get('company_id');
                $mod_ordered = $mod_ordered->whereHas('purchase_order', function ($query) use($company_id) {
                    $query = $query->where('company_id', $company_id);
                });
                $mod_received = $mod_received->whereHas('purchase', function ($query) use($company_id) {
                    $query = $query->where('company_id', $company_id);
                });
            }

            if ($request->get('date') != '') {
                $date = Carbon::parse($request->get('date'))->format('Y-m-d');
                $mod_ordered = $mod_ordered->whereHas('purchase_order', function ($query) use($date) {
                    $query = $query->whereDate('ordered_at', $date);
                });
                $mod_received = $mod_received->whereHas('purchase', function ($query) use($date) {
                    $query = $query->whereDate('purchased_at', $date);
                });
            }

            $ordered_quantity[] = $mod_ordered->sum('quantity');
            $received_quantity[] = $mod_received->sum('quantity');
        }

        return $this->sendResponse(['keys' => $keys, 'values' => ['ordered' => $ordered_quantity, 'received' => $received_quantity]]);
    }

    public function getSuppliers() {
        return $this->sendResponse(Supplier::all(['id', 'name', 'brand', 'company_id', 'discount']));
    }

    public function getCategories() {
        return $this->sendResponse(Category::all());
    }

    public function deleteImage($id) {
        $image = Image::find($id);
        if ($this->fileExists($image->path, $image->disk)) {
            $this->deleteFile($image->path, $image->disk);
        }
        $image->delete();
        return $this->sendResponse();
    }
}
