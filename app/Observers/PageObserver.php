<?php

namespace App\Observers;

use App\Models\Page;
use App\Models\Setting;
use App\Plugins\Hook;

class PageObserver
{
    /**
     * Handle the Page "saving" event.
     */
    public function saving(Page $page): void
    {
        $homepageId = Setting::get('homepage_id');

        // If this page has just become/is the home page
        if ($homepageId && (string)$page->id === (string)$homepageId) {
            $page->slug = null;
            $page->parent_id = null;
        }
    }

    /**
     * Lets plugins react to every saved page (created, updated or published).
     */
    public function saved(Page $page): void
    {
        Hook::doAction('page.saved', $page);
    }
}
