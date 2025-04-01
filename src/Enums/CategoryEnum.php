<?php

namespace Ultra\UltraConfigManager\Enums;

/**
 * CategoryEnum
 *
 * Represents the configuration categories supported by UCM.
 * Includes optional translation support for UI rendering and form options.
 */
enum CategoryEnum: string
{
    case System = 'system';
    case Application = 'application';
    case Security = 'security';
    case Performance = 'performance';
    case None = ''; // Represents absence of a category

    /**
     * Get the translation key for the current category.
     *
     * @return string Translation key string (e.g., 'uconfig::uconfig.categories.system')
     */
    public function translationKey(): string
    {
        return 'uconfig::uconfig.categories.' . $this->value;
    }

    /**
     * Get the translated display name for the current category.
     *
     * @return string Translated label
     */
    public function translatedName(): string
    {
        if ($this === self::None) {
            return __('uconfig::uconfig.categories.none');
        }

        return __($this->translationKey());
    }

    /**
     * Get all enum cases (excluding 'None') as an associative array
     * of value => translated name.
     *
     * Typically used in form dropdowns.
     *
     * @return array<string, string> Translated category options
     */
    public static function translatedOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            if ($case !== self::None) {
                $options[$case->value] = $case->translatedName();
            }
        }

        return $options;
    }
}
