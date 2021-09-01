<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Log;


class BarangController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api');
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
// https://www.positronx.io/laravel-jwt-authentication-tutorial-user-login-signup-api/
     public function index(Request $request)
    {
        $sql = DB::table('master_barang')
                        ->whereNull('deleted_at')
                        ->where(function($query) use ($request) {
                               $query->where('kode', $request->q)
                                  ->orwhere('nama', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('kategori_kode', 'LIKE', '%' . $request->q . '%');
                        });


        $data = $sql->get();

        if (count($data) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Data Tidak ditemukan'
            ], 401);
        }

        return response()->json([
                'success' => true,
                'message' => $data
            ], 200);

    }

    public function store(Request $request)
    {
        $username = \Auth::user()->username ?? "admin";
        $now = date('Y-m-d H:i:s');

        $validator = Validator::make($request->all(), [
            'nama'          => 'required|string|max:150',
            'kategori_kode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                ], 422);
        }

        $restore = DB::table('master_barang')->where('kode', $request->kode)->first();

        if ($restore) {
             DB::table('master_barang')
                    ->where('kode', $request->kode) 
                    ->update([
                        'nama'          => $request->nama,
                        'kategori_kode' => $request->kategori_kode,
                        'updated_by'    => $username,
                        'updated_at'    => $now,
                        'deleted_at'    => NULL,
                    ]);
            $message = "Data Berhasil di update";
        } else {
             DB::table('master_barang')->insert([
                'kode'          => $this->generateNumber($request->kategori_kode),
                'nama'          => $request->nama,
                'kategori_kode' => $request->kategori_kode,
                'created_by'    => $username,
                'created_at'    => $now,
                'updated_by'    => $username,
                'updated_at'    => $now,
                'deleted_at'    => NULL,
            ]);

            $message = "Data Berhasil di tambahkan";
        }

       return response()->json([
                'success' => true,
                'message' => $message,
            ], 200);
    }

    public function delete(Request $request)
    {
        $username = \Auth::user()->username ?? "admin";
        $now = date('Y-m-d H:i:s');

        $validator = Validator::make($request->all(), [
            'kode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                ], 422);
        }

        $restore = DB::table('master_barang')->where('kode', $request->kode)->first();

        if (!$restore) {
             return response()->json([
                    'success' => false,
                    'message' => "Data tidak di temukan",
                ], 422);
        }

         DB::table('master_barang')
                    ->where('kode', $request->kode) 
                    ->update([
                        'deleted_at' => $now,
                    ]);

       return response()->json([
                'success' => true,
                'message' => "Data berhasil di delete",
            ], 200);
    }

    private function generateNumber($kategori_kode) {
        $_f = '0001';
        $sql =  DB::table('master_barang')
                ->select(DB::raw('MAX(kode) AS kode'))
                ->where('kategori_kode', 'LIKE', '%' . $kategori_kode . '%')
                ->orderBy('kode', 'desc')
                ->first();

        $_maxno = $sql->kode;
        if (empty($_maxno)) {
            $no = $kategori_kode . $_f;
        } else {
            $_sbstr = substr($_maxno, -4);
            $_sbstr++;
            $_new = sprintf("%04s", $_sbstr);
            $no = $kategori_kode . $_new;
        }

      return $no;
    }

}