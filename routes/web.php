<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


/*
 * 請從這邊開始看，上面是自動產生的
 *
 * 建議註解 Auth 那行，避免不必要的影響
 */

// FB 登入
Route::get('/auth/facebook-login', [App\Http\Controllers\Auth\LoginController::class, 'fbLogin']);

// FB 登入 callback
Route::get('/auth/facebook-login-callback', [App\Http\Controllers\Auth\LoginController::class, 'fbLoginCallback']);
