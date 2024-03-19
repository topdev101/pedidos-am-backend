<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function search(Request $request)
    {
        $mod = new Company();
        if ($request->get('keyword') != '') {
            $keyword = $request->get('keyword');
            $mod = $mod->where('name', 'like', "%$keyword%");
        }
        $per_page = $request->get('per_page');
        if ($request->get('page')) {
            $data = $mod->paginate($per_page ? $per_page : 10);
        } else {
            $data = $mod->get();
        }
        return $this->sendResponse($data);
    }

    public function save(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);
        $model = Company::updateOrCreate(['id' => $request->get('id')], [
            'name' => $request->get('name'),
        ]);
        return $this->sendResponse($model);
    }

    public function getDetail($id)
    {
        return $this->sendResponse(Company::find($id));
    }

    public function delete($id)
    {
        Company::destroy($id);
        return $this->sendResponse();
    }
}
