<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 客户二次需求：
 *  1) 任务回款收益率可由管理员统一设置，也可对单个用户单独设置
 *     —— members.rate 存"专属收益率"（NULL / 0 表示跟随全局 settings.order_rate）
 *     —— orders.rate / orders.profit 在下单时冻结收益率与应得收益，便于事后对账
 *  2) 收款码顺序（序号 1 = 默认优选平台）直接使用 certs.payinfo 数组顺序，无需新增字段
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('members', 'rate')) {
            Schema::table('members', function (Blueprint $table) {
                $table->decimal('rate', 6, 4)->nullable()->after('gradename')
                    ->comment('专属任务收益率，如 0.0150；为空则跟随全局设置');
            });
        }

        if (! Schema::hasColumn('orders', 'rate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('rate', 6, 4)->default(0)->after('amount')
                    ->comment('下单时冻结的任务收益率');
            });
        }

        if (! Schema::hasColumn('orders', 'profit')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('profit', 14, 2)->default(0)->after('rate')
                    ->comment('参与者应得收益 = amount × rate');
            });
        }

        // 全局收益率默认 1.5%
        if (DB::table('settings')->where('key', 'order_rate')->doesntExist()) {
            DB::table('settings')->insert([
                'key' => 'order_rate',
                'value' => '0.015',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 注册开关：客户要求新用户由管理员在后台代注册
        if (DB::table('settings')->where('key', 'register_open')->doesntExist()) {
            DB::table('settings')->insert([
                'key' => 'register_open',
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('members', 'rate')) {
            Schema::table('members', fn (Blueprint $t) => $t->dropColumn('rate'));
        }
        if (Schema::hasColumn('orders', 'rate')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['rate', 'profit']));
        }
    }
};
