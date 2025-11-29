<?php

namespace App\Http\Controllers;

use App\Enums\AuthProvider;
use App\Enums\RoleId;
use App\Enums\UserTokenType;
use App\Models\User;
use App\Models\UserProvider;
use App\Models\UserToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use MrGarest\EchoApi\EchoApi;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function logIn()
    {
        return Socialite::driver(AuthProvider::TWITCH->value)->redirect();
    }

    private function getAuthView(array $jsonPageData)
    {
        return view('auth', [
            'jsonPageData' => $jsonPageData
        ]);
    }

    public function callback()
    {
        $provider = AuthProvider::TWITCH->value;
        try {
            $socialite = Socialite::driver($provider)->user();
        } catch (\Exception $ex) {
            $socialite = null;
        }

        if (!$socialite) return $this->getAuthView(['error' => 'auth.failed']);

        $userProvider = UserProvider::findUser($provider, $socialite->id)->with(['user' => function ($query) {
            $query->withTrashed();
        }])->first();

        if ($userProvider) {
            $user = $userProvider->user;
            if ($user->trashed()) return $this->getAuthView(['error' => 'auth.accountHasBeenDeleted']);
            if ($user->deactivated_at != null) return $this->getAuthView(['error' => 'auth.accountHasBeenDeactivated']);

            $user->update([
                'username' => $socialite->name,
                'email' => null,
                'picture' => null
            ]);

            $userProvider->update([
                'name' => $socialite->name,
            ]);
        } else {

            $user = DB::transaction(function () use ($provider, $socialite) {
                $now = Carbon::now();
                $user = User::create([
                    'role_id' => RoleId::USER->value,
                    'username' => $socialite->name,
                    // 'email' => $socialite->email,
                    'email' => null,
                    'password' => null,
                    'picture' => null,
                    'email_verified_at' => null
                ]);

                UserProvider::insert([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'account_id' => $socialite->id,
                    'name' => $socialite->name,
                    'token_type' => null,
                    'access_token' => null,
                    'refresh_token' => null,
                    'expires_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
                return $user;
            });
        }

        if (!$user) return $this->getAuthView(['error' => 'auth.failedCreateUser']);

        $now = Carbon::now();
        $token = Str::random(32);
        UserToken::insert([
            'user_id' => $user->id,
            'type' => UserTokenType::AUTH->value,
            'token' => $token,
            'expires_at' => $now->copy()->addHours(1),
            'created_at' => $now,
            'updated_at' => $now
        ]);


        return $this->getAuthView([
            'auth' => [
                'id' => $user->id,
                'token' => $token,
            ]
        ]);
    }

    public function extension(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|int',
            'token' => 'required|string'
        ]);

        if ($validator->fails()) return EchoApi::httpError(Response::HTTP_BAD_REQUEST);
        $data = $validator->getData();

        $userToken = UserToken::with('user')->userId($data['id'])->type(UserTokenType::AUTH->value)->token($data['token'])->first();

        $user = $userToken->user ?? null;
        if (!$userToken || !$user) return EchoApi::findError('USER_NOT_FOUND');

        UserToken::userId($user->id)->type(UserTokenType::AUTH->value)->delete();

        $token = $user->createToken('auth', ['*']);

        return EchoApi::success([
            'id' => $user->id,
            'role_id' => $user->role_id,
            'username' => $user->username,
            'access_token' => $token->accessToken,
            'expires_at' => Carbon::now()->addMonths(10)->timestamp
        ]);
    }
}
