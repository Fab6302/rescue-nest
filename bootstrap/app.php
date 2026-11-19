<?php
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php')
    ->withMiddleware(function ($middleware) {
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function ($exceptions) {
        // Use Laravel's standard exception reporting and rendering.
    })
    ->create();