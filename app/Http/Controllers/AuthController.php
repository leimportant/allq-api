<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\DB;
use Validator;
use Log;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'registerPhone', 'registerPhoneConfirm']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                ], 422);
        }

        $credentials = request(['username', 'password']);

        $token = auth()->guard('api')->attempt($credentials);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

     /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->guard('api')->logout();

        return response()->json(['message' => 'logged out Berhasil']);
    }

    public function updateProfile(Request $request) {

        DB::beginTransaction();
        try 
        {
        $validator = Validator::make($request->all(), [
            'nama'                  => 'required',
            'username'              => 'required',
            'phone_number'          => 'required',
            'alamat'                => 'required',
            'id'                    => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => "Silahkan isi profile anda dengan lengkap"
                ], 401);
        }    

        if ($request->password !== '' && $request->password_confirmation !== '') {

            if ($request->password !== $request->password_confirmation) {
                return response()->json([
                    'success' => false,
                    'message' => "Password konfirmasi tidak sama, silahkan periksa kembali."
                    ], 401);
            }

            DB::table('users')->where('id', $request->id)
                    ->update([
                        'password'     => bcrypt($request->password),
                    ]);
        }

         $user = DB::table('users')->where('id', $request->id)
                    ->update([
                        'username'     => $request->username,
                        'nama'         => $request->nama,
                        'phone_number' => $request->phone_number,
                        'email'        => $request->email ?? NULL,
                    ]);

          $check =  DB::table('karyawan')->where('user_id', $request->id)->first();

          if ($check) {
                 $karyawan =  DB::table('karyawan')->where('user_id', $request->id)
                                    ->update([
                                         'username'     => $request->username,
                                         'nama'         => $request->nama,
                                         'phone_number' => $request->phone_number,
                                         'alamat'       => $request->alamat,
                                         'email'        => $request->email ?? NULL,
                                         'ktp_number'   => $request->ktp_number ?? NULL,
                                         'longitude'    => $request->longitude,
                                         'latitude'     => $request->latitude,
                                    ]);

          } else {
             $karyawan =  DB::table('karyawan')
                            ->insert([
                                'kode'         => $this->generateNumber(),
                                'user_id'      => $request->id,
                                'username'     => $request->username,
                                'nama'         => $request->nama,
                                'phone_number' => $request->phone_number,
                                'alamat'       => $request->alamat,
                                'longitude'    => $request->longitude,
                                'latitude'     => $request->latitude,
                                'email'        => $request->email ?? NULL,
                                'ktp_number'   => $request->ktp_number ?? NULL,
                            ]);
          }
        
        if ($request->photo) {
            $check_photo =  DB::table('karyawan')->where('user_id', $request->id)->first();
            if ($check_photo->photo) {
                $imagePath_unlink = base_path('public/') . $check_photo->photo;
                 unlink($imagePath_unlink);
            }
           
            $getImage = $request->photo;
            $imageName = 'karyawan' . '_' .time().'.'.$getImage->extension();
            $imagePath = base_path('public/upload/karyawan/');
            $new_filename = 'upload/karyawan/'.$imageName;
            $getImage->move($imagePath, $imageName);
         
             $table = DB::table('karyawan')->where('kode', $check->kode)
                                ->update([
                                    'photo' => $new_filename,
                                    'photo_url' => env('APP_URL') . '/'. $new_filename
                                ]);


        }
         DB::commit();
        } 
        
        catch (\Exception $e) {
          DB::rollback();
        }

        return response()->json([
                'success' => true,
                'message' => 'Data Profile berhasil di update',
            ], 201);

    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerPhone(Request $request) {

        $validator = Validator::make($request->all(), [
            'phone_number' => 'required',
        ]);

        $code = date('Hs');

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => "Silahkan masukan nomor handphone aktif."
                ], 400);
        }    

        $message = "No Registrasi anda " . $code . " silahkan konfirmasi untuk kode ini                                                                                                                                                                            Jangan bagikan kode ini kepada siapapun,                                                                              Terima kasih.";    


        $store =  app(WhatsappService::class)->sendMessage($request->phone_number, $message);

        return response()->json([
            'success' => true,
            'message' => 'Silahkan konfirmasi sesuai kode yang telah dikirimkan ke nomor anda melalui Whatsapp',
            'whatsapp_code' => $code,
            'whatsapp_code_confirmation' => NULL,
        ], 201);
    }

    public function registerPhoneConfirm(Request $request) {

        $validator = Validator::make($request->all(), [
            'phone_number'               => 'required',
            'whatsapp_code'              => 'required',
            'whatsapp_code_confirmation' => 'required',
        ]);
        $request['username'] = $request->phone_number;
        $request['password']= '12345';

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => "Silahkan masukan kode konfirmasi anda"
                ], 400);
        }    

        if ($request->whatsapp_code !== $request->whatsapp_code_confirmation) {
            return response()->json([
                'success' => false,
                'message' => "Kode konfirmasi tidak berlaku, silahkan coba kembali."
                ], 401);
        }

        $message = "Selamat... anda telah berhasil registrasi,                                                                       silahkan lengkapi informasi anda di menu Profile                                                                        Terima kasih.";  

        $check = DB::table('users')->where('phone_number', $request->phone_number)->first();

        if ($check) {
             return response()->json([
                'success' => false,
                'message' => "Nomor Hp ini sudah terdaftar, silahkan cek kembali atau login untuk akses aplikasi"
                ], 401);
        }

         $user = User::create(array_merge(
                    $validator->validated(),
                    [
                        'password' => bcrypt($request->password),
                        'username' => $request->username,
                    ]
                ));

        $store =  app(WhatsappService::class)->sendMessage($request->phone_number, $message);

        $credentials = request(['username', 'password']);

        $token = auth()->guard('api')->attempt($credentials);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        return $this->respondWithToken($token);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        // return $this->createNewToken(auth()->refresh());
        return $this->respondWithToken(auth()->refresh());
    }

    public function forgot() {
        $validator = Validator::make($request->all(), [
            'phone_number'  => 'required',
        ]);

        $code = date('Hs');

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => "Silahkan masukan nomor handphone aktif."
                ], 400);
        }    

        $message = "Kode lupa password anda : " . $code . " silahkan konfirmasi untuk kode ini                                                                                                                                                                            Jangan bagikan kode ini kepada siapapun,                                                                              Terima kasih.";    


        $store =  app(WhatsappService::class)->sendMessage($request->phone_number, $message);

        return response()->json([
            'success' => true,
            'message' => 'Silahkan konfirmasi sesuai kode yang telah dikirimkan ke nomor anda melalui Whatsapp',
            'whatsapp_code' => $code,
            'new_password' => NULL,
            'new_password_confirmation' => NULL,
        ], 201);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {

        $user = auth('api')->user();
        $profile = [];
        if ($user->user_type === 1) {
             $profile= DB::table('karyawan')
                                       ->where('user_id', $user->id)
                                       ->first();

            if (!$profile->photo) {
                $profile->photo = 'upload/avatar.png';
                $profile->photo_url = env('APP_URL') . '/'. $profile->photo;
            }
        }
       
        return response()->json([
            'success'      => true,
            'user'         => $user,
            'profile'      => $profile,
        ]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */

    protected function respondWithToken($token)
    {
        $user = auth('api')->user();
        $profile = [];
        if ($user->user_type === 1) {
             $profile= DB::table('karyawan')
                                       ->where('user_id', $user->id)
                                       ->first();

            if (!$profile->photo) {
                $profile->photo = 'upload/avatar.png';
                $profile->photo_url = env('APP_URL') . '/'. $profile->photo;
            }
        }

        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 8766,
            'user'         => $user,
            'profile'      => $profile,
            'menu'         => [],
        ]);
    }

    protected function createNewToken($token)
    {
        $user = auth()->user();
        $profile = [];
        if ($user->user_type === 1) {
             $profile= DB::table('karyawan')
                                       ->where('user_id', $user->id)
                                       ->first();

            if (!$profile->photo) {
                $profile->photo = 'upload/avatar.png';
                $profile->photo_url = env('APP_URL') . '/'. $profile->photo;
            }
        }

        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 8766, // default 60
            'user'         => $user,
            'profile'      => $profile,
            'menu'         => [],
        ]);
    }

     private function generateNumber() {
        $year = 'EMP'.date('y');
        $_f = '10001';
        $sql =  DB::table('karyawan')
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

     public function upload(Request $request, $status, $kode) {
       
        if ($request['photo']) {
            $filenames = $request->photo;
            $totalFile = sizeof($filenames);

            for ($i = 0; $i < $totalFile; $i++) {

                $filename = $filenames[$i]->getClientOriginalName();
                $ext = explode('.', $filename);
                $extension = strtolower(end($ext));
                $getsize = $filenames[$i]->getSize();

                if ($getsize / 1024 > 2000) {
                    return error('Photo terlalu besar, (Ukuran harus kurang 20MB)');
                }

                $uploads_dir = base_path('public/upload/');
                $new_filename = $kode. '-' . uniqid() .  '.' . $extension;

                $destinationPath = $uploads_dir . $kode; // upload path
                if (!file_exists($destinationPath)) {
                  mkdir($destinationPath, 0777, true);
                }
       
                $filenames[$i]->move($destinationPath, $new_filename);

                if ($status === 'karyawan') {
                    $table = DB::table('karyawan')->where('kode', $kode)
                                            ->update([
                                                'photo' => $new_filename
                                            ]);
                }

                if ($status === 'pelanggan') {
                    
                }

            }           
            
        } else {
             return response()->json([
                'success' => false,
                'message' => "Photo yang di upload tidak sesuai."
                ], 400);
        }

         return response()->json([
                    'success' => false,
                    'message' => "Upload file berhasil."
                ], 400);
    }
}

