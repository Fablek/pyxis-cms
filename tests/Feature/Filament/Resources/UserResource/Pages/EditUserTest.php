<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Livewire\Livewire;

it('does not let an editor open their own account to change the role', function () {
    $editor = User::factory()->create();
    $this->actingAs($editor);

    Livewire::test(EditUser::class, ['record' => $editor->getRouteKey()])
        ->assertForbidden();
});
