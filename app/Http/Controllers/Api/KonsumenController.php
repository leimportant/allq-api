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


class KonsumenController extends Controller
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
        $sql = DB::table('konsumen')
                        ->select([
                            'konsumen.kode', 'konsumen.nama', 'konsumen.ktp_number', 'konsumen.phone_number',
                            'konsumen.nominal_kredit', 'konsumen.jumlah_kredit',
                            'konsumen.alamat', 
                            'konsumen.wilayah_kode',
                            'wilayah.nama as nama_wilayah'
                        ])
                        ->leftJoin('wilayah', 'konsumen.wilayah_kode', '=', 'wilayah.kode')
                        ->whereNull('deleted_at')
                        ->where(function($query) use ($request) {
                               $query->where('konsumen.kode', $request->q)
                                  ->orwhere('konsumen.nama', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('konsumen.ktp_number', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('konsumen.alamat', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('konsumen.phone_number', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('konsumen.nominal_kredit', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('konsumen.jumlah_kredit', 'LIKE', '%' . $request->q . '%')
                                  ->orwhere('wilayah.nama', 'LIKE', '%' . $request->q . '%');
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
            'nama'          => 'required|string|max:100',
            'wilayah_kode'  => 'required',
            'phone_number'  => 'required',
            'ktp_number'    => 'required',
            'alamat'        => 'required',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                ], 422);
        }

        $restore = DB::table('konsumen')->where('kode', $request->kode)->first();

        if ($restore) {
             DB::table('konsumen')
                    ->where('kode', $request->kode) 
                    ->update([
                        'nama'          => $request->nama,
                        'wilayah_kode'  => $request->wilayah_kode,
                        'phone_number'  => $request->phone_number,
                        'ktp_number'    => $request->ktp_number,
                        'alamat'        => $request->alamat,
                        'latitude'      => $request->latitude,
                        'longitude'     => $request->longitude,
                        'photo'         => $request->photo,
                        'updated_by'    => $username,
                        'updated_at'    => $now,
                        'deleted_at'    => NULL,
                    ]);
            $message = "Data Berhasil di update";
        } else {
             DB::table('konsumen')->insert([
                        'kode'          => $this->generateNumber(),
                        'nama'          => $request->nama,
                        'wilayah_kode'  => $request->wilayah_kode,
                        'phone_number'  => $request->phone_number,
                        'ktp_number'    => $request->ktp_number,
                        'alamat'        => $request->alamat,
                        'latitude'      => $request->latitude,
                        'longitude'     => $request->longitude,
                        'photo'         => $request->photo,
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

        $restore = DB::table('konsumen')->where('kode', $request->kode)->first();

        if (!$restore) {
             return response()->json([
                    'success' => false,
                    'message' => "Data tidak di temukan",
                ], 422);
        }

         DB::table('konsumen')
                    ->where('kode', $request->kode) 
                    ->update([
                        'deleted_at' => $now,
                    ]);

       return response()->json([
                'success' => true,
                'message' => "Data berhasil di delete",
            ], 200);
    }

    private function generateNumber() {
        $year = date('y');
        $_f = '00001';
        $sql =  DB::table('konsumen')
                ->select(DB::raw('MAX(kode) AS kode'))
                ->where('kode', 'LIKE', '%' . $year . '%')
                ->orderBy('kode', 'desc')
                ->first();

        $_maxno = $sql->kode;
        if (empty($_maxno)) {
            $no = $year . $_f;
        } else {
            $_sbstr = substr($_maxno, -5);
            $_sbstr++;
            $_new = sprintf("%05s", $_sbstr);
            $no = $year . $_new;
        }

      return $no;
    }
}