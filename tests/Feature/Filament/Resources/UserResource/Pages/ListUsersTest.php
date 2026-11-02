<?php

use App\Filament\Resources\UserResource;
use App\Models\User;

it('is available only to admins', function () {
    $this->actingAs(User::factory()->create())
        ->get(UserResource::getUrl())
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(UserResource::getUrl())
        ->assertSuccessful();
});
