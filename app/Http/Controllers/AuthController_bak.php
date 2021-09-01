<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\WhatsappService;
use Validator;
use Log;


class AuthController_bak extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
// https://www.positronx.io/laravel-jwt-authentication-tutorial-user-login-signup-api/
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                ], 422);
        }

        $credentials = request(['email', 'password']);

        $token = auth()->guard('api')->attempt($credentials);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function updateProfile(Request $request) {

    }

    public function login2(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
                ], 401);
        }

        return $this->createNewToken($token);
    }


    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
                ], 400);
        }

        $user = User::create(array_merge(
                    $validator->validated(),
                    ['password' => bcrypt($request->password)]
                ));

        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

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
                ], 400);
        }

        $message = "Selamat... anda telah berhasil registrasi,                                                                       silahkan lengkapi informasi anda di menu Profile                                                                        Terima kasih.";    

        $store =  app(WhatsappService::class)->sendMessage($request->phone_number, $message);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil',
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout2() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
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

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json([
            'success'      => true,
            'currency'     => 'IDR / Rupiah',
            'credit_total' => 120000,
            'saldo_total'  => 340000,
            'profile'      => auth()->user(),
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
        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => auth('api')->user()
        ]);
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 1200, // default 60
            'user' => auth()->user()
        ]);
    }

}