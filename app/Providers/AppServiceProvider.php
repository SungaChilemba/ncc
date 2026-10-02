<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->syncPublicDiskUrl();
    }

    /**
     * Make the `public` disk serve URLs from the host the app is actually being
     * requested on.
     *
     * Storage::url() reads `filesystems.disks.public.url` from the config file, which
     * is baked from APP_URL. Any CMS upload (image_path / media asset) is rendered
     * through that accessor, so a stale or default APP_URL (e.g. http://localhost)
     * makes every uploaded image point at the wrong origin and silently 404.
     */
    private function syncPublicDiskUrl(): void
    {
        $root = $this->app->runningInConsole()
            ? (string) config('app.url')
            : $this->app['request']->getSchemeAndHttpHost();

        $root = rtrim($root, '/');

        if ($root === '') {
            return;
        }

        config(['filesystems.disks.public.url' => $root.'/storage']);
    }
}
