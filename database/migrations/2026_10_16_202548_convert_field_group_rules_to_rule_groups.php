<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert flat location rules ([{param: "page_id", operator, value}], all joined with "and")
     * into ACF-like rule groups ([{conditions: [{param: "page", operator, value}]}]).
     */
    public function up(): void
    {
        $this->convertRules(function (array $rules): array {
            if ($rules === [] || isset($rules[0]['conditions'])) {
                return $rules;
            }

            return [[
                'conditions' => array_map(fn (array $rule): array => [
                    ...$rule,
                    'param' => ($rule['param'] ?? null) === 'page_id' ? 'page' : ($rule['param'] ?? 'page'),
                ], $rules),
            ]];
        });
    }

    /**
     * Reverse the migrations. Rule groups are flattened, so "or" groups become "and" conditions.
     */
    public function down(): void
    {
        $this->convertRules(fn (array $rules): array => collect($rules)
            ->flatMap(fn (array $ruleGroup): array => $ruleGroup['conditions'] ?? [])
            ->map(fn (array $condition): array => [
                ...$condition,
                'param' => ($condition['param'] ?? null) === 'page' ? 'page_id' : $condition['param'],
            ])
            ->values()
            ->all());
    }

    /**
     * @param  Closure(array<int, array<string, mixed>>): array<int, array<string, mixed>>  $convert
     */
    protected function convertRules(Closure $convert): void
    {
        DB::table('field_groups')->orderBy('id')->each(function (object $group) use ($convert): void {
            $rules = json_decode($group->rules ?? '[]', true) ?: [];

            DB::table('field_groups')
                ->where('id', $group->id)
                ->update(['rules' => json_encode($convert($rules))]);
        });
    }
};
