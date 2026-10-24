<?php

namespace App\Filament\CustomFields;

use App\Filament\CustomFields\FieldTypes\FieldType;
use Filament\Schemas\Components\Component;
use InvalidArgumentException;

/**
 * Central list of available custom field types.
 *
 * Plugins can add their own types with register().
 */
class FieldTypeRegistry
{
    /**
     * How deep repeaters and groups can be nested (0 = top level).
     */
    public const int MAX_DEPTH = 2;

    /**
     * @var array<string, FieldType>
     */
    protected array $types = [];

    /**
     * @param  array<FieldType>  $types
     */
    public function __construct(array $types = [])
    {
        foreach ($types as $type) {
            $this->register($type);
        }
    }

    public function register(FieldType $type): void
    {
        $this->types[$type->key()] = $type;
    }

    public function get(string $key): FieldType
    {
        return $this->types[$key] ?? throw new InvalidArgumentException("Unknown field type [{$key}].");
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /**
     * @return array<string, FieldType>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Field types available on the given nesting level, as select options.
     *
     * @return array<string, string>
     */
    public function options(int $depth = 0): array
    {
        return collect($this->types)
            ->reject(fn (FieldType $type): bool => $type->hasSubFields() && $depth >= self::MAX_DEPTH)
            ->map(fn (FieldType $type): string => $type->label())
            ->all();
    }

    /**
     * Turn saved field definitions into form components.
     *
     * @param  array<array<string, mixed>>  $definitions
     * @return array<Component>
     */
    public function formComponents(array $definitions): array
    {
        return collect($definitions)
            ->filter(fn (mixed $definition): bool => is_array($definition)
                && $this->has($definition['type'] ?? '')
                && filled($definition['name'] ?? null))
            ->map(fn (array $definition): Component => $this->get($definition['type'])->toFormComponent($definition))
            ->values()
            ->all();
    }

    /**
     * Transform saved values into their API shape, following the field definitions.
     *
     * Only defined fields are returned: values of removed fields are dropped,
     * and defined fields without a saved value are returned as null.
     *
     * @param  array<array<string, mixed>>  $definitions
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>
     */
    public function toApiValues(array $definitions, ?array $values): array
    {
        return collect($definitions)
            ->filter(fn (mixed $definition): bool => is_array($definition)
                && $this->has($definition['type'] ?? '')
                && filled($definition['name'] ?? null))
            ->mapWithKeys(fn (array $definition): array => [
                $definition['name'] => $this->get($definition['type'])->toApi($values[$definition['name']] ?? null, $definition),
            ])
            ->all();
    }
}
