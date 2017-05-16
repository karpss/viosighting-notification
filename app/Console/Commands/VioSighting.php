<?php

namespace App\Console\Commands;
use Mail;
use Illuminate\Console\Command;

use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class VioSighting extends Command
{
    private $api_endpoint = "https://stream.twitter.com/1.1/";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vio:sight';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Twitter Hashtag Notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $search_keyword = "#viosighting";
        $this->info("Twitter messages with the hashtag {$search_keyword}");

        $message = $this->SearchTwitter($search_keyword);

        while (!$message->eof()) {
            $vio_tweet = json_decode($this->readLine($message), true);
           $data = array('name'=>  $vio_tweet['text'] . PHP_EOL);

            Mail::send('emails.myview', $data, function ($message) {

                $message->from('', 'Twitter Updates');

                $message->to('')->subject('VIO Sighting');

            });
            return "Your email has been sent successfully";
        }



    }

    public function SearchTwitter($locate)
    {
        $my_defaults = [];

        $my_stack = HandlerStack::create();
        $oauth = new Oauth1([
            'consumer_key'    => '',
            'consumer_secret' => '',
            'token'           => '',
            'token_secret'    => '',
        ]);
        $my_stack->push($oauth);
        $my_defaults = array_merge($my_defaults, [
            'base_uri' => $this->api_endpoint,
            'handler'  => $my_stack,
            'auth'     => 'oauth',
            'stream'   => true,
        ]);
        $this->client = new Client($my_defaults);

        $result = $this->client->post('statuses/filter.json', [
            'form_params' => [
                'track' => $locate,
            ],
        ]);

        return $result->getBody();
    }

    public function readLine($stream, $maxLength = null, $eol = PHP_EOL)
    {
        $buffer    = '';
        $size      = 0;
        $negEolLen = -strlen($eol);
        while (!$stream->eof()) {
            if (false === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;

            if (++$size == $maxLength || substr($buffer, $negEolLen) === $eol) {
                break;
            }
        }
        return $buffer;
    }
}