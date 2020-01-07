queue example
============================

队列系统演示（模拟处理抢购的高压力场景）| queue system example (mimic flash sale online)

预期数据流 | prefered data flow:

![data flow](./flow.jpg)

为了简化部署，移除了mysql的部分 | removed mysql for simplicity

## 部署方法 | deploy

### redis安装 | install redis
https://redis.io/topics/quickstart


### php依赖安装（通过composer） | install php composer 

```
# 可选步骤-更换composer源 https://pkg.phpcomposer.com/ | optional, change composer source
php composer.phar config -g repo.packagist composer https://packagist.phpcomposer.com 

# 安装依赖 | install packages
php composer.phar install --prefer-dist
```

### 配置redis、mysql及队列 | config redis, mysql and queue

refer

- [config/web.php](./config/web.php)
- [config/console.php](./config/console.php)

## 压测方法 | stress test

### 启动web服务 | start web server

```
./yii serve
```

默认服务地址：http://localhost:8080/index.php | default serve path

如果配置到其他地址，需要更改 `./vegeta.urls.txt` 中的内容 | if changed, you need to change `./vegeta.urls.txt` accordingly

打开此地址初始化redis，注意安web配置更改相应地址 | visit this url to init redis (change url accordingly)

http://localhost:8080/index.php?r=site/store&add=100


### 启动 worker | start workers

打开两个cmd窗口，分别执行

```
yii queue/listen
```

```
yii queue-chained/listen
```


### vegeta安装 | install vegeta

vegeta下载：https://github.com/tsenart/vegeta/releases
将vegeta.exe放到项目目录即可

### 启动压测命令 | start stress test

```
stress.bat
```