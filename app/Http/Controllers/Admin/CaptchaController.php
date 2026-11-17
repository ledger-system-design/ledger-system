<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * 后台登录图形验证码
 *  - 4 位字符，5 分钟有效，一次性使用
 *  - 校验不区分大小写
 */
class CaptchaController extends Controller
{
    private const SESSION_KEY = 'admin_captcha';

    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** 输出验证码图片 */
    public function image(Request $request)
    {
        $width = 120;
        $height = 40;
        $len = 4;

        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }

        $request->session()->put(self::SESSION_KEY, [
            'code' => strtoupper($code),
            'expire_at' => time() + 300,
        ]);

        if (! function_exists('imagecreatetruecolor')) {
            // 未开启 GD 时给出纯文本兜底（仍可用于校验）
            return response($code, 200)->header('Content-Type', 'text/plain');
        }

        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 245, 247, 250);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);

        // 干扰点
        for ($i = 0; $i < 160; $i++) {
            $c = imagecolorallocate($img, random_int(170, 225), random_int(170, 225), random_int(170, 225));
            imagesetpixel($img, random_int(0, $width), random_int(0, $height), $c);
        }

        // 干扰线
        for ($i = 0; $i < 5; $i++) {
            $c = imagecolorallocate($img, random_int(140, 210), random_int(140, 210), random_int(140, 210));
            imageline($img, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $c);
        }

        // 字符
        $step = (int) ($width / ($len + 1));
        for ($i = 0; $i < $len; $i++) {
            $c = imagecolorallocate($img, random_int(20, 110), random_int(20, 110), random_int(90, 180));
            $size = random_int(4, 5);
            $x = $step * ($i + 1) - 12 + random_int(-3, 3);
            $y = random_int(6, 14);
            imagestring($img, $size, $x, $y, $code[$i], $c);
        }

        ob_start();
        imagepng($img);
        $binary = ob_get_clean();
        imagedestroy($img);

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /** 校验验证码 */
    public static function verify(Request $request, ?string $input): bool
    {
        $saved = $request->session()->pull(self::SESSION_KEY);

        if (! is_array($saved) || empty($saved['code'])) {
            return false;
        }
        if (($saved['expire_at'] ?? 0) < time()) {
            return false;
        }

        return strtoupper(trim((string) $input)) === $saved['code'];
    }
}
