## QApi 开发框架

---

## 简述

`QApi` 是一个对 IDE 高度友好的 PHP REST API 快速开发框架。框架设计目标是：最大化 IDE 代码补全与自动提示能力，不限定开发目录结构，完全遵循 `PSR-4` 自动加载规范，支持 PHP 8.0 原生注解（Attribute），适合快速构建高性能 API 服务。

---

## 项目分析

### 技术栈

| 类别 | 技术 |
|------|------|
| 语言 | PHP >= 8.0 |
| 日志 | monolog/monolog 2.9.3 |
| 数据库抽象 | doctrine/dbal 3.8.4 |
| 缓存 | predis/predis（Redis）、多种后端适配器 |
| 事件 | psr/event-dispatcher 1.0.0 |
| 依赖注入 | psr/container（PSR-11 标准） |
| HTTP 服务 | react/http、amphp/http-server（可选 Swoole） |
| CLI | symfony/console、league/climate |
| 唯一 ID | symfony/uid（ULID / UUID） |
| MIME 检测 | symfony/mime |
| 测试 | phpunit/phpunit ^9.5 |
| 许可证 | MIT |

---

### 架构概览

QApi 采用 **分层模块化架构**，核心层次如下：

```
请求入口（HTTP / CLI）
    ↓
路由层（Router）── 中间件（Middleware）
    ↓
控制器 / 路由处理器
    ↓
模型层（Model / ORM / ODM）── 缓存层（Cache）
    ↓
数据库（MySQL / SQLite / SQL Server / MongoDB）
```

---

### 核心模块说明

#### 1. App（`src/App.php`）
应用启动入口。负责：
- 统一错误处理（将 PHP 错误转换为自定义异常）
- 初始化时区、上传目录、DI 容器、路由系统
- 捕获并记录异常堆栈，返回标准化错误响应

#### 2. Router（`src/Router.php`）
HTTP 路由系统。支持：
- 注册 GET / POST / PUT / DELETE / OPTIONS / HEAD / ALL 方法路由
- 路由级中间件与类级中间件
- 路由缓存（FileSystem、Redis 等多驱动）
- 请求日志记录（方法、域名、请求头、请求体）

#### 3. Request（`src/Request.php`）
HTTP 请求对象。封装：
- `$header`、`$get`、`$post`、`$request`、`$file`、`$cookie`、`$session`、`$auth`
- 原始输入体（`$input`）
- HTTP 方法（`$method`）

#### 4. Response（`src/Response.php`）
HTTP 响应对象。提供：
- 完整的 HTTP 状态码映射（100 ~ 511）
- 统一响应格式（`status`、`statusCode`、`data`、`msg`、`extra`）
- 自定义响应短语支持

#### 5. Model（`src/Model.php`）
ORM 查询构建器基类。支持：
- 流式链式调用：`select()`、`where()`、`orderBy()`、`limit()`、`join()`
- 数据操作：`insert()`、`update()`、`delete()`、`query()`
- 聚合函数：`count()`、`sum()`、`avg()`、`max()`、`min()`
- 分页：`paginate()`
- 事务：`beginTransaction()`、`commit()`、`rollBack()`
- 多数据库驱动：PdoMysql、PdoSqlite、PdoSqlServ、Mysqli

#### 6. Config（`src/Config.php`）
配置管理中心。特性：
- 集中加载 `config/app.php` 等配置文件
- 配置文件不存在时自动从模板生成
- 支持多应用配置、多版本管理、多数据库配置、多缓存配置
- 运行模式判断（DEV / PROD / TEST / DEBUG）

#### 7. DI Container（`src/DI/Container.php`）
PSR-11 兼容的依赖注入容器。特性：
- 支持多命名容器（`$containers` 静态注册表）
- 依赖自动解析与绑定
- 通过 `Container::G()` 快速获取默认容器

#### 8. Cache（`src/Cache/`）
多后端缓存系统，提供 **15 种适配器**：

| 适配器 | 存储后端 |
|--------|---------|
| RedisAdapter | Redis（predis） |
| RedisNativeAdapter | Redis（原生扩展） |
| FileSystemAdapter | 本地文件系统 |
| MySQLAdapter | MySQL 表 |
| SQLite3Adapter | SQLite 数据库 |
| MongoDBAdapter | MongoDB |
| ApcuAdapter | APCu 内存缓存 |
| YacAdapter | YAC 缓存 |
| SwooleTableAdapter | Swoole 共享内存表 |
| PhpArrayAdapter | PHP 内存数组 |
| ChainAdapter | 多级缓存链 |
| RemoteAdapter | 远程 HTTP 缓存 |
| CacheContainer | 缓存容器管理 |

#### 9. ORM（`src/ORM/`）
现代 ORM 层（基于 Doctrine DBAL）：
- `DB.php`：数据库门面
- `DriverManager.php`：数据库驱动抽象
- `Model.php`：ORM 模型基类
- `SchemaManager.php`：数据库 Schema 管理（建表、迁移）
- `SQLLogger.php`：SQL 查询日志
- `Connector/`：各数据库连接器

#### 10. Attribute（`src/Attribute/`）
PHP 8 原生注解，用于路由声明与 API 文档生成：
- `Route`、`Get`：路由注解
- `Parameter/`：参数注解（Path、Get、Post、Header）
- `Column/`：数据表字段注解
- `ResultDictionary*`：响应结果映射注解

#### 11. Event（`src/Event/`）
发布-订阅事件系统：
- `EventDispatcher`：事件派发器
- `EventSubscriberInterface`：事件订阅接口
- `EventDispatcherInterface`：PSR 兼容调度接口

#### 12. Template（`src/Template/`）
内置模板引擎，支持自定义标签与错误处理。

#### 13. Validator（`src/Validator/`）
基于约束（Constraint）的数据验证系统。

#### 14. Console / Command（`src/Console/`、`src/Command.php`）
CLI 命令系统（基于 symfony/console）：
- `RunCommand`：启动开发服务器
- `db/`：数据库管理命令（迁移、生成等）
- `make/`：代码生成命令

#### 15. Model Traits（`src/Model/Traits/`）
模型功能 Trait 集合：

| Trait | 功能 |
|-------|------|
| Authorize | 访问授权控制 |
| Validate | 字段数据验证 |
| SoftDelete | 软删除支持 |
| UUID | UUID/ULID 主键生成 |
| Partition | 数据分区支持 |
| Auxiliary | 辅助工具方法 |

---

### 目录结构

```
QApi/
├── composer.json          # 依赖配置与 PSR-4 自动加载
├── LICENSE                # MIT 许可证
├── README.md              # 项目文档
└── src/
    ├── App.php            # 应用启动入口
    ├── Router.php         # HTTP 路由
    ├── Request.php        # HTTP 请求对象
    ├── Response.php       # HTTP 响应对象
    ├── Model.php          # ORM 查询构建器基类
    ├── Config.php         # 配置管理
    ├── Data.php           # 扩展 ArrayObject 数据容器
    ├── Logger.php         # 日志门面
    ├── Console.php        # CLI 应用
    ├── Command.php        # CLI 命令基类
    ├── Attribute/         # PHP 8 注解（路由、参数、字段、响应映射）
    ├── Cache/             # 多后端缓存（15 种适配器）
    ├── Config/            # 配置类（App、Database、Cache、Version）
    ├── Database/          # 传统数据库驱动（PDO、MySQLi）
    ├── DI/                # PSR-11 依赖注入容器
    ├── Enumeration/       # 枚举（CLI 颜色、数据库驱动、运行模式）
    ├── Event/             # 事件系统
    ├── Exception/         # 自定义异常（PHP 各错误级别映射）
    ├── Helper/            # 全局辅助函数（helpers.php）
    ├── Http/              # HTTP 服务器、中间件接口、请求/响应方法
    ├── Logger/            # 日志级别与类型定义
    ├── Model/             # 模型 Traits（授权、验证、软删除、UUID 等）
    ├── ODM/               # MongoDB 文档映射（Mongo.php）
    ├── ORM/               # 现代 ORM（Doctrine DBAL 驱动）
    ├── Route/             # 路由实现与 HTTP 方法定义
    ├── Template/          # 内置模板引擎
    ├── Validator/         # 数据验证约束
    ├── bin/               # 可执行入口（QApi、QConsole）
    └── serializeClosure/  # Closure 序列化工具
```

---

### 主要特性总结

1. **PHP 8 原生注解**：通过 `#[Route]`、`#[Get]`、`#[Post]` 等注解声明路由与参数，IDE 友好，无魔法字符串
2. **多数据库支持**：MySQL、SQLite、SQL Server（PDO 与 MySQLi 双驱动），通过 Doctrine DBAL 统一抽象
3. **多级缓存体系**：15 种缓存适配器，支持链式缓存降级策略
4. **多运行模式**：DEV / PROD / TEST / DEBUG，支持 PHP 内置服务器、Swoole 高性能服务器、Socket 服务器
5. **中间件支持**：路由级与类级中间件，支持请求/响应过滤链
6. **事件系统**：发布-订阅模式，PSR 兼容事件调度接口
7. **软删除**：模型 Trait 内置软删除功能
8. **数据验证**：基于约束的验证器，支持自定义规则
9. **MongoDB 支持**：ODM 文档映射
10. **CLI 工具**：内置代码生成命令与数据库管理命令
11. **PSR 规范**：PSR-4 自动加载、PSR-11 依赖注入容器、PSR-14 事件调度

---

## 安装 Composer

框架使用 Composer 来管理其依赖性。所以，在你使用之前，你必须确认在你电脑上是否安装了 Composer。 如果未安装请前往 [https://getcomposer.org/](https://getcomposer.org/)
下载安装。

## 快速创建项目

您可以通过 composer 快速创建一个模板项目

```
composer create-project qapi/project project_dir
cd project_dir
composer serve
```
---
## PHPServer 模式运行
````
composer serve
````
## Swoole 模式运行

````
composer swoole
````

## 环境需求

PHP 版本 >= 8.0

### 建议启用的 PHP 扩展

| 扩展 | 用途 |
|------|------|
| ext-json | JSON 数据处理 |
| ext-redis | Redis 原生缓存 |
| ext-mbstring | 多字节字符串处理 |
| ext-pdo / ext-pdo_mysql / ext-pdo_sqlite | PDO 数据库驱动 |
| ext-mysqli | MySQLi 驱动 |
| ext-sqlite3 | SQLite 支持 |
| ext-swoole (>=4) | 高性能异步服务器 |
| ext-bcmath | 精确数学计算 |
| ext-sqlsrv | SQL Server 支持 |
| ext-mongodb | MongoDB 支持 |
| ext-apcu | APCu 内存缓存 |
| ext-openssl | 加密支持 |
