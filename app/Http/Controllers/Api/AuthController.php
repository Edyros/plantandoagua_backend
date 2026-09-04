<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdatePreferencesRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PlantingPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private PlantingPhotoService $photos) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'uuid' => $request->input('id', (string) Str::uuid()),
            'name' => $request->string('name')->trim(),
            'email' => $request->string('email')->lower()->trim(),
            'password' => $request->string('password'),
            'phone' => $request->string('phone')->trim(),
            'cpf' => $request->input('cpf'),
            'profile_complete' => 40,
            'appear_on_community_map' => true,
            'public_profile' => true,
            'show_city_on_profile' => true,
            'pin_precision' => 'exact',
            'monthly_goal' => 20,
            'default_map_filter' => 'mine',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha incorretos.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'cpf' => $data['cpf'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            $this->photos->deleteMany([$user->avatar_url]);
            $user->avatar_url = $this->photos->storeAvatar($request->file('avatar'), $user->id);
        }

        $user->profile_complete = $this->profileComplete($user);
        $user->save();

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        return response()->json([
            'preferences' => $request->user()->preferencePayload(),
        ]);
    }

    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $map = [
            'appearOnCommunityMap' => 'appear_on_community_map',
            'publicProfile' => 'public_profile',
            'showCityOnProfile' => 'show_city_on_profile',
            'pinPrecision' => 'pin_precision',
            'monthlyGoal' => 'monthly_goal',
            'defaultMapFilter' => 'default_map_filter',
        ];

        foreach ($map as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $user->{$snake} = $data[$camel];
            }
        }

        $user->save();

        return response()->json([
            'preferences' => $user->preferencePayload(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    private function profileComplete(User $user): int
    {
        $score = 40;
        if (filled($user->city) && filled($user->state)) {
            $score += 30;
        }
        if (filled($user->cpf)) {
            $score += 15;
        }
        if (filled($user->avatar_url)) {
            $score += 15;
        }

        return min(100, $score);
    }
}
