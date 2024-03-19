<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PDF;

class SupplierController extends Controller
{
    public function search(Request $request)
    {
        $mod = new Supplier();
        if ($request->get('keyword') != '') {
            $keyword = $request->get('keyword');
            $mod = $mod->where(function($query) use ($keyword) {
                return $query->where('name', 'like', "%$keyword%")
                            ->orWhere('brand', 'like', "%$keyword%")
                            ->orWhere('phone_number', 'like', "%$keyword%")
                            ->orWhere('address', 'like', "%$keyword%");
            });
        }
        $per_page = $request->get('per_page');
        $data = $mod->orderBy('created_at', 'desc')->paginate($per_page);
        $comapny_id = $request->get('company_id');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $data->each(function ($item) use ($comapny_id, $startDate, $endDate) {
            $mod_ordered = $item->ordered_items();
            $mod_received = $item->received_items();
            if ($comapny_id) {
                $mod_ordered = $mod_ordered->whereHas('purchase_order', function($query) use ($comapny_id) {
                    $query = $query->where('company_id', $comapny_id);
                });
                $mod_received = $mod_received->whereHas('purchase', function($query) use ($comapny_id) {
                    $query = $query->where('company_id', $comapny_id);
                });
            }

            if ($startDate != '' && $endDate != '') {
                $startDate = Carbon::parse($startDate);
                $endDate = Carbon::parse($endDate);
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


            $item->ordered_quantity = $mod_ordered->sum('quantity');
            $item->received_quantity = $mod_received->sum('quantity');
        });
        return $this->sendResponse($data);
    }

    public function save(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('suppliers')->ignore($request->get('id')),
            ],
            'brand' => 'required',
            'company_id' => 'required',
            'discount' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!is_numeric($value) && !preg_match('/^\d+(\.\d+)?%$/', $value)) {
                        $fail(__('validation.discount_format', ['attribute' => $attribute]));
                    }
                },
            ],
        ]);

        $model = Supplier::updateOrCreate(['id' => $request->get('id')], [
            'name' => $request->get('name'),
            'company_id' => $request->get('company_id'),
            'brand' => $request->get('brand'),
            'phone_number' => $request->get('phone_number'),
            'address' => $request->get('address'),
            'discount' => $request->get('discount'),
            'note' => $request->get('note'),
        ]);

        return $this->sendResponse($model);
    }

    public function delete($id)
    {
        $model = Supplier::find($id);
        if (Auth::user()->role === 'secretary' || (Auth::user()->role === 'user' && Auth::user()->company_id != $model->company_id)) {
            return $this->sendErrors(null, __('page.not_allowed'), 403);
        }
        try {
            DB::beginTransaction();
            if ($model->purchases()->exists() || $model->purchase_orders()->exists()) {
                return $this->sendErrors(null, __('page.supplier_cant_delete'));
            }
            $model->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return $this->sendResponse();
    }

    public function report($id)
    {
        $supplier = Supplier::find($id);
        $file_name = "SupplierReport-".Str::slug($supplier->company).".PDF";
        $path = $this->generatePdf($id);
        $file_url = url("storage/$path");
        return $this->sendResponse([
            'file_name' => $file_name,
            'file_url' => $file_url,
        ]);
    }

    public function generatePdf($id) {
        $supplier = Supplier::find($id);
        $pdf = PDF::loadView('reports.supplier', compact('supplier'));
        $path = "generated_pdf/SupplierReport.PDF";
        Storage::disk('public')->put($path, $pdf->output());
        return $path;
    }

    public function getDetail($id) {
        $model = Supplier::find($id);
        return $this->sendResponse($model);
    }
}
