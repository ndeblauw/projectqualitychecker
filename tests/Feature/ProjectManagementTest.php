<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function fakeGitHubStatisticsRequest(): void
{
    $github = \Mockery::mock();
    $repoApi = \Mockery::mock();
    $commitsApi = \Mockery::mock();

    $github->shouldReceive('repo')->andReturn($repoApi);
    $repoApi->shouldReceive('commits')->andReturn($commitsApi);
    $commitsApi->shouldReceive('all')->andReturn([]);

    app()->instance('github', $github);
}

test('authenticated users can add projects to their account', function () {
    $user = User::factory()->create();

    Http::fake([
        'https://api.github.com/repos/laravel/framework' => Http::response([
            'private' => false,
        ], 200),
    ]);

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'title' => 'Project Quality Checker',
        'github_url' => 'github.com/laravel/framework',
    ]);

    $response->assertRedirect(route('dashboard'));

    $project = $user->projects()->first();

    expect($project)->not->toBeNull();
    expect($project?->title)->toBe('Project Quality Checker');
    expect($project?->github_url)->toBe('https://github.com/laravel/framework');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/laravel/framework');
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

    Http::fake();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'title' => 'Invalid Project Link',
        'github_url' => 'github.com/laravel',
    ]);

    $response->assertSessionHasErrors(['github_url']);
    expect($user->projects()->count())->toBe(0);
    Http::assertNothingSent();
});

test('project link must point to an existing public repository', function () {
    $user = User::factory()->create();

    Http::fake([
        'https://api.github.com/repos/acme/missing-repository' => Http::response([], 404),
    ]);

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'title' => 'Missing Repository',
        'github_url' => 'https://github.com/acme/missing-repository',
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
    $response->assertSee(route('projects.show', $userProject));
    $response->assertDontSeeText('Other Project');
});

test('user can view their own project page', function () {
    $user = User::factory()->create();
    fakeGitHubStatisticsRequest();

    $project = Project::factory()->for($user)->create([
        'title' => 'Quality Checker',
        'github_url' => 'https://github.com/laravel/framework',
    ]);

    $response = $this->actingAs($user)->get(route('projects.show', $project));

    $response->assertOk();
    $response->assertSeeText('Quality Checker');
    $response->assertSee($project->github_url);
    $response->assertSeeText('Opens in a new tab');
    $response->assertSeeText('Refresh statistics');
});

test('user cannot view another users project page', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $project = Project::factory()->for($owner)->create();

    $this->actingAs($viewer)
        ->get(route('projects.show', $project))
        ->assertNotFound();
});

test('user can update their own project', function () {
    $user = User::factory()->create();

    $project = Project::factory()->for($user)->create([
        'title' => 'Old Title',
        'github_url' => 'https://github.com/laravel/framework',
    ]);

    Http::fake([
        'https://api.github.com/repos/laravel/pint' => Http::response([
            'private' => false,
        ], 200),
    ]);

    $response = $this->actingAs($user)->patch(route('projects.update', $project), [
        'title' => 'Updated Title',
        'github_url' => 'github.com/laravel/pint',
    ]);

    $response->assertRedirect(route('projects.show', $project));

    expect($project->fresh()?->title)->toBe('Updated Title');
    expect($project->fresh()?->github_url)->toBe('https://github.com/laravel/pint');
});

test('user cannot update another users project', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $project = Project::factory()->for($owner)->create();

    Http::fake();

    $this->actingAs($viewer)
        ->patch(route('projects.update', $project), [
            'title' => 'Updated Title',
            'github_url' => 'https://github.com/laravel/framework',
        ])
        ->assertNotFound();
});

test('edit project modal reopens when update validation fails', function () {
    $user = User::factory()->create();
    fakeGitHubStatisticsRequest();

    $project = Project::factory()->for($user)->create([
        'title' => 'Quality Checker',
        'github_url' => 'https://github.com/laravel/framework',
    ]);

    Http::fake([
        'https://api.github.com/repos/acme/missing-repository' => Http::response([], 404),
    ]);

    $response = $this->actingAs($user)
        ->from(route('projects.show', $project))
        ->followingRedirects()
        ->patch(route('projects.update', $project), [
            'title' => 'Quality Checker Updated',
            'github_url' => 'https://github.com/acme/missing-repository',
        ]);

    $response->assertOk();
    $response->assertSee('data-test="open-edit-project-modal"', false);
    $response->assertSeeText('The GitHub repository does not exist or is not publicly accessible.');
});

test('user can delete their own project', function () {
    $user = User::factory()->create();

    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('projects.destroy', $project));

    $response->assertRedirect(route('dashboard'));
    expect(Project::query()->find($project->id))->toBeNull();
});

test('user cannot delete another users project', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $project = Project::factory()->for($owner)->create();

    $this->actingAs($viewer)
        ->delete(route('projects.destroy', $project))
        ->assertNotFound();

    expect(Project::query()->find($project->id))->not->toBeNull();
});
