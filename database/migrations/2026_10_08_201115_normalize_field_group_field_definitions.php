<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the keys of existing field definitions to the field type registry format:
     * "is_required" becomes "required" and the "media" type becomes "image".
     */
    public function up(): void
    {
        $this->convertFields(fn (array $field): array => [
            ...collect($field)->except('is_required')->all(),
            'type' => ($field['type'] ?? 'text') === 'media' ? 'image' : ($field['type'] ?? 'text'),
            'required' => (bool) ($field['required'] ?? $field['is_required'] ?? false),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->convertFields(fn (array $field): array => [
            ...collect($field)->except('required')->all(),
            'type' => ($field['type'] ?? 'text') === 'image' ? 'media' : ($field['type'] ?? 'text'),
            'is_required' => (bool) ($field['required'] ?? false),
        ]);
    }

    /**
     * @param  Closure(array<string, mixed>): array<string, mixed>  $convert
     */
    protected function convertFields(Closure $convert): void
    {
        DB::table('field_groups')->orderBy('id')->each(function (object $group) use ($convert): void {
            $fields = json_decode($group->fields ?? '[]', true) ?: [];

            DB::table('field_groups')
                ->where('id', $group->id)
                ->update(['fields' => json_encode(array_values(array_map($convert, $fields)))]);
        });
    }
};
