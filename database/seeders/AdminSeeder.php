<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 默认管理员账号：username / admin（登录后台后请在「账号设置」中修改密码）
        Admin::updateOrCreate(
            ['username' => 'username'],
            [
                'password' => password_hash('admin', PASSWORD_DEFAULT),
                'name' => '管理员',
                'is_super' => 1,
                'status' => 1,
            ]
        );
    }
}
