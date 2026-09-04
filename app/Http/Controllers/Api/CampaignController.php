<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\HebronPayException;
use App\Http\Requests\Campaign\RedeemCampaignRequest;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignStatusRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PlantingResource;
use App\Models\Campaign;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(): JsonResponse
    {
        $campaigns = Campaign::query()
            ->with('user')
            ->where('visibility', Campaign::VISIBILITY_PUBLIC)
            ->where('status', Campaign::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json([
            'campaigns' => CampaignResource::collection($campaigns),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $campaigns = Campaign::query()
            ->with(['user', 'payment'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        foreach ($campaigns as $index => $campaign) {
            if ($campaign->status !== Campaign::STATUS_PENDING_PAYMENT) {
                continue;
            }
            $campaign->activateIfPaid();
            $campaigns[$index] = $campaign->fresh(['user', 'payment']) ?? $campaign;
        }

        return response()->json([
            'campaigns' => CampaignResource::collection($campaigns),
        ]);
    }

    public function unlocked(Request $request): JsonResponse
    {
        $campaigns = $request->user()
            ->unlockedCampaigns()
            ->with('user')
            ->where('visibility', Campaign::VISIBILITY_INVITE)
            ->where('status', Campaign::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->orderByDesc('campaign_user.created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'campaigns' => CampaignResource::collection($campaigns),
        ]);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $quantity = (int) $data['quantity'];
        $name = trim($data['name']);
        $amount = round(($quantity * Campaign::UNIT_PRICE_CENTS) / 100, 2);

        try {
            $payment = $this->payments->create($request->user(), [
                'amount' => $amount,
                'paymentMethod' => 'pix',
                'description' => Campaign::paymentDescription($name, $quantity),
                'payerName' => $data['payerName'] ?? null,
                'payerCpf' => $data['payerCpf'] ?? null,
            ]);
        } catch (HebronPayException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 502);
        }

        $campaign = Campaign::query()->create([
            'id' => Campaign::newId(),
            'user_id' => $request->user()->id,
            'name' => $name,
            'total' => $quantity,
            'remaining' => $quantity,
            'visibility' => $data['visibility'],
            'per_user_limit' => isset($data['perUserLimit']) ? (int) $data['perUserLimit'] : null,
            'status' => Campaign::STATUS_PENDING_PAYMENT,
            'payment_id' => $payment->id,
        ]);

        Campaign::activatePaid($payment->fresh() ?? $payment);
        $campaign->refresh()->load(['user', 'payment']);

        return response()->json([
            'campaign' => new CampaignResource($campaign),
            'payment' => new PaymentResource($campaign->payment ?? $payment),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::query()->with(['user', 'payment'])->findOrFail($id);

        if (! $campaign->canBeViewedBy($request->user())) {
            abort(404, 'Campanha não encontrada.');
        }

        if ($campaign->isOwner($request->user()) && $campaign->payment) {
            $payment = $campaign->payment;
            if ($payment->isPending()) {
                $payment = $this->payments->refreshFromProvider($payment);
            }
            Campaign::activatePaid($payment);
            $campaign->refresh()->load(['user', 'payment']);
        }

        return response()->json([
            'campaign' => new CampaignResource($campaign),
            'payment' => $campaign->isOwner($request->user()) && $campaign->payment
                ? new PaymentResource($campaign->payment)
                : null,
        ]);
    }

    public function redeem(RedeemCampaignRequest $request): JsonResponse
    {
        $code = (string) $request->validated('code');
        $campaign = Campaign::query()
            ->with('user')
            ->where('invite_code', $code)
            ->first();

        if (! $campaign || $campaign->visibility !== Campaign::VISIBILITY_INVITE) {
            abort(404, 'Código inválido.');
        }

        if ($campaign->status === Campaign::STATUS_PAUSED) {
            abort(422, 'Esta campanha está pausada.');
        }

        if ($campaign->status === Campaign::STATUS_CANCELED) {
            abort(404, 'Esta campanha foi cancelada.');
        }

        if ($campaign->status !== Campaign::STATUS_ACTIVE) {
            abort(404, 'Esta campanha ainda não está disponível.');
        }

        if (! $campaign->isOwner($request->user())) {
            $campaign->unlockedUsers()->syncWithoutDetaching([$request->user()->id]);
        }

        $campaign->load('user');

        return response()->json([
            'campaign' => new CampaignResource($campaign),
        ]);
    }

    public function plantings(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::query()->findOrFail($id);

        if (! $campaign->canBeViewedBy($request->user())) {
            abort(404, 'Campanha não encontrada.');
        }

        $plantings = $campaign->plantings()
            ->with('user')
            ->orderByDesc('planted_at')
            ->limit(500)
            ->get();

        return response()->json([
            'plantings' => PlantingResource::collection($plantings),
        ]);
    }

    public function updateStatus(UpdateCampaignStatusRequest $request, string $id): JsonResponse
    {
        $campaign = Campaign::query()->with(['user', 'payment'])->findOrFail($id);

        if (! $campaign->isOwner($request->user())) {
            abort(403, 'Só quem criou a campanha pode alterar o status.');
        }

        match ($request->validated('status')) {
            Campaign::STATUS_PAUSED => $this->pauseCampaign($campaign),
            Campaign::STATUS_ACTIVE => $this->resumeCampaign($campaign),
            Campaign::STATUS_CANCELED => $this->cancelCampaign($campaign, $request->user()),
            default => abort(422, 'Status inválido.'),
        };

        $campaign->refresh()->load(['user', 'payment']);

        return response()->json([
            'campaign' => new CampaignResource($campaign),
            'payment' => $campaign->payment
                ? new PaymentResource($campaign->payment)
                : null,
        ]);
    }

    private function pauseCampaign(Campaign $campaign): void
    {
        if ($campaign->status !== Campaign::STATUS_ACTIVE) {
            abort(422, 'Só dá para pausar uma campanha ativa.');
        }

        $campaign->forceFill(['status' => Campaign::STATUS_PAUSED])->save();
    }

    private function resumeCampaign(Campaign $campaign): void
    {
        if ($campaign->status !== Campaign::STATUS_PAUSED) {
            abort(422, 'Só dá para retomar uma campanha pausada.');
        }

        if ($campaign->remaining < 1) {
            $campaign->forceFill(['status' => Campaign::STATUS_CLOSED])->save();
            abort(422, 'Esta campanha já encerrou.');
        }

        $campaign->forceFill(['status' => Campaign::STATUS_ACTIVE])->save();
    }

    private function cancelCampaign(Campaign $campaign, User $user): void
    {
        if (in_array($campaign->status, [Campaign::STATUS_CANCELED, Campaign::STATUS_CLOSED], true)) {
            abort(422, 'Esta campanha não pode mais ser cancelada.');
        }

        $payment = $campaign->payment;
        if ($payment && $payment->isPending()) {
            try {
                $this->payments->cancelForUser($user, $payment->id);
            } catch (HebronPayException) {
                $payment->forceFill(['status' => Payment::STATUS_CANCELED])->save();
            }
        }

        $campaign->forceFill(['status' => Campaign::STATUS_CANCELED])->save();
    }
}
