# LazyBot

这个项目主要放置一些基于 robo.li 的日常自动化/半自动化脚本。


## robo.li 

robo.li 相当于一个 PHP 版的 Gulp。

安装：

在 Mac 或者 Linux 的命令行下：

wget http://robo.li/robo.phar
sudo chmod +x robo.phar && mv robo.phar /usr/bin/robo

然后就可以直接使用 robo 命令了。

## 可用命令列表

运行 `robo list` 可以查看可用的命令。

在 RoboFile.php 中，添加一个 Public 的方法，就可以添加一个命令。 

在方法头注释中写的文字会变成 list 时的命令说明。

## 使用实例

安装一个 SS 服务器：

robo build_shadowsocks_server