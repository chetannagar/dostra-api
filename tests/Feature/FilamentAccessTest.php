<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest is redirected to the filament login page', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

test('a super user can access the filament admin panel', function () {
    $user = User::factory()->create([
        'role' => 'super_user',
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('an admin cannot access the filament admin panel', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('a client user cannot access the filament admin panel', function () {
    $user = User::factory()->create([
        'role' => 'client',
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
