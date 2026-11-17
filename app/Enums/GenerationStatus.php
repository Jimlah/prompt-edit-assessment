<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * The generation's lifecycle, mirroring Pixelforge's own statuses.
 * Succeeded and failed are final: a generation never moves out of them.
 */
enum GenerationStatus: string
{
    case Queued = 'queued';

    case Processing = 'processing';

    case Succeeded = 'succeeded';

    case Failed = 'failed';

    /**
     * @return array<int, string>
     */
    public static function values(GenerationStatus ...$statuses): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            $statuses === [] ? self::cases() : $statuses,
        );
    }

    /**
     * @return array<int, self>
     */
    public static function terminal(): array
    {
        return [self::Succeeded, self::Failed];
    }

    public function isTerminal(): bool
    {
        return in_array($this, self::terminal(), true);
    }

    public function label(): string
    {
        return Str::ucfirst($this->value);
    }
}
