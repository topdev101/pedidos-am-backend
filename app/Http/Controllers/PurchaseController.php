<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrderItem;
use App\Traits\UploadAble;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use PDF;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    use UploadAble;

    public function search(Request $request)
    {
        $mod = new Purchase();
        $mod = $mod->with('supplier', 'company', 'purchase_order');

        if ($request->get('supplier_id') != "") {
            $supplier_id = $request->get('supplier_id');
            $mod = $mod->where('supplier_id', $supplier_id);
        }

        if ($request->get('company_id') != "") {
            $company_id = $request->get('company_id');
            $mod = $mod->where('company_id', $company_id);
        }
        if ($request->get('keyword') != "") {
            $keyword = $request->keyword;
            $mod = $mod->where(function ($query) use ($keyword) {
                return $query->where('reference_no', 'like', "%$keyword%")
                    ->orWhereHas('supplier', function ($query) use ($keyword) {
                        return $query->where('company', 'like', "%$keyword%")
                                    ->orWhere('name', 'like', "%$keyword%");
                    })
                    ->orWhereHas('purchase_order', function ($query) use ($keyword) {
                        return $query->where('reference_no', 'like', "%$keyword%");
                    })
                    ->orWhere('total_amount', 'like', "%$keyword%");
            });
        }

        if ($request->get('startDate') != '' && $request->get('endDate') != '') {
            if ($request->get('startDate') === $request->get('endDate')) {
                $mod = $mod->whereDate('purchased_at', $request->get('startDate'));
            } else {
                $mod = $mod->whereBetween('purchased_at', [$request->get('startDate'), $request->get('endDate')]);
            }
        }

        $per_page = $request->get('per_page');
        $data = $mod->orderBy('created_at', 'desc')->paginate($per_page);
        return $this->sendResponse($data);
    }

    public function create(Request $request)
    {
        $request->validate([
            'purchased_at' => 'required',
            'reference_no' => 'required|string',
            'supplier' => 'required',
        ]);

        if (Purchase::where('reference_no', $request->get('reference_no'))->where('supplier_id', $request->get('supplier'))->exists()) {
            return $this->sendErrors(['reference_no' => [__('validation.unique', ['attribute' => 'reference number'])]], '', 422);
        }

        $model = new Purchase();
        $model->user_id = Auth::id();
        $model->company_id = $request->get('company_id');
        $model->store_id = $request->get('store_id');
        $model->purchased_at = Carbon::parse($request->get('purchased_at'))->format('Y-m-d H:i:s');
        $model->reference_no = $request->get('reference_no');
        $model->supplier_id = $request->get('supplier');
        $model->shipping_carrier = $request->get('shipping_carrier');
        $model->note = $request->get('note');
        $model->total_amount = $request->get('total_amount');
        $model->save();

        $nameElements = [];
        if ($model->company) $nameElements[] = $model->company->name;
        if ($model->reference_no) $nameElements[] = $model->reference_no;
        if ($model->supplier->company) $nameElements[] = $model->supplier->company;
        $imageName = Str::slug(implode(' ', $nameElements), '_');
        if ($request->file("images")) {
            $this->uploadFiles($request->file('images'), $imageName, 'purchases', $model);
        }
        return $this->sendResponse($model);
    }

    public function update(Request $request) {
        $request->validate([
            'reference_no' => 'required|string',
            'store' => 'required',
        ]);

        $model = Purchase::find($request->get('id'));
        $model->store_id = $request->get('store');
        $model->reference_no = $request->get('reference_no');
        $model->shipping_carrier = $request->get('shipping_carrier');
        $model->note = $request->get('note');
        $model->total_amount = $request->get('total_amount');
        $model->save();

        $nameElements = [];
        if ($model->company) $nameElements[] = $model->company->name;
        if ($model->reference_no) $nameElements[] = $model->reference_no;
        if ($model->supplier->company) $nameElements[] = $model->supplier->company;
        $imageName = Str::slug(implode(' ', $nameElements), '_');
        if ($request->file("images")) {
            $this->uploadFiles($request->file('images'), $imageName, 'purchases', $model);
        }

        $items = json_decode($request->get('items'), true);
        foreach($items as $item){
            $purchase_order_item = PurchaseOrderItem::find($item['purchase_order_item_id']);
            $purchase_item = PurchaseItem::find($item['id']);
            if (!$purchase_item) {
                $purchase_item = new PurchaseItem();
                $purchase_item->purchase_id = $model->id;
                $purchase_item->category_id = $purchase_order_item->category_id;
                $purchase_item->purchase_order_item_id = $item['purchase_order_item_id'];
                $purchase_item->product = $purchase_order_item->product;
                $purchase_item->cost = $purchase_order_item->cost - $purchase_order_item->discount;
            }
            $purchase_item->quantity = $item['receive'];
            $purchase_item->amount = ($purchase_order_item->cost - $purchase_order_item->discount) * $item['receive'];
            $purchase_item->save();
            if (!$item['id']) {
                foreach ($purchase_order_item->images as $image) {
                    $purchase_item->images()->create([
                        'mime' => $image->mime,
                        'disk' => $image->disk,
                        'path' => $image->path,
                    ]);
                }
            }
        }
        return $this->sendResponse($model);
    }

    public function getDetail($id)
    {
        $model = Purchase::find($id);
        return $this->sendResponse($model->load('items', 'user', 'supplier', 'images', 'store.company'));
    }

    public function delete($id)
    {
        if (Auth::user()->role === 'secretary') {
            return $this->sendErrors(null, __('page.not_allowed'), 403);
        }
        try {
            DB::beginTransaction();
            $model = Purchase::find($id);
            if (!$model) {
                return $this->sendErrors(["delete" => __('page.something_went_wrong')]);
            }
            foreach ($model->images as $image) {
                if ($this->fileExists($image->path, $image->disk)) {
                    $this->deleteFile($image->path, $image->disk);
                }
                $image->delete();
            }
            foreach ($model->items as $item) {
                foreach ($item->images as $image) {
                    if ($this->fileExists($image->path, $image->disk)) {
                        $this->deleteFile($image->path, $image->disk);
                    }
                    $image->delete();
                }
                $item->delete();
            }
            $model->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
        return $this->sendResponse();
    }

    public function report($id) {
        $purchase = Purchase::find($id);
        $file_name = "PurchaseReport-".$purchase->reference_no.".PDF";
        $path = $this->generatePdf($id);
        $file_url = Storage::disk('public')->url($path);
        return $this->sendResponse([
            'file_name' => $file_name,
            'file_url' => $file_url,
        ]);
    }

    public function generatePdf($id) {
        $purchase = Purchase::find($id);
        $pdf = PDF::loadView('reports.purchase', compact('purchase'));
        $path = "generated_pdf/PurchaseReport.PDF";
        Storage::disk('public')->put($path, $pdf->output(), 'public');
        return $path;
    }

    public function getNextReferenceNo() {
        $lastModel = Purchase::orderBy('id', 'desc')->first();
        if ($lastModel) {
            $lastReferenceNo = $lastModel->reference_no;
            $lastReferenceNo = explode("-", $lastReferenceNo);
            $lastReferenceNo = $lastReferenceNo[1];
            $lastReferenceNo = str_pad($lastReferenceNo + 1, 6, "0", STR_PAD_LEFT);
            $referenceNo = "COMPRAS-". $lastReferenceNo;
        } else {
            $referenceNo = "COMPRAS-000001";
        }
        return $this->sendResponse($referenceNo);
    }
}
