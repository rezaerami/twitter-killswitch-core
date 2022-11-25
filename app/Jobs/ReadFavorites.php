<?php

namespace App\Jobs;

use App\Helpers\SecurityHelpers;
use App\Models\User;
use Atymic\Twitter\Facade\Twitter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReadFavorites implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $maxId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $maxId = null)
    {
        $this->data = $data;
        $this->maxId = $maxId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = json_decode(base64_decode($this->data));

        $userId = SecurityHelpers::hashIdToId($data->userCode);
        $user = User::where("id", $userId)->first();

        $encryptedCredentials = SecurityHelpers::decrypt(base64_decode($data->credentials), base64_decode($user->private_key));
        $credentials = json_decode(base64_decode($encryptedCredentials));

        $twitter = Twitter::usingCredentials($credentials->token, $credentials->secret);

        $params = [
            "max_id" => $this->maxId,
            "count" => 200,
        ];

        $favorites = $twitter->getFavorites($params);

        if(count($favorites)) {
            foreach ($favorites as $favorite) {
                DestroyFavorite::dispatch($this->data, SecurityHelpers::idToHashId($favorite->id));
            }
        }

        if(count($favorites)) {
            ReadFavorites::dispatch(
                $this->data, $favorites[count($favorites) - 1]->id
            )->delay(
                now()->addMinutes(15) // because of twitter rate limit you can only remove 50 tweets per 15 minutes
            );
        }
    }
}
