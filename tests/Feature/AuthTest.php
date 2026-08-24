<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('allows a user to register', function () {
    $response = $this->postJson('/register', [
        'name' => 'John Doe',
        'username' => 'johndoe',
        'password' => 'password123',
        'role' => 'user',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'message',
                 'user' => [
                     'id',
                     'name',
                     'username',
                     'role',
                     'created_at',
                     'updated_at',
                 ],
             ]);

    $this->assertDatabaseHas('users', [
        'username' => 'johndoe',
    ]);
});

it('allows a user to login', function () {
    $user = User::factory()->create([
        'username' => 'johndoe',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/login', [
        'username' => 'johndoe',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'message',
                 'token',
             ]);
});

it('prevents login with invalid credentials', function () {
    $user = User::factory()->create([
        'username' => 'johndoe',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/login', [
        'username' => 'johndoe',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
             ->assertJson([
                 'message' => 'Invalid credentials.',
             ]);
});

