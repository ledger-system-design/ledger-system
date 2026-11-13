<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Api;

class ToolController extends Controller
{
    /** Tool/pcdjson —— 省市区数据 */
    public function pcdjson()
    {
        $path = resource_path('data/pcd.json');

        if (! is_file($path)) {
            return Api::ok([]);
        }

        $data = json_decode((string) file_get_contents($path), true);

        return Api::ok(is_array($data) ? $data : []);
    }
}
