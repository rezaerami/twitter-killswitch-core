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

class DestroyFriendships implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $user)
    {
        $this->data = $data;
        $this->user = $user;
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
        $userId = (string) SecurityHelpers::hashIdToId($this->user);

        $twitter->postUnfollow(["user_id" => $userId]);
    }
}
