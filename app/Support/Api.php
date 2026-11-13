<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 统一 API 响应格式，与参考站保持一致：{ code, msg, data }
 */
class Api
{
    public const OK = 0;
    public const FAIL = 1;
    public const NOT_FOUND = 1002;   // 方法不存在
    public const PARAM_ERROR = 2001; // 参数错误
    public const NO_ACTION = 2013;   // 未指定操作
    public const NOT_LOGIN = 4000;   // 未登录

    public static function ok($data = '', string $msg = '成功')
    {
        return response()->json(['code' => self::OK, 'msg' => $msg, 'data' => $data]);
    }

    public static function fail(string $msg = '操作失败', int $code = self::FAIL)
    {
        return response()->json(['code' => $code, 'msg' => $msg, 'data' => '']);
    }

    public static function notLogin()
    {
        return response()->json(['code' => self::NOT_LOGIN, 'msg' => '您还未登录，请登陆后再操作', 'data' => '']);
    }

    public static function noAction()
    {
        return response()->json(['code' => self::NO_ACTION, 'msg' => '未指定操作', 'data' => '']);
    }

    public static function noMethod()
    {
        return response()->json(['code' => self::NOT_FOUND, 'msg' => '方法不存在', 'data' => '']);
    }

    /**
     * 把 Laravel 分页器转成前端期望的扁平结构
     */
    public static function paginate(LengthAwarePaginator $p, ?callable $map = null): array
    {
        $items = $p->getCollection();
        if ($map) {
            $items = $items->map($map);
        }

        return [
            'total' => $p->total(),
            'per_page' => $p->perPage(),
            'current_page' => $p->currentPage(),
            'last_page' => $p->lastPage(),
            'data' => array_values($items->all()),
        ];
    }
}
