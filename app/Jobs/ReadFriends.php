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

class ReadFriends implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $cursor;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $cursor = null)
    {
        $this->data = $data;
        $this->cursor = $cursor;
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
            "cursor" => $this->cursor,
            "count" => 1000,
        ];

        $friends = $twitter->getFriendsIds($params);

        if(isset($friends->ids)) {
            foreach ($friends->ids as $id) {
                DestroyFriendships::dispatch($this->data, SecurityHelpers::idToHashId($id));
            }
        }

        if(isset($users->meta->next_cursor)) {
            ReadFriends::dispatch(
                $this->data, $users->next_cursor
            )->delay(
                now()->addMinutes(15) // because of twitter rate limit you can only remove 50 tweets per 15 minutes
            );
        }
    }
}
