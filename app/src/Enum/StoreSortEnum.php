<?php

namespace App\Enum;

enum StoreSortEnum: string
{
    case NEWEST = 'newest';
    case OLDEST = 'oldest';
    case PRICE_ASC = 'price_asc';
    case PRICE_DESC = 'price_desc';
    case TITLE_ASC = 'title_asc';
    case TITLE_DESC = 'title_desc';

    public static function toArray(): array
    {
        return [
            StoreSortEnum::NEWEST,
            StoreSortEnum::OLDEST,
            StoreSortEnum::PRICE_ASC,
            StoreSortEnum::PRICE_DESC,
            StoreSortEnum::TITLE_ASC,
            StoreSortEnum::TITLE_DESC,
        ];
    }

    public function toLabel(?StoreSortEnum $sortEnum = null): string
    {
        return match ($sortEnum ?: $this) {
            StoreSortEnum::NEWEST => 'Newest',
            StoreSortEnum::OLDEST => 'Oldest',
            StoreSortEnum::PRICE_ASC => 'Price: Low to High',
            StoreSortEnum::PRICE_DESC => 'Price: High to Low',
            StoreSortEnum::TITLE_ASC => 'Title: A to Z',
            StoreSortEnum::TITLE_DESC => 'Title: Z to A',
        };
    }

    public static function getByValue(string $value): ?StoreSortEnum
    {
        foreach (self::cases() as $enum) {
            if ($enum->value === $value) {
                return $enum;
            }
        }

        return null;
    }
}
