yii2 queue 任务系统演示（模拟处理抢购的高压力场景）
============================

官方github https://github.com/zhuravljov/yii2-queue

## 部署方法

### redis安装
https://redis.io/topics/quickstart


### php依赖安装（通过composer）

```
# 可选步骤-更换composer源 https://pkg.phpcomposer.com/ 
php composer.phar config -g repo.packagist composer https://packagist.phpcomposer.com

# 安装依赖步骤
php composer.phar require --prefer-dist zhuravljov/yii2-queue
```

### 配置web服务
代码中已配置

### php控制台命令
```
cd \项目目录\

# 启动一个worker，做完所有的事情自己退出，比如每天晚上处理一天积累的错误
yii queue/run

# 实时监听，有任务就启动worker来做事，比如抢购
yii queue/listen

# 查看队列情况
yii queue/info


```

## 压测方法

### 启动web服务
默认服务地址：http://localhost/yii2-queue-example/web/index.php
如果配置到其他地址，需要更改\项目目录\vegeta.urls.txt中的内容

初始化redis，注意安web配置更改相应地址
http://localhost/yii2-queue-example/web/index.php?r=site/store&add=100

### 启动worker
打开两个cmd窗口，分别执行
```
cd \项目目录\
yii queue/listen
```

```
cd \项目目录\
yii queue-chained/listen
```


### vegeta安装
vegeta下载：https://github.com/tsenart/vegeta/releases
将vegeta.exe放到项目目录即可

### 启动压测命令
```
cd \项目目录\
stress
```

## 多通道（queue-chained）

业务中有部分逻辑需要跑在win平台，或者docker内部，就需要将这一部分任务推到单独的队列中
需要注意的是redis默认只能被本机访问，需要放开局域网访问权限，如果觉得不安全就加个密码验证吧


