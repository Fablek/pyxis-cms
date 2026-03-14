<header class="flex items-center gap-4 py-4 mb-6">
    <button 
        x-data="{}" 
        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        data-slot="sidebar-trigger" 
        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-[color,box-shadow] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] hover:bg-gray-100 dark:hover:bg-white/5 size-9 h-7 w-7 -ml-1 text-gray-500"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panel-left">
            <rect width="18" height="18" x="3" y="3" rx="2"></rect>
            <path d="M9 3v18"></path>
        </svg>
        <span class="sr-only">Toggle Sidebar</span>
    </button>

    <h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-2xl">
        {{ $title ?? 'Dashboard' }}
    </h1>
</header>