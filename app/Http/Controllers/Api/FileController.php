<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /** File/upload —— 图片上传 */
    public function upload(Request $request)
    {
        $file = $request->file('data') ?: $request->file('files') ?: $request->file('file');

        if (! $file) {
            return Api::fail('请选择要上传的文件');
        }

        if (! $file->isValid()) {
            return Api::fail('文件上传失败');
        }

        if ($file->getSize() > 8 * 1024 * 1024) {
            return Api::fail('图片不能超过 8MB');
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            return Api::fail('只支持 png/jpg/gif/webp 图片');
        }

        $dir = public_path('uploads/' . date('Ym'));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = date('YmdHis') . '_' . Str::random(8) . '.' . $ext;
        $file->move($dir, $name);

        $url = '/uploads/' . date('Ym') . '/' . $name;

        return Api::ok([
            'url' => $url,
            'filename' => $url,
            'name' => $name,
        ], '上传成功');
    }
}
