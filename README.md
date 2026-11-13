# 账本管理系统

一套面向「会员任务 + 采购流水 + 多级分佣」场景的管理系统。

**会员端**是移动端 H5（Vue 2 + vue-router，hash 路由，按手机屏 750rpx 适配），**管理后台**是 Laravel Blade 服务端渲染，两者共用同一套 Laravel 应用与数据库。默认使用 SQLite，克隆下来跑两条命令就能用。

---

## 功能一览

### 会员端（H5）

| 模块 | 说明 |
| --- | --- |
| 登录 | 账号密码登录，注册入口默认关闭（由管理员在后台代建账号） |
| 首页 | 店铺信息、轮播图（可手动滑动）、导航宫格、公告栏、今日任务 |
| 采购清单 | 按状态分页（待付款 / 待确认 / 已确认 / 已完成），只显示分配给自己的任务 |
| 订单详情 | 查看买卖双方、任务金额、按序号陈列的收款码（序号 1 为默认优选），上传支付凭证 |
| 团队采购 | 直推 / 间推下级的下单与收益统计 |
| 我的 | 订单收益、直推收益、可提现余额；头像昵称修改、收货地址、修改密码、退出登录 |
| 明细 | 收益明细、余额明细、提现记录，支持分页加载 |

### 管理后台

| 模块 | 说明 |
| --- | --- |
| 数据概览 | 会员数、任务数、资金流向、待处理事项 |
| 用户数据 | 全字段可查可改；新增用户、启用禁用、删除、余额调整、批量清空 |
| 采购清单 | 发布任务：先选卖方（含智能推荐）→ 填任务额度 → 选买方 |
| 采购记录 | 查看用户上传的支付凭证，手动确认到账 / 取消到账（自动发放各层级奖励） |
| 资金明细 | 全部流水，可按会员、类型、日期筛选 |
| 提现审核 | 通过 / 驳回提现申请 |
| 系统设置 | 店铺名称、轮播图、导航宫格、公告、任务收益率、自助注册开关 |
| 操作日志 | 所有后台操作的审计记录 |
| 管理员账号 | 最多 5 个；分为超级管理员与普通管理员 |

**权限分级**：普通管理员登录后只能给用户安排任务（采购清单 / 采购记录）；用户数据、资金、提现、设置、日志等仅超级管理员可见。

---

## 核心业务规则

任务回款 = 本金 + 本人收益，本人收益 = 本金 × 收益率。

- **收益率可配**：全局默认 1.5%（后台可改 1%～100%），也可给单个会员设置专属收益率；
- **直推奖励**：下级参与任务时，其直推人获得 0.5%（计入余额）；
- **店长收益**：所属店长获得 1.5%；
- **平台留存**：0.5%。

> 收益口径：会员「订单收益」只统计本人参与任务产生的收益累计（不含本金）；「余额」只统计直推下级参与带来的奖励，可用于提现。

---

## 技术栈

| 层 | 选型 |
| --- | --- |
| 后端 | PHP 8.1+ / Laravel 10 |
| 数据库 | SQLite（默认，开箱即用）/ 可切 MySQL |
| 会员端 | Vue 2.7 + vue-router 3（Hash 路由）+ 自研 uView 风格组件，无构建步骤，直接引 CDN 本地文件 |
| 管理后台 | Laravel Blade + 原生 CSS/JS，无前端构建 |
| 鉴权 | 会员端 JWT（HS256）；管理后台 Session + 图形验证码 |

---

## 快速开始

```bash
# 1. 安装依赖
composer install

# 2. 准备环境变量
cp .env.example .env
php artisan key:generate

# 3. 初始化数据库（建表 + 写入管理员与站点配置，不含任何业务数据）
touch database/database.sqlite
php artisan migrate --seed

# 4. 启动
php artisan serve --port=9193
```

打开 <http://localhost:9193/> 即为会员端。

需要造一批演示数据（会员 / 任务 / 收益）时：

```bash
php artisan db:seed --class=DemoSeeder
```

> 上线前请务必在「账号设置」里修改密码，并在 `.env` 中更换 `APP_KEY` 与 `JWT_SECRET`。

### Nginx 部署要点

站点根目录要指向 `public/`，并配置伪静态（否则无扩展名的路由会 404）：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

`.env` 里的 `APP_URL` 必须改成实际访问地址（应用会用它生成后台表单地址、验证码与上传接口链接）。`storage`、`bootstrap/cache`、`database`、`public/uploads` 需要 Web 用户可写。

---

## 目录结构

```
app/
  Http/Controllers/Admin/   后台控制器（用户、任务、记录、资金、提现、设置…）
  Http/Controllers/Api/     会员端接口（登录、首页、会员、文件上传）
  Http/Middleware/          AdminAuth / SuperAdmin / ApiAuth
  Models/                   Member、Order、Balance、Cash、Cert、Address、Setting、Admin
  Services/FlowService.php  任务结算与分佣核心逻辑
config/flow.php             业务参数（服务费、各层级分成比例、明细类型）
database/migrations/        表结构
database/seeders/           初始化数据与演示数据
public/static/              会员端前端资源（JS / CSS / 图片 / 字体）
resources/views/admin/      后台页面模板
routes/admin.php            后台路由
routes/api.php              会员端接口路由
```

---

## 说明

- 项目初始状态**不含任何业务数据**（无会员、无任务、无资金流水），只有 1 个管理员账号和站点基础配置；
- 会员端前端不依赖构建工具，改完 `public/static/js/*.js` 刷新即可生效；
- 后台所有增删改都会写入 `admin_logs`，可在「操作日志」中追溯；
- 图片上传走 `/app/File/upload`，文件保存在 `public/uploads/年月/`，限制 8MB，支持 png / jpg / gif / webp。

## 许可

本项目仅供学习与二次开发参考，请勿用于违法违规用途。
