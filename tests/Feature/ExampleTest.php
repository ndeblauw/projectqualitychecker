<?php

test('home route redirects to dashboard', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('welcome page can be rendered from its new path', function () {
    $response = $this->get(route('welcome'));

    $response->assertOk();
});
