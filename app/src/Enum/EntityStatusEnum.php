<?php

namespace App\Enum;

enum EntityStatusEnum: string
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
            EntityStatusEnum::DRAFT => 'Draft',
            EntityStatusEnum::PUBLISHED => 'Published',
        };
    }
}
