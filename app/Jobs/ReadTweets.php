<?php

namespace App\Jobs;

use App\Helpers\SecurityHelpers;
use App\Models\User;
use Atymic\Twitter\Facade\Twitter;
use Atymic\Twitter\Contract\Twitter as TwitterContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReadTweets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $paginationToken;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $paginationToken = null)
    {
        $this->data = $data;
        $this->paginationToken = $paginationToken;
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

        $twitter = Twitter::forApiV2()->usingCredentials($credentials->token, $credentials->secret);

        $params = [
            TwitterContract::KEY_RESPONSE_FORMAT => TwitterContract::RESPONSE_FORMAT_OBJECT,
            "pagination_token" => $this->paginationToken,
            "max_results" => "100",
        ];

        $tweets = $twitter->userTweets($credentials->twitter_user_id, $params);

        if(isset($tweets->data)) {
            foreach ($tweets->data as $tweet) {
                DestroyTweets::dispatch($this->data, SecurityHelpers::idToHashId($tweet->id));
            }
        }

        if(isset($tweets->meta->next_token)) {
            ReadTweets::dispatch(
                $this->data, $tweets->meta->next_token
            )->delay(
                now()->addMinutes(15) // because of twitter rate limit you can only remove 50 tweets per 15 minutes
            );
        }
        else {
            $user->delete(); // remove user from db when operation is done
        }
    }
}
