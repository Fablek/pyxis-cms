<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

/**
 * A custom field type (ACF-like) that can be defined in a field group.
 *
 * Every type describes its own settings (split into the General, Validation
 * and Presentation tabs of the field group editor) and knows how to turn a
 * saved definition into a Filament form component.
 */
abstract class FieldType
{
    /**
     * Width options mapped to the number of columns in a 12-column grid.
     *
     * @var array<string, int>
     */
    public const array WIDTHS = [
        'full' => 12,
        'three_quarters' => 9,
        'two_thirds' => 8,
        'half' => 6,
        'third' => 4,
        'quarter' => 3,
    ];

    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function icon(): Heroicon;

    /**
     * Build the bare form component for a field definition. Common options
     * (label, required, instructions, width) are applied by toFormComponent().
     *
     * @param  array<string, mixed>  $config
     */
    abstract protected function makeFormComponent(array $config): Component;

    /**
     * Type-specific settings in the "General" tab.
     *
     * @return array<Component>
     */
    public function generalSettings(int $depth): array
    {
        return [];
    }

    /**
     * Type-specific settings in the "Validation" tab.
     *
     * @return array<Component>
     */
    public function validationSettings(): array
    {
        return [];
    }

    /**
     * Type-specific settings in the "Presentation" tab.
     *
     * @return array<Component>
     */
    public function presentationSettings(): array
    {
        return [];
    }

    public function supportsRequired(): bool
    {
        return true;
    }

    /**
     * Whether this type contains nested fields (repeater, group).
     */
    public function hasSubFields(): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function toFormComponent(array $config): Component
    {
        $component = $this->makeFormComponent($config);

        if ($component instanceof Field) {
            $component
                ->label($config['label'] ?? $config['name'])
                ->helperText($config['instructions'] ?? null)
                ->required($this->supportsRequired() && ($config['required'] ?? false));
        }

        return $component->columnSpan([
            'default' => 'full',
            'lg' => self::WIDTHS[$config['width'] ?? 'full'] ?? 12,
        ]);
    }
}
