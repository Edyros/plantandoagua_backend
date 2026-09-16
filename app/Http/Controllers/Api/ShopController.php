<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\HebronPayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreShopListingRequest;
use App\Http\Requests\Shop\StoreShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Services\PaymentService;
use App\Services\PlantingPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function __construct(
        private PlantingPhotoService $photos,
        private PaymentService $payments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $viewerId = (int) $request->user()->id;
        $shops = Shop::query()
            ->with('user')
            ->where(function ($query) use ($viewerId) {
                $query->where('user_id', $viewerId)
                    ->orWhere(function ($listed) {
                        $listed->where('visible', true)
                            ->whereNotNull('listing_paid_until')
                            ->where('listing_paid_until', '>', now());
                    });
            })
            ->orderBy('name')
            ->limit(500)
            ->get();

        return response()->json([
            'shops' => ShopResource::collection($shops),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $shop = Shop::query()
            ->with(['user', 'listingPayment'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $shop) {
            abort(404, 'Você ainda não cadastrou uma loja.');
        }

        $this->refreshListingPayment($shop);

        return response()->json([
            'shop' => new ShopResource($shop),
            'payment' => $shop->isOwner($request->user()) && $shop->listingPayment
                ? new PaymentResource($shop->listingPayment)
                : null,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $shop = Shop::query()->with(['user', 'listingPayment'])->findOrFail($id);
        $isOwner = $shop->isOwner($request->user());

        if (! $shop->isListedPublicly() && ! $isOwner) {
            abort(404, 'Esta loja está oculta.');
        }

        if ($isOwner) {
            $this->refreshListingPayment($shop);
        }

        return response()->json([
            'shop' => new ShopResource($shop),
            'payment' => $isOwner && $shop->listingPayment
                ? new PaymentResource($shop->listingPayment)
                : null,
        ]);
    }

    public function store(StoreShopRequest $request): JsonResponse
    {
        $data = $this->mapPayload($request->validated());
        $id = $data['id'] ?? (string) Str::uuid();
        unset($data['id']);

        $existing = Shop::query()->find($id);
        if ($existing) {
            if ((int) $existing->user_id !== (int) $request->user()->id) {
                abort(403, 'Esta loja pertence a outro usuário.');
            }

            $existing->fill($data);
            $existing->save();
            $this->storeLogo($request, $existing);
            $existing->load(['user', 'listingPayment']);

            return response()->json([
                'shop' => new ShopResource($existing),
            ]);
        }

        $owned = Shop::query()->where('user_id', $request->user()->id)->first();
        if ($owned) {
            abort(409, 'Sua conta já tem uma loja.');
        }

        $shop = Shop::create([
            ...$data,
            'id' => $id,
            'user_id' => $request->user()->id,
        ]);
        $this->storeLogo($request, $shop);
        $shop->load(['user', 'listingPayment']);

        return response()->json([
            'shop' => new ShopResource($shop),
        ], 201);
    }

    public function update(UpdateShopRequest $request, string $id): JsonResponse
    {
        $shop = Shop::query()->findOrFail($id);

        if (! $shop->isOwner($request->user())) {
            abort(403, 'Esta loja pertence a outro usuário.');
        }

        $shop->fill($this->mapPayload($request->validated()));
        $shop->save();
        $this->storeLogo($request, $shop);
        $shop->load(['user', 'listingPayment']);

        return response()->json([
            'shop' => new ShopResource($shop),
        ]);
    }

    public function listing(StoreShopListingRequest $request, string $id): JsonResponse
    {
        $shop = Shop::query()->with(['user', 'listingPayment'])->findOrFail($id);

        if (! $shop->isOwner($request->user())) {
            abort(403, 'Só quem cadastrou a loja pode pagar a anuidade.');
        }

        $this->refreshListingPayment($shop);

        $open = $shop->listingPayment;
        if ($open && $open->isPending()) {
            return response()->json([
                'shop' => new ShopResource($shop),
                'payment' => new PaymentResource($open),
            ]);
        }

        $data = $request->validated();

        try {
            $payment = $this->payments->create($request->user(), [
                'amount' => Shop::LISTING_PRICE_CENTS / 100,
                'paymentMethod' => 'pix',
                'description' => Shop::listingDescription($shop->name),
                'payerName' => $data['payerName'] ?? null,
                'payerCpf' => $data['payerCpf'] ?? null,
            ]);
        } catch (HebronPayException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 502);
        }

        $shop->forceFill(['listing_payment_id' => $payment->id])->save();
        Shop::activatePaid($payment->fresh() ?? $payment);
        $shop->refresh()->load(['user', 'listingPayment']);

        return response()->json([
            'shop' => new ShopResource($shop),
            'payment' => new PaymentResource($shop->listingPayment ?? $payment),
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $shop = Shop::query()->findOrFail($id);

        if (! $shop->isOwner($request->user())) {
            abort(403, 'Esta loja pertence a outro usuário.');
        }

        $shop->delete();

        return response()->json([
            'message' => 'Loja removida com sucesso.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function mapPayload(array $input): array
    {
        $mapped = [
            'name' => $input['name'] ?? null,
            'description' => $input['description'] ?? null,
            'phone' => $input['phone'] ?? null,
            'city' => $input['city'] ?? null,
            'state' => isset($input['state']) ? strtoupper((string) $input['state']) : null,
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
            'categories' => $input['categories'] ?? null,
            'products' => $input['products'] ?? null,
        ];

        if (array_key_exists('visible', $input)) {
            $mapped['visible'] = (bool) $input['visible'];
        }

        if (isset($input['id'])) {
            $mapped['id'] = $input['id'];
        }

        if (is_array($mapped['categories'])) {
            $mapped['categories'] = array_values(array_unique($mapped['categories']));
        }

        if (is_array($mapped['products'])) {
            $mapped['products'] = array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $mapped['products'],
            )));
        }

        return array_filter($mapped, fn ($value) => $value !== null);
    }

    private function storeLogo(Request $request, Shop $shop): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        $this->photos->deleteMany([$shop->logo_url]);
        $shop->logo_url = $this->photos->storeShopLogo($request->file('logo'), $request->user()->id);
        $shop->save();
    }

    private function refreshListingPayment(Shop $shop): void
    {
        $payment = $shop->listingPayment;
        if (! $payment) {
            return;
        }

        if ($payment->isPending()) {
            $payment = $this->payments->refreshFromProvider($payment);
        }

        Shop::activatePaid($payment);
        $shop->refresh()->load(['user', 'listingPayment']);
    }
}
