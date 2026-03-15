<div class="flex items-center gap-3">
    <div class="flex shrink-0 items-center justify-center">
        <img 
            src="{{ asset('logo.svg') }}" 
            alt="Logo" 
            class="h-8 w-8 block rounded-md overflow-hidden object-cover"
        >
    </div>

    <div 
        x-show="$store.sidebar.isOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-2"
        x-transition:enter-end="opacity-100 translate-x-0"
        class="flex flex-col overflow-hidden whitespace-nowrap"
    >
        <span class="text-sm font-bold leading-none text-gray-950 dark:text-white tracking-tight">
            Pyxis CMS
        </span>
        
        <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium leading-none mt-1">
            v1.0.0
        </span>
    </div>
</div>