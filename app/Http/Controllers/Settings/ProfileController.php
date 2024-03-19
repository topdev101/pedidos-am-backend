<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\UploadAble;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use UploadAble;

    /**
     * Update the user's profile information.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $this->validate($request, [
            'username' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->username = $request->get('username');
        $user->email = $request->get('email');
        $user->first_name = $request->get('first_name');
        $user->last_name = $request->get('last_name');
        $user->phone_number = $request->get('phone_number');
        $user->save();

        if ($request->file('photo')) {
            $disk = config('filesystems.image_storage_disk');
            $path = $this->uploadThumbnail($request->file('photo'), 'users', $disk);
            $user->image()->create([
                'mime' => 'image',
                'disk' => $disk,
                'path' => $path,
            ]);
        }

        return $this->sendResponse($user);
    }

    public function generate2FACode(Request $request) {
        if ($request->get('user_id') != '') {
            $user = User::find($request->get('user_id'));
        } else {
            $user = Auth::user();
        }
        $google2fa = app('pragmarx.google2fa');
        $google2fa_secret = $user->google2fa_secret;
        if ($request->get('regenerate') != '') {
            $google2fa_secret = $google2fa->generateSecretKey();
            $user->update([
                'google2fa_secret' => $google2fa_secret
            ]);
        }
        $QR_Image = null;
        if ($google2fa_secret) {
            $QR_Image = $google2fa->getQRCodeInline(
                config('app.name'),
                $user->username,
                $google2fa_secret
            );
        }
        $user->update([
            'enable_google2fa' => 1,
        ]);
        return $this->sendResponse(['secret_code' => $user->google2fa_secret, 'qr_code' => $QR_Image]);
    }

    public function checkOTP(Request $request) {
        $request->validate([
            'one_time_password' => 'required|digits:6'
        ]);
        $google2fa = app('pragmarx.google2fa');
        $secret = $request->get('google2fa_secret');
        $token = $request->get('one_time_password');
        $result = $google2fa->verifyKey($secret, $token, config('google2fa.window'));
        if (getenv('GOOGLE_2FA_DISABLED')) {
            return $this->sendResponse(true);
        }
        if ($result) {
            return $this->sendResponse($result);
        } else {
            return $this->sendErrors(['one_time_password' => [__('page.wrong_otp')]], '', 422);
        }
    }
}
