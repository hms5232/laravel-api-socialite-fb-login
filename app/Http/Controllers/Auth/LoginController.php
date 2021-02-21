<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->middleware('guest')->except('logout');
    }

    /*
    |--------------------------------------------------------------------------
    | Facebook Login
    |--------------------------------------------------------------------------
    */
    public function fbLogin(Request $request)
    {
        $redirect_url = Socialite::driver('facebook')
            ->scopes(['email'])  // 額外要求要使用者的電子信箱地址
            ->redirect()->getTargetUrl();

        return response()->json(['target' => [$redirect_url]], 302);
    }

    public function fbLoginCallback(Request $request)
    {
        if (is_null($request['code']) || is_null($request['state'])){
            return response()->json(['messages' => ['授權失敗']], 401);
        }

        Session::put('state', $request['state']);  // 不寫入 session 就要 stateless

        try{
            $fb_user = Socialite::driver('facebook')->user();
        } catch (\Exception $exception){
            Log::error($exception);
            return response()->json(['messages' => [strval($exception)]], 401);
        }

        // 確認使用者是否已經使用此方法註冊過
        if (User::where('fb_id', $fb_user->getId())->exists()){  // 有
            // 登入
            Auth::guard('web')->login(User::where('fb_id', $fb_user->getId())->first());
        } else if (User::where('email', $fb_user->getEmail())->exists()){  // 有相同 email 的使用者
            // 更新使用者資料
            DB::transaction(function () use ($fb_user){
                User::where('email', $fb_user->getEmail())->update([
                    'fb_id' => $fb_user->getId()
                ]);
            });

            Auth::guard('web')->login(User::where('email', $fb_user->getEmail())->first());
        }else {  // 沒找到
            // 自動註冊
            DB::transaction(function () use ($fb_user){
                User::create([
                    'name' => $fb_user->getName(),
                    'email' => $fb_user->getEmail(),
                    'password' => Hash::make(uniqid('FB_')),
                    'fb_id' => $fb_user->getId()
                ]);
            });

            Auth::guard('web')->login(User::where('email', $fb_user->getEmail())->where('fb_id', $fb_user->getId())->first());
        }

        return response()->json(['messages' => ['登入成功！']], 200);
    }
}
