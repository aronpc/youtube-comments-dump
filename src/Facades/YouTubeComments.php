<?php

namespace AronPC\YouTubeComments\Facades;

use Illuminate\Support\Facades\Facade;
use AronPC\YouTubeComments\Service\YouTubeClient;

/**
 * @method static array fetchCommentsAndLiveChat(string $videoId)
 * @method static string fetchLiveChat(string $videoId)
 * @method static string fetchComments(string $videoId)
 * @method static string downloadComments(string $videoId)
 * @method static string parseComments(string $videoId, string $jsonFile)
 * @method static void formatAndSaveComments(array $comments, string $outputFile)
 * @method static string downloadLiveChat(string $videoId)
 * @method static string parseLiveChat(string $videoId, string $jsonFile)
 * @method static void formatAndSaveLiveChat(array $chatMessages, string $outputFile)
 *
 * @see \AronPC\YouTubeComments\Service\YouTubeClient
 */
class YouTubeComments extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return YouTubeClient::class;
    }
}
