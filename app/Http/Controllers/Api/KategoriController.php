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


class KategoriController extends Controller
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
     public function index(Request $request)
    {
        $sql = DB::table('kategori')
                        ->whereNull('deleted_at')
                        ->where(function($query) use ($request) {
                               $query->where('kode', $request->q)
                                  ->orwhere('nama', 'LIKE', '%' . $request->q . '%');
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
            'nama' => 'required|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                ], 422);
        }

        $restore = DB::table('kategori')->where('kode', $request->kode)->first();

        if ($restore) {
             DB::table('kategori')
                    ->where('kode', $request->kode) 
                    ->update([
                        'nama'       => $request->nama,
                        'updated_by' => $username,
                        'updated_at' => $now,
                        'deleted_at' => NULL,
                    ]);
            $message = "Data Berhasil di update";
        } else {
             DB::table('kategori')->insert([
                'nama'       => $request->nama,
                'created_by' => $username,
                'created_at' => $now,
                'updated_by' => $username,
                'updated_at' => $now,
                'deleted_at' => NULL,
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

        $restore = DB::table('kategori')->where('kode', $request->kode)->first();

        if (!$restore) {
             return response()->json([
                    'success' => false,
                    'message' => "Data tidak di temukan",
                ], 422);
        }

         DB::table('kategori')
                    ->where('kode', $request->kode) 
                    ->update([
                        'deleted_at' => $now,
                    ]);

       return response()->json([
                'success' => true,
                'message' => "Data berhasil di delete",
            ], 200);
    }

    public function kategorimemo(Request $request)
    {
        $sql = DB::table('kategori_memo')
                        ->whereNull('deleted_at')
                        ->where(function($query) use ($request) {
                               $query->where('kode', $request->q)
                                  ->orwhere('nama', 'LIKE', '%' . $request->q . '%');
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

    public function wilayah(Request $request) {
        $sql = DB::table('wilayah')
                        ->whereNull('deleted_at')
                        ->where(function($query) use ($request) {
                               $query->where('kode', $request->q)
                                  ->orwhere('nama', 'LIKE', '%' . $request->q . '%');
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

}