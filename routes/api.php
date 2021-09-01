<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


$router->get('/', function () use ($router) {
    $version = new \stdClass();
    $version->name = "ALLQ System Rest Api";
    $version->version = "1";
    return response()->json($version);
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
    Route::post('/register-whatsapp', [App\Http\Controllers\AuthController::class, 'registerPhone']);
    Route::post('/register-whatsapp-confirm', [App\Http\Controllers\AuthController::class, 'registerPhoneConfirm']);
    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);
    Route::post('/forgot-password', [App\Http\Controllers\AuthController::class, 'forgot']);
    Route::post('/refresh', [App\Http\Controllers\AuthController::class, 'refresh']);
    Route::get('/user-profile', [App\Http\Controllers\AuthController::class, 'userProfile']);   
    Route::post('/update-profile', [App\Http\Controllers\AuthController::class, 'updateProfile']);
    
});

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'master'
], function ($router) {
    
    Route::get('/wilayah', [App\Http\Controllers\Api\KategoriController::class, 'wilayah']);  
    Route::get('/provinsi', [App\Http\Controllers\Api\KategoriController::class, 'provinsi']);  
    Route::get('/kategori-memo', [App\Http\Controllers\Api\KategoriController::class, 'kategorimemo']);   
    Route::get('/kategori', [App\Http\Controllers\Api\KategoriController::class, 'index']);    
    Route::post('/kategori/store', [App\Http\Controllers\Api\KategoriController::class, 'store']);
    Route::post('/kategori/delete', [App\Http\Controllers\Api\KategoriController::class, 'delete']);

    Route::get('/jabatan', [App\Http\Controllers\Api\JabatanController::class, 'index']);    
    Route::post('/jabatan/store', [App\Http\Controllers\Api\JabatanController::class, 'store']);
    Route::post('/jabatan/delete', [App\Http\Controllers\Api\JabatanController::class, 'delete']);

    Route::get('/barang', [App\Http\Controllers\Api\BarangController::class, 'index']);    
    Route::post('/barang/store', [App\Http\Controllers\Api\BarangController::class, 'store']);
    Route::post('/barang/delete', [App\Http\Controllers\Api\BarangController::class, 'delete']);

    Route::get('/persentase-omset', [App\Http\Controllers\Api\PersenomsetController::class, 'index']);    
    Route::post('/persentase-omset/store', [App\Http\Controllers\Api\PersenomsetController::class, 'store']);
    Route::post('/persentase-omset/delete', [App\Http\Controllers\Api\PersenomsetController::class, 'delete']);

    Route::get('/operational', [App\Http\Controllers\Api\OperationalController::class, 'index']);    
    Route::post('/operational/store', [App\Http\Controllers\Api\OperationalController::class, 'store']);
    Route::post('/operational/delete', [App\Http\Controllers\Api\OperationalController::class, 'delete']);

    Route::get('/konsumen', [App\Http\Controllers\Api\KonsumenController::class, 'index']);    
    Route::post('/konsumen/store', [App\Http\Controllers\Api\KonsumenController::class, 'store']);
    Route::post('/konsumen/delete', [App\Http\Controllers\Api\KonsumenController::class, 'delete']);

    Route::get('/supplier', [App\Http\Controllers\Api\SupplierController::class, 'index']);    
    Route::post('/supplier/store', [App\Http\Controllers\Api\SupplierController::class, 'store']);
    Route::post('/supplier/delete', [App\Http\Controllers\Api\SupplierController::class, 'delete']);

});

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi'
], function ($router) {
    
    Route::get('/pembelian-barang', [App\Http\Controllers\Api\PembelianBarangController::class, 'index']);    
    Route::post('/pembelian-barang/store', [App\Http\Controllers\Api\PembelianBarangController::class, 'store']);
    Route::post('/pembelian-barang/reject', [App\Http\Controllers\Api\PembelianBarangController::class, 'reject']);
    Route::post('/pembelian-barang/approve', [App\Http\Controllers\Api\PembelianBarangController::class, 'approve']);

});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
