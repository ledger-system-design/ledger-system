<?php

namespace App\Support;

/**
 * 轻量 JWT（HS256）实现，与参考站的 access_token / refresh_token 结构保持一致。
 */
class Jwt
{
    public static function encode(array $payload, ?int $ttl = null): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => 'liushui.local',
            'aud' => 'liushui.local',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($ttl ?? 720000),
        ], $payload);

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $segments = [
            self::b64(json_encode($header, JSON_UNESCAPED_UNICODE)),
            self::b64(json_encode($payload, JSON_UNESCAPED_UNICODE)),
        ];

        $signing = implode('.', $segments);
        $segments[] = self::b64(self::sign($signing));

        return implode('.', $segments);
    }

    /**
     * @return array|null 校验失败返回 null
     */
    public static function decode(?string $token): ?array
    {
        if (! $token) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$h, $p, $s] = $parts;

        if (! hash_equals(self::b64(self::sign($h . '.' . $p)), $s)) {
            return null;
        }

        $payload = json_decode(self::unb64($p), true);
        if (! is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function sign(string $data): string
    {
        return hash_hmac('sha256', $data, (string) config('app.key') . env('JWT_SECRET', ''), true);
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function unb64(string $raw): string
    {
        return base64_decode(strtr($raw, '-_', '+/')) ?: '';
    }
}
