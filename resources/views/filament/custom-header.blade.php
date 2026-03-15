{{-- resources/views/filament/custom-header.blade.php --}}
@php
    $heading = 'Dashboard';
    
    // Używamy $this, skoro u Ciebie nie wywala błędu 500 
    if (isset($this)) {
        if (method_exists($this, 'getHeading')) {
            $heading = $this->getHeading();
        } 
        
        if (($heading === 'Dashboard' || empty($heading)) && method_exists($this, 'getBreadcrumbs')) {
            $breadcrumbs = $this->getBreadcrumbs();
            if (!empty($breadcrumbs)) {
                $heading = is_array(end($breadcrumbs)) ? array_key_last($breadcrumbs) : end($breadcrumbs);
            }
        }

        $headerActions = method_exists($this, 'getCachedHeaderActions') 
            ? $this->getCachedHeaderActions() 
            : [];
    }
@endphp

<header class="flex h-16 w-full shrink-0 items-center justify-between gap-4 border-b border-gray-200/50 dark:border-white/10 px-6 transition-all backdrop-blur-md rounded-t-xl">
    <div class="flex items-center gap-2 overflow-hidden">
        <button x-data="{}" x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()" class="inline-flex items-center justify-center rounded-md size-8 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M9 3v18"></path></svg>
        </button>

        <div class="h-4 w-px bg-gray-200 dark:bg-white/10 mx-1"></div>

        <nav class="text-sm font-medium">
            <ol class="flex items-center gap-1.5">
                <li class="text-gray-400">Pyxis</li>
                <li class="text-gray-300 dark:text-gray-600">/</li>
                <li class="text-gray-900 dark:text-white font-semibold">
                    {{ $heading }}
                </li>
            </ol>
        </nav>
    </div>

    <div class="flex items-center gap-3">
        @if (isset($headerActions) && count($headerActions))
            <div class="flex items-center gap-2">
                <x-filament-actions::actions :actions="$headerActions" />
            </div>
            <div class="h-4 w-px bg-gray-200 dark:bg-white/10 mx-1"></div>
        @endif

        <button class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6V2H8"></path><path d="m8 18-4 4V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2Z"></path><path d="M2 12h2"></path><path d="M9 11v2"></path><path d="M15 11v2"></path><path d="M20 12h2"></path></svg>
        </button>
    </div>
</header>