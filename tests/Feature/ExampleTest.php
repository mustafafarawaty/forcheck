<?php

test('the application redirects guests to the student login page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/student/login');
});
