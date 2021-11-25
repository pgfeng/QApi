## QApi 开发框架

---

## 简述

`QApi` 是一个对IDE高度友好的框架，使用IDE能为您提升更多的开发效率，不限定开发目录的结构，完全遵循`psr-4`规范。

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
