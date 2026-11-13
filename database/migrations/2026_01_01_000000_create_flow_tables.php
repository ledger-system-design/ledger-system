<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 账本管理系统 · 数据表结构
 *
 * 业务说明（依据需求文档）：
 *  - 参与者缴纳 998 元服务费，参与每日"采购流水"任务，产生 4% 的溢价利润；
 *  - 4% 溢价分配：店长 1.5% + 参与者 1.5% + 直推人 0.5% + 平台 0.5%；
 *  - 直推（第二代）给第一代带来收益，间推（第三代）只给第二代带来收益。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 会员（参与者 / 店长）
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pid')->default(0)->comment('直推人ID（第一代）');
            $table->unsignedBigInteger('fid')->default(0)->comment('所属店铺/店长ID');
            $table->string('unionid', 64)->default('');
            $table->string('username', 32)->unique()->comment('登录账号（手机号）');
            $table->string('userpwd', 64)->default('');
            $table->string('userreal', 32)->default('')->comment('真实姓名');
            $table->string('nickname', 64)->default('');
            $table->string('telephone', 32)->default('');
            $table->string('headimgurl', 255)->default('');
            $table->tinyInteger('sex')->default(1);
            $table->string('invitecode', 16)->default('')->comment('推荐码');
            $table->string('gradename', 32)->default('VIP')->comment('等级名称');
            $table->decimal('balance1', 14, 2)->default(0)->comment('余额（可提现）');
            $table->decimal('balance2', 14, 2)->default(0)->comment('积分');
            $table->decimal('bonus1', 14, 2)->default(0)->comment('累计订单收益');
            $table->decimal('bonus2', 14, 2)->default(0)->comment('累计直推收益');
            $table->decimal('withdrawed', 14, 2)->default(0)->comment('已提现收益');
            $table->string('cert1', 64)->default('');
            $table->string('cert2', 64)->default('');
            $table->tinyInteger('iscert')->default(0)->comment('店铺认证：0未认证 1已认证');
            $table->tinyInteger('is_shop')->default(0)->comment('是否店长');
            $table->tinyInteger('status')->default(1)->comment('1正常 0禁用');
            $table->unsignedInteger('logintimes')->default(0);
            $table->timestamp('addtime')->nullable();
            $table->timestamps();
            $table->index('pid');
            $table->index('fid');
        });

        // 店铺认证 / 收款信息
        Schema::create('certs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('memberid')->unique();
            $table->string('license', 255)->default('')->comment('营业执照照片');
            $table->json('shopinfo')->nullable()->comment('店铺信息');
            $table->json('payinfo')->nullable()->comment('收款方式（5项）');
            $table->tinyInteger('iscert')->default(0);
            $table->timestamp('addtime')->nullable();
            $table->timestamps();
        });

        // 采购订单（流水任务）
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('orderno', 32)->unique()->comment('订单编号');
            $table->unsignedBigInteger('memberid')->comment('买家（付款人）');
            $table->unsignedBigInteger('sellerid')->comment('卖家（收款人）');
            $table->string('name', 128)->default('采购货款')->comment('采购商品名称');
            $table->string('indexpic', 255)->default('')->comment('商品图片');
            $table->decimal('amount', 14, 2)->default(0)->comment('采购货款金额');
            $table->tinyInteger('status')->default(0)->comment('0待付款 1待确认 2已确认 3已完成');
            $table->string('payphoto', 255)->default('')->comment('支付凭证');
            $table->string('paytype', 32)->default('');
            $table->string('payname', 64)->default('');
            $table->unsignedInteger('calcdate')->default(0)->comment('结算日期 yyyymmdd');
            $table->timestamp('paytime')->nullable();
            $table->timestamp('confirmtime')->nullable();
            $table->timestamp('addtime')->nullable();
            $table->timestamps();
            $table->index('memberid');
            $table->index('sellerid');
            $table->index('status');
        });

        // 资金明细
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('memberid')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->tinyInteger('type')->default(1)->comment('1收入 0支出');
            $table->unsignedInteger('typeid')->default(0)->comment('7订单收益 8直推收益 11店长收益 21提现 31服务费');
            $table->string('account', 16)->default('account1')->comment('account1余额 account2积分');
            $table->decimal('prebalance', 14, 2)->default(0)->comment('变动前余额');
            $table->decimal('balance', 14, 2)->default(0)->comment('变动后余额');
            $table->string('remark', 64)->default('')->comment('关联单号');
            $table->string('remark1', 160)->default('')->comment('描述');
            $table->unsignedInteger('calcmonth')->default(0)->comment('结算月份 yyyymm');
            $table->unsignedInteger('calcdate')->default(0)->comment('结算日期 yyyymmdd');
            $table->timestamp('addtime')->nullable();
            $table->timestamps();
        });

        // 提现申请
        Schema::create('cashes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('memberid')->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('account', 16)->default('account1');
            $table->tinyInteger('type')->default(0)->comment('0银行卡 1微信 2支付宝');
            $table->tinyInteger('status')->default(0)->comment('0待审核 1已打款 2已驳回');
            $table->string('remark', 160)->default('');
            $table->timestamp('addtime')->nullable();
            $table->timestamps();
        });

        // 收货地址
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('memberid')->index();
            $table->string('userreal', 32)->default('');
            $table->string('telephone', 32)->default('');
            $table->string('region', 128)->default('')->comment('省 市 区');
            $table->string('address', 255)->default('');
            $table->tinyInteger('isdefault')->default(0);
            $table->timestamp('addtime')->nullable();
            $table->timestamps();
        });

        // 系统配置
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('cashes');
        Schema::dropIfExists('balances');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('certs');
        Schema::dropIfExists('members');
    }
};
