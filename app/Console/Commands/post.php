<?php

namespace App\Console\Commands;

use App\Helpers\AmeshImages;
use Carbon\Carbon;
use Illuminate\Console\Command;
use potibm\Bluesky;

class post extends Command {
    const OUTPUT_LATEST_IMAGE_PATH = "/tmp/combined.png";

    const TEXT_LATEST_IMAGE     = "%04d年 %02d月 %02d日 %02d時 %02d分 時点での東京都を中心とした雨模様です。";
    const ALT_TEXT_LATEST_IMAGE = "%04d年 %02d月 %02d日 %02d時 %02d分 時点での東京都を中心とした雨模様の画像";

    protected $signature = 'run:post';

    protected $description = 'Post current amesh image.';

    protected $api;

    public function __construct() {
        parent::__construct();

        $this->api = new Bluesky\BlueskyApi(
            identifier: env('BLUESKY_IDENTIFIER'),
            password  : env('BLUESKY_PASSWORD')
        );
    }

    public function handle() {
        $carbon_now = Carbon::now();
        $amesh_images = AmeshImages::new([
            AmeshImages::generateMeshFilename($carbon_now),
        ]);
        $amesh_images->downloadAll();
        $amesh_images->plotMeshToMap();

        // ポスト処理
        $post_service = new Bluesky\BlueskyPostService($this->api);
        $post = $post_service->addImage(
            post     : Bluesky\Feed\Post::create(
                sprintf(
                    self::TEXT_LATEST_IMAGE,
                    $carbon_now->year,
                    $carbon_now->month,
                    $carbon_now->day,
                    $carbon_now->hour,
                    $carbon_now->minute,
                )
            ),
            imageFile: collect($amesh_images->getOutputFilepathes())->first(),
            altText  : sprintf(
                self::ALT_TEXT_LATEST_IMAGE,
                $carbon_now->year,
                $carbon_now->month,
                $carbon_now->day,
                $carbon_now->hour,
                $carbon_now->minute,
            )
        );
        $post->setLangs(["ja-JP"]);
        $this->api->createRecord($post);

        $amesh_images->cleanup();
    }
}
