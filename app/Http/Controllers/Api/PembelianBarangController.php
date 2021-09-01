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


class PembelianBarangController extends Controller
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
        $year = $request->year;
        $sql = DB::table('pembelian_barang')
                        ->whereNull('deleted_at')
                        ->where('year', $year)
                        ->where(function($query) use ($request) {
                               $query->where('kode', $request->q)
                                  ->orwhere('supplier_kode', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('keterangan', 'LIKE', '%' . $request->q . '%');
                        });


        $data = $sql->get();

        if (count($data) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Data Tidak ditemukan'
            ], 401);
        }

        $result = [];
        foreach ($data as $key => $value) {
            $detail = DB::table('pembelian_barang_items')
                                    ->where('pembelian_kode', $value->kode)
                                    ->get();
            $result[] = [
                    'kode'          => $value->kode,
                    'supplier_kode' => $value->supplier_kode,
                    'tanggal_beli'  => $value->tanggal_beli,
                    'keterangan'    => $value->keterangan,
                    'created_by'    => $value->created_by,
                    'updated_by'    => $value->updated_by,
                    'created_at'    => $value->created_at,
                    'updated_at'    => $value->updated_at,
                    'detail'        => $detail,
            ];
        }


        return response()->json([
                'success' => true,
                'message' => $result
            ], 200);

    }

    public function store(Request $request)
    {
        $username = \Auth::user()->username ?? "admin";
        $now = date('Y-m-d H:i:s');
        $tanggal_beli = strtotime($request->tanggal_beli);                     
        $year = date("Y", $tanggal_beli); 

        $validator = Validator::make($request->all(), [
            'supplier_kode' => 'required',
            'tanggal_beli'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Isi data Supplier dan Tanggal pembelian",
                ], 422);
        }
        $kode = $this->generateNumber($year);
        
        if ($request->kode != "") {
            $kode = $request->kode;
        }

        $check = DB::table('pembelian_barang')->where('kode', $kode)
                                                ->whereIn('status', ["submit", "approve"])
                                                ->first();

         if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Data sudah ada status dokumen submit atau approve, silahkan di cek kembali",
                ], 422);
        }

        $restore = DB::table('pembelian_barang')->where('kode', $kode)
                                                ->first();

        if ($restore) {
             DB::table('pembelian_barang')
                    ->where('kode', $kode) 
                    ->update([
                        'kantor_kode'   => 1,
                        'year'          => $year,
                        'tanggal_beli'  => $request->tanggal_beli,
                        'supplier_kode' => $request->supplier_kode,
                        'keterangan'    => $request->keterangan,
                        'status'        => $request->status,
                        'next_approval' => 'admin',
                        'updated_by'    => $username,
                        'updated_at'    => $now,
                        'deleted_at'    => NULL,
                    ]);
            $message = "Data Berhasil di update";
        } else {
             DB::table('pembelian_barang')->insert([
                        'kode'          => $kode,
                        'kantor_kode'   => 1,
                        'year'          => $year,
                        'tanggal_beli'  => $request->tanggal_beli,
                        'supplier_kode' => $request->supplier_kode,
                        'keterangan'    => $request->keterangan,
                        'status'        => $request->status,
                        'next_approval' => 'admin',
                        'created_by'    => $username,
                        'created_at'    => $now,
                        'updated_by'    => $username,
                        'updated_at'    => $now,
                        'deleted_at'    => NULL,
            ]);

            $message = "Data Berhasil di tambahkan";
        }

        if (!$request->items) {
             return response()->json([
                'success' => false,
                'message' => "Isi Item barang pembelian",
                ], 422);
        }

        $delete = DB::table('pembelian_barang_items')->where('pembelian_kode', $kode)->delete();
        foreach ($request->items as $key => $row) {
        $total = floatval($row['qty'] * $row['harga_satuan']);

           DB::table('pembelian_barang_items')->insert([
                        'kode'              => uniqid(),
                        'pembelian_kode'    => $kode,
                        'barang_kode'       => $row['barang_kode'],
                        'qty'               => $row['qty'],
                        'unit'              => $row['unit'],
                        'harga_satuan'      => $row['harga_satuan'],
                        'harga_jual_satuan' => $row['harga_jual_satuan'],
                        'harga_total_beli'  => $row['harga_total_beli'] ?? $total,
            ]);
        }

        if ($request->status === "submit") {
            // send notif ke wa
        }

       return response()->json([
                'success' => true,
                'message' => $message,
            ], 200);
    }

    public function reject(Request $request)
    {
        $username = \Auth::user()->username ?? "admin";
        $now = date('Y-m-d H:i:s');

        $validator = Validator::make($request->all(), [
            'kode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Tidak ada kode pembelian",
                ], 422);
        }

        $restore = DB::table('pembelian_barang')
                                     ->where('status', "submit")
                                     ->where('kode', $request->kode)
                                     ->first();

        if (!$restore) {
             return response()->json([
                    'success' => false,
                    'message' => "Data tidak di temukan, periksa kembali status dokumen, dokumen reject harus sudah submit.",
                ], 422);
        }

         DB::table('pembelian_barang')
                    ->where('kode', $request->kode) 
                    ->update([
                        'next_approval' => '-',
                        'updated_by'    => $username,
                        'updated_at'    => $now,
                        'status'        => "reject",
                    ]);

       return response()->json([
                'success' => true,
                'message' => "Data berhasil di reject",
            ], 200);
    }

    public function approve(Request $request)
    {
      $username = \Auth::user()->username ?? "admin";
        $now = date('Y-m-d H:i:s');

        $validator = Validator::make($request->all(), [
            'kode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Tidak ada kode pembelian",
                ], 422);
        }

        $restore = DB::table('pembelian_barang')
                                     ->where('status', 'submit')
                                     ->where('kode', $request->kode)
                                     ->first();

        if (!$restore) {
             return response()->json([
                    'success' => false,
                    'data' => $restore ?? [$request->kode],
                    'message' => "Data tidak di temukan, periksa kembali status dokumen, dokumen approve... harus sudah submit.",
                ], 422);
        }

         DB::table('pembelian_barang')
                    ->where('kode', $request->kode) 
                    ->update([
                        'next_approval' => '-',
                        'updated_by'    => $username,
                        'updated_at'    => $now,
                        'status'        => "approve",
                    ]);

       return response()->json([
                'success' => true,
                'message' => "Data berhasil di approve",
            ], 200);
    }

    private function generateNumber($year) {
        $_f = $year .'10000001';
        $sql =  DB::table('pembelian_barang')
                ->select(DB::raw('MAX(kode) AS kode'))
                ->where('year', 'LIKE', '%' . $year . '%')
                ->orderBy('kode', 'desc')
                ->first();

        $_maxno = $sql->kode;
        if (empty($_maxno)) {
            $no = $_f;
        } else {
            $_sbstr = substr($_maxno, -8);
            $_sbstr++;
            $_new = sprintf("%08s", $_sbstr);
            $no = $year . $_new;
        }

      return $no;
    }

}