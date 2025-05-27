<?php

namespace AronPC\YouTubeComments;

use Illuminate\Support\ServiceProvider;
use AronPC\YouTubeComments\Service\YouTubeClient;

class YouTubeCommentsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register the YouTubeClient service
        $this->app->singleton(YouTubeClient::class, function ($app) {
            return new YouTubeClient();
        });

        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/youtube-comments.php', 'youtube-comments'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/youtube-comments.php' => config_path('youtube-comments.php'),
        ], 'config');

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \AronPC\YouTubeComments\Command\FetchCommentsCommand::class,
                \AronPC\YouTubeComments\Command\FetchLiveChatCommand::class,
                \AronPC\YouTubeComments\Command\DownloadCommentsCommand::class,
                \AronPC\YouTubeComments\Command\ParseCommentsCommand::class,
                \AronPC\YouTubeComments\Command\FetchAllCommand::class,
            ]);
        }
    }
}
