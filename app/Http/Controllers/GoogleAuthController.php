<?php
namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(string $type)
    {
        abort_unless(in_array($type,['user','seller']),404);
        $redirect=$type==='seller'
            ? config('services.google.seller_redirect')
            : config('services.google.user_redirect');

        return Socialite::driver('google')
            ->redirectUrl($redirect)
            ->stateless()
            ->redirect();
    }

    public function callback(string $type)
    {
        abort_unless(in_array($type,['user','seller']),404);

        try {
            $redirect=$type==='seller'
                ? config('services.google.seller_redirect')
                : config('services.google.user_redirect');

            $google=Socialite::driver('google')
                ->redirectUrl($redirect)
                ->stateless()
                ->user();

            $user=User::where('google_id',$google->getId())
                ->orWhere('email',$google->getEmail())
                ->first();

            if($user){
                if($user->role!==($type==='seller'?'seller':'user')){
                    return $this->error(
                        $type==='seller'?'seller/login':'login',
                        $type==='seller'
                            ?'This email belongs to a customer account.'
                            :'This email belongs to a seller or administrator account.'
                    );
                }

                $user->update([
                    'google_id'=>$google->getId(),
                    'avatar'=>$google->getAvatar(),
                ]);
            }else{
                $user=User::create([
                    'name'=>$google->getName()?:'Google User',
                    'email'=>$google->getEmail(),
                    'google_id'=>$google->getId(),
                    'avatar'=>$google->getAvatar(),
                    'password'=>null,
                    'role'=>$type==='seller'?'seller':'user',
                    'status'=>$type==='seller'?'pending':'active',
                    'verification_status'=>'unverified',
                    'is_blocked'=>false,
                ]);
            }

            if($user->is_blocked){
                return $this->error(
                    $type==='seller'?'seller/login':'login',
                    'Your account has been suspended.'
                );
            }

            if($type==='seller'){
                if($user->status==='pending'){
                    return $this->error('seller/login','Your request is still awaiting approval.');
                }

                if($user->status==='rejected'){
                    return $this->error('seller/login','Your seller request was rejected.');
                }
            }

            $token=$user->createToken('auth_token')->plainTextToken;

            return redirect(
                config('app.frontend_url',env('FRONTEND_URL')) .
                '/auth/google/success?token=' . urlencode($token) .
                '&type=' . urlencode($type)
            );
        }catch(\Throwable $e){
            report($e);

            return $this->error(
                $type==='seller'?'seller/login':'login',
                'Google authentication failed.'
            );
        }
    }

    private function error(string $page,string $message)
    {
        return redirect(
            config('app.frontend_url',env('FRONTEND_URL')) .
            '/' . ltrim($page,'/') .
            '?error=' . urlencode($message)
        );
    }
}