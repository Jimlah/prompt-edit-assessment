<?php

namespace App\Http\Controllers;

use App\Enums\GenerationStatus;
use App\Http\Requests\GenerateImageRequest;
use App\Jobs\GenerateImage;
use App\Models\Generation;
use App\Models\User;
use App\Services\PixelforgePricing;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Prism\Prism\Exceptions\PrismException;
use Throwable;

final class ImageGenerationController extends Controller
{
    public function index(Request $request, PixelforgePricing $pricing): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        return Inertia::render('Dashboard', $this->dashboardProps($user, $pricing));
    }

    public function generate(Request $request, PixelforgePricing $pricing): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        return Inertia::render('Dashboard/Generate', $this->dashboardProps($user, $pricing, limit: 1));
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardProps(User $user, PixelforgePricing $pricing, ?int $limit = null): array
    {
        $sheet = null;
        $pricingError = false;

        try {
            $sheet = $pricing->sheet();
        } catch (Throwable) {
            $pricingError = true; // The estimate picker hides itself; submitting re-checks.
        }

        return [
            'generations' => function () use ($user, $limit): array {
                $query = $user->generations()->latest();

                if ($limit !== null) {
                    $query->limit($limit);
                }

                return $query
                    ->get()
                    ->map(fn (Generation $generation): array => $generation->toDashboard())
                    ->values()
                    ->all();
            },
            'balance_nd' => (string) ($user->credit_balance_nd ?? 0),
            'balance_display' => Money::toDisplay((int) ($user->credit_balance_nd ?? 0)),
            'pricing' => $sheet?->toFrontendArray(),
            'pricing_error' => $pricingError,
            'is_admin' => (bool) $user->is_admin,
        ];
    }

    public function store(GenerateImageRequest $request, PixelforgePricing $pricing): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if (! filled((string) config('prism.providers.pixelforge.api_key'))) {
            throw PrismException::providerResponseError(
                'Image generation is temporarily unavailable. Please try again later.',
                503,
            );
        }

        $validated = $request->validated();

        try {
            $sheet = $pricing->sheet();
            $estimate = $sheet->estimate(
                $validated['prompt'],
                $validated['quality'],
                $validated['size'],
                count($validated['reference_images'] ?? []),
            );
        } catch (Throwable $e) {
            throw PrismException::providerResponseError(
                'Pixelforge pricing is temporarily unavailable. Please try again shortly.',
                503,
            );
        }

        // The user is charged a 25% markup on the provider cost, so the price
        // they are quoted — and what their balance must cover — is the marked-up
        // minimum. The final price is recorded when the generation completes.
        $minimumCharge = Money::withMarkup($estimate['min_nd']);

        if ($user->credit_balance_nd < $minimumCharge) {
            throw ValidationException::withMessages([
                'balance' => 'Insufficient credits: this image will cost at least '
                    .Money::toDisplay($minimumCharge)
                    .', but your balance is '
                    .Money::toDisplay($user->credit_balance_nd).'. Buy credits to continue.',
            ]);
        }

        $generation = $user->generations()->create([
            'prompt' => $validated['prompt'],
            'size' => $validated['size'],
            'quality' => $validated['quality'],
            'reference_images' => $validated['reference_images'] ?? [],
            'status' => GenerationStatus::Queued,
            'estimated_min_nd' => Money::withMarkup($estimate['min_nd']),
            'estimated_max_nd' => Money::withMarkup($estimate['max_nd']),
        ]);

        GenerateImage::dispatch($generation);

        return back()->with('status', 'Image queued for generation.');
    }
}
