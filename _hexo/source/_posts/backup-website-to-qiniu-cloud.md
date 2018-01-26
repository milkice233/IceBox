---
title: 充分运用七牛云自动备份网站/vps
date: 2017-04-23 20:12:12
tags:
  - Backup
  - Qiniu
  - Website
  - Crontab
categoris:
  - Tech
  - Web
---

几段代码轻松搞定自动备份

### 废话几句

还是在免费部落看到的一篇
[免费资源部落：七牛云存储变身备份利器-自动备份网站文件和数据库到七牛云存储空间](https://www.freehao123.com/qiniu-beifen/)

后来又到投稿者本人的博客上看了下 大致了解了下流程

[张戈博客：Linux/vps本地七天循环备份和七牛远程备份脚本](https://zhangge.net/4336.html)

但是毕竟是2014年的文章了 原文中提及的qrsync在七牛云官网已经找不到了

遂自己按照七牛云的文档折腾了下 贴在这里以备忘

### 备份准备

七牛云说到底只提供了一个存储功能 不能像多备份那样把网站文件和数据库自动收集&备份

于是我们需要一个工具将要备份的文件打包好以供上传

这里用到了张戈博客中的脚本 奶冰将其稍加修改使得能够更好用于更广泛的需求
```shell
#!/bin/bash
#Author:ZhangGe Enhanced by Milkice
#Des:Backup database and webfile.
#Date:2014-8-28
TODAY=$(date +%F)
PPPPASS="12345678" //Change me Plz!
if [ -z "$1" ];then
        echo Needed Usage arguments. Please Use --help to get more infomation.
        exit 1
fi
 
test -f /etc/profile &amp;&amp; ./etc/profile &gt;/dev/null 2&gt;&amp;1
zip --version &gt;/dev/null || yum install -y zip || apt-get install zip -y
ZIP=$(which zip)
MYSQLDUMP=$(which mysqldump)
 
if [ "$1" == "db" ];then
        domain=$2
        dbname=$3
        mysqluser=$4
        mysqlpd=$5
        back_path=$6
        test -d "$back_path" || (mkdir -p "$back_path" || echo "$back_path not found! Please CheckOut Or feedback to zhangge.net..." &amp;&amp; exit 2)
        cd "$back_path" || exit
        $MYSQLDUMP -u"$mysqluser" -p"$mysqlpd" "$dbname" --skip-lock-tables&gt;"$back_path"/"$domain"\_db_"$TODAY"\.sql
        test -f "$back_path"/"$domain"\_db_"$TODAY"\.sql || (echo "MysqlDump failed! Please CheckOut Or feedback to zhangge.net..." &amp;&amp; exit 2)
        $ZIP -P"$PPPPASS" -m "$back_path"/"$domain"\_db_"$TODAY"\.zip "$domain"\_db_"$TODAY"\.sql
elif [ "$1" == "file" ];then
        domain=$2
        site_path=$3
        back_path=$4
        test -d "$site_path" || (echo "$site_path not found! Please CheckOut Or feedback to zhangge.net..." &amp;&amp; exit 2)
        test -d "$back_path" || (mkdir -p "$back_path" || echo "$back_path not found! Please CheckOut Or feedback to zhangge.net..." &amp;&amp; exit 2)
        test -f "$back_path"/"$domain"\_"$TODAY"\.zip &amp;&amp; rm -f "$back_path"/"$domain"\_"$TODAY"\.zip
        $ZIP -P"$PPPPASS" -9r "$back_path"/"$domain"\_"$TODAY"\.zip "$site_path"
elif [ "$1" == "--help" ];then
        clear
        echo =====================================Help infomation=========================================
        echo 1. Use For Backup database:
        echo The \$1 must be \[db\]
        echo \$2: \[domain\]
        echo \$3: \[dbname\]
        echo \$4: \[mysqluser\]
        echo \$5: \[mysqlpassword\]
        echo \$6: \[back_path\]
        echo
        echo For example:./backup.sh db zhangge.net zhangge_db zhangge 123456 /home/wwwbackup/zhangge.net
        echo
        echo 2. Use For Backup webfile:
        echo The \$1 must be \[file\]:
        echo \$2: \[domain\]
        echo \$3: \[site_path\]
        echo \$4: \[back_path\]
        echo
        echo For example:./backup.sh file zhangge.net /home/wwwroot/zhangge.net /home/wwwbackup/zhangge.net
        echo =====================================End of Help==============================================
        exit 0
else
        echo "Error!Please Usage --help to get help infomation!"
        exit 2
fi
```

修改的脚本大致做了如下变更：
    - 将原脚本中的日改成日期（Line 5）
    - 将密码单独改为一个变量（Line 6）
    - 为deb系系统自动安装zip（Line 13）
    - Shell脚本语法及一些拼错单词的修正

**在取得这份脚本之后请打开并将第六行的PPPPASS变量改成你想要给备份包设置的密码**

#### 使用指南
##### 备份文件：
```shell
./backup.sh file <域名> <网站所在文件夹> <备份文件存放路径>
```

Example:

```shell
./backup.sh file zhangge.net /home/wwwroot/zhangge.net /home/wwwbackup/zhangge.net
```

##### 备份数据库：
```shell
./backup.sh db <mysql服务器域名/ip> <数据库名> <mysql用户名> <mysql密码> <备份文件存放路径>
```

Example:

```shell
./backup.sh db zhangge.net zhangge_db zhangge 123456 /home/wwwbackup/zhangge.net
```

建议把备份文件都放在同一个文件夹里 这样方便后续七牛工具上传

这样数据库和网站就打包好啦ヾ(≧O≦)〃 那么就可以进入到下一步啦

### 使用七牛云工具进行上传

先到官网开一个私有bucket（如果没有注册的话去注册一个）

然后点击个人面板->密钥管理

如果没有密钥则生成之 有密钥就请记下AK(Access Key)和SK(Secret Key)

------

原作者当年用的qrsync在七牛官网已经不见踪影

因此我又对照官网文档用新工具上传

先到项目Github地址去下载qrsync二进制文件

[Github/qshell](https://github.com/qiniu/qshell)

![](https://milkice.me/wp-content/uploads/2017/04/1.png)

解压 根据你的cpu找到相应的qshell工具

大部分linux服务器应该都是qshell_linux_amd64

解压好了之后

在同文件夹下输入如下命令（以linux amd64版为例）

```
./qshell_linux_amd64 account <ak> <sk>
```

把ak和sk换成之前在七牛官网获取到的两个Key

如果成功则没有输出

接下来创建一个上传配置文件

这里以QiniuConfig.conf为例
```
{
   "src_dir"            :   "/home/milkice/backup", #将这里改成有备份文件的那个文件夹
   "bucket"             :   "ILoveDrinkingMilkice",  #改为备份bucket
   "rescan_local"       :   true,
   "overwrite"          :   true
}
```
将这个配置文件最好和qshell放置于同一文件夹下

然后执行

```
./qshell_linux_amd64 qupload 2 QiniuConfig.json
```

~~~这里的2其实就是线程数 如果对自己的网络有信心的话可以试着增加~~~

等到上传完毕之后请到七牛运官网查看，如果有文件就说明上传成功

------

### 设置自动备份网站

既然已经上传成功了总不能以后都手动备份吧

所以还要通过设置crontab实现自动备份

在终端下输入

```
crontab -e
```

进入crontab工作编辑模式

然后根据如下规则：

```
15 3 * * * /root/scripts/backup.sh file zhangge.net /home/wwwroot/zhangge.net /home/wwwbackup/zhangge.net  >/dev/null 2>&1
```

前5列表示执行频率，具体为 分  时 日 月 周，工具默认是定义在【日】这一列，代码中的 \*/7 表示每7天备份一次 \*表示全部都匹配

所以这行的意思就是在每天的3点15分执行backup.sh开始备份

然后按Esc键 再输入:x就可以生效了

关于vim和crontab更多的使用方法请谷歌 资料还是挺多的

------

当然啦也可以根据自己的需求自定义时间

比如我的crontab是这样的

```
0 5 * * 0 /home/milkice/backup.sh file milkice.me /var/www/html /home/milkice/backups
```

表示每周日的0：05分调用backup.sh进行打包

实际上要完成全部的备份操作不止一行命令

因为整套流程是先打包再上传

必须要一套动作连起来

所以实际运用上要多条命令

比如我的命令是这样的

```
0 0 * * 0 /home/milkice/pack.sh file milkice.me_wordpress /var/www/html /home/milkice/backups/wordpress
0 5 * * 0 /home/milkice/pack.sh db milkice.me wordpress wordpress ILoveDrinkingMilkice /home/milkice/backups/wordpress
0 10 * * 0 /home/milkice/qiniu/qshell_linux_amd64 qupload 2 /home/milkice/qiniu/QiniuConfig.conf
```

即周日0点开始打包网站文件 0：05开始打包数据库 0：10正式上传

这样就能做到自动备份了...免费而且特别方便（

------

就这样...虽说没多备份方便

但是自己主动推送的文件当然比客户端要放心啊（雾

以上
