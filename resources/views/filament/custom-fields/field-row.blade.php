<span class="pyxis-field-row">
    <span class="pyxis-field-number" aria-hidden="true"></span>

    <span class="truncate">
        {{ filled($label) ? $label : 'Nowe pole' }}
        @if ($isRequired)
            <span class="text-danger-600 dark:text-danger-400">*</span>
        @endif
    </span>

    <span class="pyxis-field-col truncate font-mono text-xs font-normal text-gray-500 dark:text-gray-400">
        {{ $name ?: '—' }}
    </span>

    <span class="pyxis-field-col flex items-center gap-1.5 text-xs font-normal text-gray-500 dark:text-gray-400">
        @if ($type)
            <x-filament::icon :icon="$type->icon()" class="size-4 shrink-0" />
            <span class="truncate">{{ $type->label() }}</span>
        @endif
    </span>
</span>
