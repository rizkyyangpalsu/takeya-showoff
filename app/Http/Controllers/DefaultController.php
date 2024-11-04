<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException as SymfonyFileNotFoundException;

class DefaultController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard');
    }

    public function profile(): Response
    {
        return Inertia::render('Profile/Show');
    }

    public function attachment(Factory $filesystem, Attachment $attachment)
    {
        try {
            return \response()->make($filesystem->disk('local')->get($attachment->path), 200)
                ->header('Content-Type', $attachment->getAttribute('mime'));
//            return Storage::download($attachment->path);
        } catch (FileNotFoundException | SymfonyFileNotFoundException $e) {
            abort(404);
        }
    }
}
