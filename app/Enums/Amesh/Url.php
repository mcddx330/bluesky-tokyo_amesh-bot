<?php

namespace App\Enums\Amesh;

enum Url: string {

    case URL_BASE = "http://tokyo-ame.jwa.or.jp/";
    case URL_MAP = "map/";

    // "/mesh/000/{YYYYMMDDHHMM}.gif"
    case URL_HISTORICAL_MESH = "mesh/000/";

    case FILENAME_MAP = "map000.jpg";
    case FILENAME_MAP_LANDMARK = "msk000.png";
    case FILENAME_MESH = "%04d%02d%02d%02d%02d.gif";
}
