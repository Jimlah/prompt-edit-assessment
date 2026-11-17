<?php

namespace App\Prism\Providers;

use App\Exceptions\PixelforgePermanentException;
use App\Exceptions\PixelforgeThrottledException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Images\Request as ImagesRequest;
use Prism\Prism\Images\Response as ImagesResponse;
use Prism\Prism\Providers\Provider;
use Prism\Prism\ValueObjects\GeneratedImage;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;
use SensitiveParameter;

/**
 * @phpstan-type PixelGenerationUsage array{input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int, total_tokens: int}
 * @phpstan-type PixelGenerationError array{code: string, message: string}
 * @phpstan-type PixelGenerationRateMap array<string, string>
 * @phpstan-type PixelGeneration array{id: string, object: string, status: string, prompt: string, size: string, quality: string, reference_images: array<int, string>, metadata: array<string, string>, image_url: ?string, image_url_expires_at: ?string, usage: PixelGenerationUsage|null, rates: PixelGenerationRateMap|null, error: PixelGenerationError|null, completed_at: ?string, created_at: ?string}
 * @phpstan-type PixelUsageRow array{date: string, generations: int, input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int, cost_usd: string}
 */
class PixelForgeProvider extends Provider
{
    public function __construct(
        #[SensitiveParameter] protected string $apiKey,
        protected string $url,
        protected int $pollInterval = 2,
    ) {}

    public function pollInterval(): int
    {
        return $this->pollInterval;
    }

    public function images(ImagesRequest $request): ImagesResponse
    {
        $size = $request->providerOptions('size') ?? '1024x1024';
        $quality = $request->providerOptions('quality') ?? 'medium';
        $idempotencyKey = $request->providerOptions('idempotency_key');

        if (! is_string($size) || ! is_string($quality)) {
            throw PrismException::providerResponseError('PixelForge size and quality options must be strings.');
        }

        if (! is_string($idempotencyKey) && $idempotencyKey !== null) {
            throw PrismException::providerResponseError('The PixelForge idempotency key must be a string.');
        }

        $generation = $this->createGeneration(
            prompt: $request->prompt() ?? '',
            size: $size,
            quality: $quality,
            referenceImages: $this->stringList($request->providerOptions('reference_images') ?? [], 'reference_images'),
            metadata: $this->stringMap($request->providerOptions('metadata') ?? [], 'metadata'),
            idempotencyKey: $idempotencyKey,
            clientOptions: $request->clientOptions(),
        );

        $generation = $this->waitForGeneration($generation['id'], $request->clientOptions());

        return $this->toImageResponse($generation, $request->model());
    }

    /**
     * @param  array<int, string>  $referenceImages
     * @param  array<string, string>  $metadata
     * @param  array<string, mixed>  $clientOptions
     * @return PixelGeneration
     */
    public function createGeneration(
        string $prompt,
        string $size = '1024x1024',
        string $quality = 'medium',
        array $referenceImages = [],
        array $metadata = [],
        ?string $idempotencyKey = null,
        array $clientOptions = [],
    ): array {
        $payload = [
            'prompt' => $prompt,
            'size' => $size,
            'quality' => $quality,
        ];

        if ($referenceImages !== []) {
            $payload['reference_images'] = $referenceImages;
        }

        if ($metadata !== []) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->client($clientOptions)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey ?? (string) Str::uuid()])
            ->post('/generations', $payload)
            ->throw();

        return $this->parseGeneration($response->json());
    }

    /**
     * @param  array<string, mixed>  $clientOptions
     * @return PixelGeneration
     */
    public function getGeneration(string $id, array $clientOptions = []): array
    {
        $response = $this->client($clientOptions)
            ->get('/generations/'.rawurlencode($id))
            ->throw();

        return $this->parseGeneration($response->json());
    }

    /**
     * What Pixelforge billed for this API key, per UTC day. The source of truth
     * for reconciling the provider costs we recorded.
     *
     * @param  string  $startDate  YYYY-MM-DD, inclusive.
     * @param  string  $endDate  YYYY-MM-DD, inclusive.
     * @return array{data: array<int, PixelUsageRow>, total: PixelUsageRow|null}
     */
    public function usage(string $startDate, string $endDate, array $clientOptions = []): array
    {
        $response = $this->client($clientOptions)
            ->get('/usage', ['start_date' => $startDate, 'end_date' => $endDate])
            ->throw();

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            throw PrismException::providerResponseError('PixelForge returned an invalid usage report.');
        }

        $rows = [];

        foreach ($payload['data'] as $row) {
            $rows[] = [
                'date' => $this->stringField(is_array($row) ? $row : [], 'date'),
                'generations' => $this->integerField(is_array($row) ? $row : [], 'generations'),
                'input_text_tokens' => $this->integerField(is_array($row) ? $row : [], 'input_text_tokens'),
                'input_image_tokens' => $this->integerField(is_array($row) ? $row : [], 'input_image_tokens'),
                'output_tokens' => $this->integerField(is_array($row) ? $row : [], 'output_tokens'),
                'reasoning_tokens' => $this->integerField(is_array($row) ? $row : [], 'reasoning_tokens'),
                'cost_usd' => $this->stringField(is_array($row) ? $row : [], 'cost_usd'),
            ];
        }

        $total = null;

        if (is_array($payload['total'] ?? null)) {
            $total = [
                'date' => '',
                'generations' => $this->integerField($payload['total'], 'generations'),
                'input_text_tokens' => $this->integerField($payload['total'], 'input_text_tokens'),
                'input_image_tokens' => $this->integerField($payload['total'], 'input_image_tokens'),
                'output_tokens' => $this->integerField($payload['total'], 'output_tokens'),
                'reasoning_tokens' => $this->integerField($payload['total'], 'reasoning_tokens'),
                'cost_usd' => $this->stringField($payload['total'], 'cost_usd'),
            ];
        }

        return ['data' => $rows, 'total' => $total];
    }

    /**
     * Rate limits and overloads are transient: the caller should wait and retry.
     * Request errors that will never resolve by retrying are permanent.
     */
    public function handleRequestException(string $model, RequestException $e): never
    {
        $errorType = $e->response->json('error.type');
        $errorCode = $e->response->json('error.code');
        $errorMessage = $e->response->json('error.message');

        $code = is_string($errorCode) ? $errorCode : (is_string($errorType) ? $errorType : 'request_error');
        $message = is_string($errorMessage) ? $errorMessage : 'Unknown provider error.';

        match ($e->response->getStatusCode()) {
            429 => throw new PixelforgeThrottledException(
                errorCode: $code,
                errorMessage: $message,
                retryAfter: (int) $e->response->header('Retry-After') ?: 5,
            ),
            503 => throw new PixelforgeThrottledException(
                errorCode: $code !== 'request_error' ? $code : 'overloaded',
                errorMessage: $message,
                retryAfter: max(1, (int) $e->response->header('Retry-After') ?: 2),
            ),
            400, 401, 404, 409, 422 => throw new PixelforgePermanentException($code, $message),
            default => throw PrismException::providerRequestErrorWithDetails(
                provider: 'PixelForge',
                statusCode: $e->response->getStatusCode(),
                errorType: is_string($errorType) ? $errorType : null,
                errorMessage: $errorMessage,
                previous: $e,
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $clientOptions
     * @return PixelGeneration
     */
    protected function waitForGeneration(string $id, array $clientOptions = []): array
    {
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $generation = $this->getGeneration($id, $clientOptions);

            if ($generation['status'] === 'succeeded') {
                return $generation;
            }

            if ($generation['status'] === 'failed') {
                throw new PixelforgePermanentException(
                    $generation['error']['code'] ?? 'generation_failed',
                    $generation['error']['message'] ?? 'PixelForge could not generate this image.',
                );
            }

            Sleep::sleep(max(0, $this->pollInterval));
        }

        throw PrismException::providerResponseError(
            message: 'PixelForge did not finish the generation in time. Retrieve it later using its generation ID.',
            httpStatus: 504,
        );
    }

    /**
     * @param  PixelGeneration  $generation
     */
    protected function toImageResponse(array $generation, string $model): ImagesResponse
    {
        $images = [];

        if ($generation['status'] === 'succeeded' && filled($generation['image_url'])) {
            $images[] = new GeneratedImage(
                url: $generation['image_url'],
                mimeType: 'image/png',
            );
        }

        $usage = $generation['usage'];

        return new ImagesResponse(
            images: $images,
            usage: new Usage(
                promptTokens: $usage['input_text_tokens'] ?? 0,
                completionTokens: $usage['output_tokens'] ?? 0,
                thoughtTokens: $usage['reasoning_tokens'] ?? null,
            ),
            meta: new Meta(
                id: $generation['id'],
                model: $model,
            ),
            additionalContent: [
                'generation' => $generation,
                'status' => $generation['status'],
            ],
            raw: $generation,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function client(array $options = []): PendingRequest
    {
        return Http::baseUrl(rtrim($this->url, '/'))
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->withOptions($options)
            // Every HTTP error the provider reports is mapped to a precise
            // exception here, so callers never inspect raw status codes.
            ->throw(function (Response $response, RequestException $e): never {
                $this->handleRequestException('pixelforge', $e);
            });
    }

    /**
     * @return PixelGeneration
     */
    protected function parseGeneration(mixed $data): array
    {
        if (! is_array($data)) {
            throw PrismException::providerResponseError('PixelForge returned an invalid generation.');
        }

        $status = $this->stringField($data, 'status');

        if (! in_array($status, ['queued', 'processing', 'succeeded', 'failed'], true)) {
            throw PrismException::providerResponseError('PixelForge returned an unknown generation status.');
        }

        return [
            'id' => $this->stringField($data, 'id'),
            'object' => 'generation',
            'status' => $status,
            'prompt' => $this->stringField($data, 'prompt'),
            'size' => $this->stringField($data, 'size'),
            'quality' => $this->stringField($data, 'quality'),
            'reference_images' => $this->stringList($data['reference_images'] ?? [], 'reference_images'),
            'metadata' => $this->stringMap($data['metadata'] ?? [], 'metadata'),
            'image_url' => $this->nullableStringField($data, 'image_url'),
            'image_url_expires_at' => $this->nullableStringField($data, 'image_url_expires_at'),
            'usage' => $this->parseUsage($data['usage'] ?? null),
            'rates' => $this->parseRates($data['rates'] ?? null),
            'error' => $this->parseError($data['error'] ?? null),
            'created_at' => $this->nullableStringField($data, 'created_at'),
            'completed_at' => $this->nullableStringField($data, 'completed_at'),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function stringField(array $data, string $field): string
    {
        $value = $data[$field] ?? null;

        if (! is_string($value) || $value === '') {
            throw PrismException::providerResponseError("PixelForge returned an invalid {$field} field.");
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function nullableStringField(array $data, string $field): ?string
    {
        $value = $data[$field] ?? null;

        if (! is_string($value) && $value !== null) {
            throw PrismException::providerResponseError("PixelForge returned an invalid {$field} field.");
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    protected function stringList(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw PrismException::providerResponseError("PixelForge expected {$field} to be an array.");
        }

        foreach ($value as $item) {
            if (! is_string($item)) {
                throw PrismException::providerResponseError("PixelForge expected {$field} to contain strings.");
            }
        }

        return array_values($value);
    }

    /**
     * @return array<string, string>
     */
    protected function stringMap(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw PrismException::providerResponseError("PixelForge expected {$field} to be an object.");
        }

        foreach ($value as $key => $item) {
            if (! is_string($key) || ! is_string($item)) {
                throw PrismException::providerResponseError("PixelForge expected {$field} to contain string values.");
            }
        }

        return $value;
    }

    /**
     * @return PixelGenerationRateMap|null
     */
    protected function parseRates(mixed $rates): ?array
    {
        if ($rates === null) {
            return null;
        }

        return $this->stringMap($rates, 'rates');
    }

    /**
     * @return PixelGenerationUsage|null
     */
    protected function parseUsage(mixed $usage): ?array
    {
        if ($usage === null) {
            return null;
        }

        if (! is_array($usage)) {
            throw PrismException::providerResponseError('PixelForge returned invalid token usage.');
        }

        return [
            'input_text_tokens' => $this->integerField($usage, 'input_text_tokens'),
            'input_image_tokens' => $this->integerField($usage, 'input_image_tokens'),
            'output_tokens' => $this->integerField($usage, 'output_tokens'),
            'reasoning_tokens' => $this->integerField($usage, 'reasoning_tokens'),
            'total_tokens' => $this->integerField($usage, 'total_tokens'),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function integerField(array $data, string $field): int
    {
        $value = $data[$field] ?? null;

        if (! is_int($value)) {
            throw PrismException::providerResponseError("PixelForge returned invalid {$field} usage.");
        }

        return $value;
    }

    /**
     * @return PixelGenerationError|null
     */
    protected function parseError(mixed $error): ?array
    {
        if ($error === null) {
            return null;
        }

        if (! is_array($error)) {
            throw PrismException::providerResponseError('PixelForge returned an invalid generation error.');
        }

        return [
            'code' => $this->stringField($error, 'code'),
            'message' => $this->stringField($error, 'message'),
        ];
    }
}
