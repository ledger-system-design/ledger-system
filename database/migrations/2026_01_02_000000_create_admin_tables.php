<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 管理后台数据表
 *   - admins     ：后台管理员账号（默认 zhengjun）
 *   - admin_logs ：后台所有数据修改的操作日志
 *   - orders     ：补充「发布人 / 备注 / 到账时间」等后台字段
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64)->unique()->comment('登录用户名');
            $table->string('password', 255)->comment('密码（Hash）');
            $table->string('name', 64)->default('')->comment('显示名称');
            $table->tinyInteger('is_super')->default(0)->comment('1 超级管理员');
            $table->tinyInteger('status')->default(1)->comment('1 正常 0 禁用');
            $table->unsignedInteger('logintimes')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 64)->default('');
            $table->timestamps();
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->default(0);
            $table->string('admin_name', 64)->default('');
            $table->string('action', 64)->default('')->comment('动作标识');
            $table->string('description', 255)->default('')->comment('操作说明');
            $table->string('target_type', 64)->default('');
            $table->string('target_id', 64)->default('');
            $table->text('detail')->nullable()->comment('变更内容 JSON');
            $table->string('ip', 64)->default('');
            $table->timestamp('created_at')->nullable();
            $table->index('admin_id');
            $table->index('action');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->default(0)->comment('发布任务的管理员ID')->after('payname');
            $table->string('admin_name', 64)->default('')->comment('发布人名称')->after('created_by');
            $table->string('remark', 255)->default('')->comment('后台备注')->after('admin_name');
            $table->timestamp('arrived_at')->nullable()->comment('确认到账时间')->after('confirmtime');
            $table->unsignedBigInteger('settled_by')->default(0)->comment('确认到账的管理员ID')->after('arrived_at');
            $table->decimal('settle_amount', 14, 2)->default(0)->comment('已发放奖励合计')->after('settled_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'admin_name', 'remark', 'arrived_at', 'settled_by', 'settle_amount']);
        });
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('admins');
    }
};
