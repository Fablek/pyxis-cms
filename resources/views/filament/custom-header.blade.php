<header class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-gray-200/50 dark:border-white/10 px-4 transition-all">
    <div class="flex items-center gap-2">
        <button 
            x-data="{}" 
            x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-gray-100 dark:hover:bg-white/5 size-8 text-gray-500 dark:text-gray-400"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panel-left">
                <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                <path d="M9 3v18"></path>
            </svg>
            <span class="sr-only">Toggle Sidebar</span>
        </button>

        <div class="h-4 w-px bg-gray-200 dark:bg-white/10 mx-1"></div>

        <nav aria-label="breadcrumb">
            <ol class="flex items-center gap-1.5 text-sm font-medium">
                <li class="flex items-center gap-1.5">
                    <span class="text-gray-400 dark:text-gray-500">Pyxis</span>
                </li>
                <li class="text-gray-300 dark:text-gray-600">/</li>
                <li class="flex items-center gap-1.5">
                    <span class="text-gray-900 dark:text-white font-semibold tracking-tight">
                        {{ $title ?? 'Dashboard' }}
                    </span>
                </li>
            </ol>
        </nav>
    </div>

    <div class="flex items-center gap-2">
        <button class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/5 transition-colors" title="Open AI Assistant">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot-message-square">
                <path d="M12 6V2H8"></path>
                <path d="m8 18-4 4V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2Z"></path>
                <path d="M2 12h2"></path>
                <path d="M9 11v2"></path>
                <path d="M15 11v2"></path>
                <path d="M20 12h2"></path>
            </svg>
        </button>
    </div>
</header>