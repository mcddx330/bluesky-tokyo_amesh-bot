<?php

namespace App\Helpers;

use App\Enums\Amesh\Count;
use App\Enums\Amesh\Url;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation;

class AmeshImages {
    protected bool $success = false;
    protected string $message = '';
    protected int $status_code = -1;

    /** @var array<string> */
    protected array $mesh_filenames = [];

    protected string $working_directory = '/tmp/';

    protected string $filepath_map = '';
    protected string $filepath_landmark = '';

    /** @var array<string> */
    protected array $filepath_meshes = [];

    /** @var array<string> */
    protected array $output_filenames = [];

    public function __construct(array $mesh_filenames) {
        $this->mesh_filenames = $mesh_filenames;
    }

    public static function new(array $mesh_filenames) {
        return new static(
            mesh_filenames: $mesh_filenames
        );
    }

    public static function generateMeshFilename(Carbon $carbon): string {
        $carbon->floorMinute(Count::AMESH_FLOOR_MINUTES->value);

        return sprintf(
            Url::FILENAME_MESH->value,
            $carbon->year,
            $carbon->month,
            $carbon->day,
            $carbon->hour,
            $carbon->minute,
        );
    }

    public function downloadAll() {
        $this->_download_map();
        $this->_download_mlandmark();
        $this->_download_meshes();
    }

    protected function _download_map() {
        $this->filepath_map = $this->working_directory . Url::FILENAME_MAP->value;
        $this->_download(
            url: Url::URL_BASE->value . Url::URL_MAP->value . Url::FILENAME_MAP->value,
            to : $this->filepath_map
        );

        return $this->success;
    }

    protected function _download_mlandmark() {
        $this->filepath_landmark = $this->working_directory . Url::FILENAME_MAP_LANDMARK->value;
        $this->_download(
            url: Url::URL_BASE->value . Url::URL_MAP->value . Url::FILENAME_MAP_LANDMARK->value,
            to : $this->filepath_landmark
        );

        return $this->success;
    }

    protected function _download_meshes() {
        foreach ($this->mesh_filenames as $filename) {
            $this->_download(
                url: Url::URL_BASE->value . Url::URL_HISTORICAL_MESH->value . $filename,
                to : $this->working_directory . $filename
            );
            if (!$this->success) {
                return $this->success;
            }
        }

        return $this->success;
    }

    protected function _download(string $url, string $to) {
        $client = new Client();
        try {
            $response = $client->get($url, ['sink' => $to]);
            $this->status_code = $response->getStatusCode();
            if ($this->status_code !== HttpFoundation\Response::HTTP_OK) {
                throw new \RuntimeException(
                    sprintf(
                        'Status code is not %s. Get %s. URL: %s',
                        HttpFoundation\Response::HTTP_OK,
                        $this->status_code,
                        $url
                    )
                );
            }
            $this->success = true;
        } catch (\RuntimeException $e) {
            $this->success = false;
            $this->message = $e->getMessage();
        } catch (\Exception $e) {
            $this->message = "Exception: " . $e->getMessage();
            $this->success = false;
        }

        return $this->success;
    }

    public function plotMeshToMap() {
        $this->success = false;

        foreach ($this->mesh_filenames as $mesh_filename) {
            $map = imagecreatefromjpeg($this->filepath_map);
            $landmark = imagecreatefrompng($this->filepath_landmark);
            imagecopy($map, $landmark, 0, 0, 0, 0, imagesx($landmark), imagesy($landmark));
            $mesh = imagecreatefromgif($this->working_directory . $mesh_filename);
            imagecopy($map, $mesh, 0, 0, 0, 0, imagesx($mesh), imagesy($mesh));
            $output_filename = str_replace('.gif', '.png', $mesh_filename);
            imagepng($map, $this->working_directory . $output_filename);
            $this->output_filenames[] = $output_filename;
        }
        // メモリの開放
        imagedestroy($map);
        imagedestroy($landmark);
        imagedestroy($mesh);

        if (!empty($this->output_filenames)) {
            $this->success = true;
        }

        return $this->success;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getOutputFilepathes(): array {
        $arr = [];
        foreach ($this->output_filenames as $filename) {
            $arr[] = $this->working_directory . $filename;
        }

        return $arr;
    }

    public function cleanup(): bool {
        unlink($this->filepath_map);
        unlink($this->filepath_landmark);
        foreach ($this->mesh_filenames as $filename) {
            unlink($this->working_directory . $filename);
        }

        return true;
    }
}
