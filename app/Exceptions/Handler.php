<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 统一把异常翻译成人话：
     *  - 会员端 /app/*  →  JSON（code=1 + 中文提示），前端直接 toast 出来
     *  - 管理后台        →  友好的错误页（含原因摘要 + 返回按钮），不再出现裸 500
     *  - 其它            →  Laravel 默认处理
     */
    public function render($request, Throwable $e)
    {
        // 表单校验失败：交给 Laravel 默认逻辑（回填 + 逐条中文提示）
        if ($e instanceof ValidationException) {
            return parent::render($request, $e);
        }

        $path = trim((string) $request->path(), '/');
        $isApi = $request->expectsJson() || $path === 'app' || str_starts_with($path, 'app/');
        $isAdmin = $path === 'admin_manage_6789' || str_starts_with($path, 'admin_manage_6789/');

        if ($isApi) {
            return response()->json([
                'code' => 1,
                'msg' => $this->friendlyMessage($e),
                'data' => '',
            ], 200);
        }

        if ($isAdmin) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 0;

            // 出错页可能发生在 AdminAuth 之前（如页面过期），这里补上侧边栏需要的变量
            $admin = \App\Models\Admin::find((int) (session('admin.id') ?? 0));
            if ($admin) {
                view()->share('currentAdmin', $admin);
            }
            view()->share('pendingRecords', 0);
            view()->share('pendingCashes', 0);

            return response()->view('admin.error', [
                'message' => $this->friendlyMessage($e),
                'hint' => $this->friendlyHint($e),
                'backUrl' => url()->previous() ?: route('admin.dashboard'),
                'reload' => in_array($status, [419], true),
            ], 200);
        }

        return parent::render($request, $e);
    }

    /** 给用户看的一句话 */
    private function friendlyMessage(Throwable $e): string
    {
        if ($e instanceof \Illuminate\Session\TokenMismatchException
            || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 419)) {
            return '页面停留太久已经过期了，请刷新页面后重新提交';
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403) {
            return '没有权限执行这个操作';
        }

        if ($e instanceof QueryException) {
            $raw = $e->getMessage();

            if (preg_match('/NOT NULL constraint failed:\s*([a-zA-Z0-9_\.]+)/i', $raw, $m)) {
                return '「' . $this->columnLabel($m[1]) . '」不能为空，请补充后再提交';
            }
            if (stripos($raw, 'UNIQUE constraint failed') !== false || stripos($raw, 'Integrity constraint violation') !== false) {
                return '数据已存在或有关联，请检查后重试';
            }
            if (stripos($raw, 'no such column') !== false || stripos($raw, 'no such table') !== false) {
                return '数据表结构需要升级，请在服务器执行 php artisan migrate';
            }

            return '数据保存失败，请检查填写内容后重试';
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return '要找的数据不存在，可能已被删除';
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return '请求方式不正确，请刷新页面后重试';
        }

        if ($e instanceof TooManyRequestsHttpException || $e instanceof ThrottleRequestsException) {
            return '操作太频繁了，请稍等一会儿再试';
        }

        if ($e instanceof AuthenticationException) {
            return '登录状态已失效，请重新登录';
        }

        $raw = $e->getMessage();

        if (stripos($raw, 'Could not parse') !== false || stripos($raw, 'Failed to parse time string') !== false) {
            return '时间格式不正确，请重新选择时间';
        }
        if (stripos($raw, 'Undefined array key') !== false) {
            return '缺少必填项，请把带 * 的项都填写后再提交';
        }
        if (stripos($raw, 'Division by zero') !== false) {
            return '计算时出现除零，请检查输入金额';
        }

        return '操作失败，请检查填写内容后重试';
    }

    /** 补充说明（后台错误页展示，方便管理员自查） */
    private function friendlyHint(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            return '如果是刚升级过程序，请先在服务器执行：php artisan migrate --force';
        }

        return '如果反复出现，请把操作步骤告知技术人员，错误信息已记录到 storage/logs/laravel.log';
    }

    /** 把 users.balance1 之类的列名翻译成中文 */
    private function columnLabel(string $column): string
    {
        $map = [
            'orders.indexpic' => '商品图片',
            'orders.payphoto' => '支付凭证',
            'orders.name' => '采购商品名称',
            'orders.amount' => '采购货款金额',
            'orders.status' => '任务状态',
            'orders.memberid' => '目标用户',
            'orders.sellerid' => '任务对象',
            'members.username' => '登录账号',
            'members.userreal' => '真实姓名',
            'members.nickname' => '昵称',
            'members.userpwd' => '登录密码',
            'members.invitecode' => '推荐码',
            'members.gradename' => '等级名称',
            'members.telephone' => '手机号',
            'members.headimgurl' => '头像',
            'certs.license' => '营业执照照片',
            'addresses.region' => '所在地区',
            'addresses.address' => '详细地址',
        ];

        if (isset($map[$column])) {
            return $map[$column];
        }

        $parts = explode('.', $column);

        return end($parts);
    }
}
