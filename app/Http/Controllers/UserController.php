<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function current(Request $request)
    {
        return response()->json($request->user());
    }

    public function search(Request $request)
    {
        $mod = new User();
        if ($request->get('keyword') != "") {
            $keyword = $request->get('keyword');
            $mod = $mod->where(function ($query) use ($keyword) {
                return $query->where('username', 'like', "%$keyword%")
                    ->orWhere('first_name', 'like', "%$keyword%")
                    ->orWhere('last_name', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%")
                    ->orWhere('phone_number', 'like', "%$keyword%");
            });
        }
        $per_page = $request->get('per_page');
        $data = $mod->orderBy('created_at', 'desc')->paginate($per_page);
        return $this->sendResponse($data);
    }

    public function update(Request $request)
    {
        $validation_rules = [
            'username' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'phone_number' => 'required',
        ];
        if ($request->get('password') != '') {
            $validation_rules['password'] = [
                'confirmed',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[@$!%*#?&]/', // must contain a special character
            ];
        }
        $request->validate($validation_rules);
        $model = User::find($request->get('id'));
        $model->username = $request->get("username");
        $model->phone_number = $request->get("phone_number");
        $model->first_name = $request->get("first_name");
        $model->last_name = $request->get("last_name");
        $model->ip_address = $request->get("ip_address");
        // $model->enable_google2fa = $request->get("enable_google2fa");

        if ($request->get('password') != '') {
            if (Hash::check($request->get('password'), $model->password)) {
                return $this->sendErrors(['password' => [__('page.same_password')]], '', 422);
            }
            $model->password = Hash::make($request->get('password'));
        }
        $model->save();
        return $this->sendResponse($model);
    }

    public function create(Request $request)
    {
        $validate_array = array(
            'username' => 'required|string|unique:users',
            'phone_number' => 'required',
            'password' => [
                'required',
                'confirmed',
                'string',
                'min:8',                // must be at least 10 characters in length
                'regex:/[a-z]/',        // must contain at least one lowercase letter
                'regex:/[A-Z]/',        // must contain at least one uppercase letter
                'regex:/[@$!%*#?&]/',   // must contain a special character
            ],
        );
        if ($request->get('role') == 'user' || $request->get('role') == 'secretary') {
            $validate_array['company_id'] = 'required';
        }

        $request->validate($validate_array);

        $model = User::create([
            'username'      => $request->get('username'),
            'company_id'    => $request->get('company_id'),
            'first_name'    => $request->get('first_name'),
            'last_name'     => $request->get('last_name'),
            'role'          => $request->get('role'),
            'phone_number'  => $request->get('phone_number'),
            'ip_address'    => $request->get('ip_address'),
            'password'      => Hash::make($request->get('password'))
        ]);

        return $this->sendResponse($model);
    }

    public function getDetail($id) {
        return $this->sendResponse(User::find($id));
    }

    public function delete($id)
    {
        User::destroy($id);
        return $this->sendResponse();
    }
}
