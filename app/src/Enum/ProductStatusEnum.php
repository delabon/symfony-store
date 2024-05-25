<?php

namespace App\Enum;

enum ProductStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public static function toArray(): array
    {
        return [
            self::DRAFT,
            self::PUBLISHED,
        ];
    }

    public function toLabel(): string
    {
        return match ($this) {
            ProductStatusEnum::DRAFT => 'Draft',
            ProductStatusEnum::PUBLISHED => 'Published',
        };
    }
}
