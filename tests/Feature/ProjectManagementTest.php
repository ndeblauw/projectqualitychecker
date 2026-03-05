<?php

use App\Models\Project;
use App\Models\User;

test('authenticated users can add projects to their account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'title' => 'Project Quality Checker',
        'github_url' => 'github.com/laravel/framework',
    ]);

    $response->assertRedirect(route('dashboard'));

    $project = $user->projects()->first();

    expect($project)->not->toBeNull();
    expect($project?->title)->toBe('Project Quality Checker');
    expect($project?->github_url)->toBe('https://github.com/laravel/framework');
});

test('guests cannot add projects', function () {
    $response = $this->post(route('projects.store'), [
        'title' => 'Project Quality Checker',
        'github_url' => 'https://github.com/laravel/framework',
    ]);

    $response->assertRedirect(route('login'));
});

test('project link must include owner and repository', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'title' => 'Invalid Project Link',
        'github_url' => 'github.com/laravel',
    ]);

    $response->assertSessionHasErrors(['github_url']);
    expect($user->projects()->count())->toBe(0);
});

test('sidebar only shows projects for the logged-in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $userProject = Project::factory()->for($user)->create([
        'title' => 'My Project',
        'github_url' => 'https://github.com/acme/my-project',
    ]);

    Project::factory()->for($otherUser)->create([
        'title' => 'Other Project',
        'github_url' => 'https://github.com/acme/other-project',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSeeText($userProject->title);
    $response->assertSee($userProject->github_url);
    $response->assertDontSeeText('Other Project');
});
