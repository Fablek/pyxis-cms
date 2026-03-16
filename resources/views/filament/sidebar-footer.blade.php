<div 
    class="fi-sidebar-user border-t border-gray-200 dark:border-white/10 transition-all duration-300 ease-in-out"
    :class="$store.sidebar.isOpen ? 'p-4' : 'p-2 flex justify-center'"
>
    <x-filament-panels::user-menu />
</div>