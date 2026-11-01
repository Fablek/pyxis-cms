<?php

namespace App\View\Components;

use Closure;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Services\AiSettings;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Livewire\Livewire;

class FilamentCustomHeader extends Component
{
    public string $heading = 'Dashboard';
    public array $headerActions = [];
    public bool $aiAssistant = false;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        // Get current component Livewire
        $page = Livewire::current();

        if ($page) {
            // Get heading
            if (method_exists($page, 'getHeading'))  {
                $this->heading = $page->getHeading();
            }

            // Logic for breadcrumbs
            if (($this->heading === 'Dashboard' || empty($this->heading)) && method_exists($page, 'getBreadcrumbs')) {
                $breadcrumbs = $page->getBreadcrumbs();
                if (!empty($breadcrumbs)) {
                    $this->heading = is_array(end($breadcrumbs)) ? (string)array_key_last($breadcrumbs) : (string)end($breadcrumbs);
                }
            }

            // Get actions
            if (method_exists($page, 'getCachedHeaderActions')) {
                $this->headerActions = $page->getCachedHeaderActions();
            }

            // Only the page form has content the assistant can work with
            $this->aiAssistant = ($page instanceof EditPage || $page instanceof CreatePage)
                && app(AiSettings::class)->isEnabled();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.filament-custom-header');
    }
}
