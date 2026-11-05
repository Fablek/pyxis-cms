<?php

use App\Models\Setting;

it('returns the site language', function () {
    Setting::set('site_language', 'en');

    $this->getJson('/api/site')
        ->assertOk()
        ->assertExactJson(['data' => ['language' => 'en']]);
});

it('returns polish when the site language is not set', function () {
    $this->getJson('/api/site')
        ->assertOk()
        ->assertExactJson(['data' => ['language' => 'pl']]);
});
