---
title: 安装并使用 EFB：在 Telegram 收发 QQ 消息
date: 2018-09-17 00:04:00
tags:
  - Develop
  - EFB
  - ehForwarderBot
  - EQS
  - Python
  - QQ
  - Telegram
categories:
  - Tech
  - Develop
  - Python
thumbnail: https://hexo.milkice.me/wp-content/uploads/2018/12/hexo-banner-EQS-banner.png
---

终于可以不用装QQ啦w

![EFB-QQ-Slave Banner](https://hexo.milkice.me/wp-content/uploads/2018/09/EFB-QQ-Slave-banner.png)

EH Forwarder Bot（简称 EFB）是一个可扩展的聊天平台隧道框架，基于 Python 3。同时 EFB 配备了详尽的文档，欢迎有兴趣的朋友们开发自己的主端或从端，来支持更多的平台。EFB [在 GitHub 中开放了源代码](https://github.com/blueset/ehforwarderbot)，并且[在 Read The Docs 平台上发布的开发文档](https://ehforwarderbot.readthedocs.io/)（英文，`en-US`）。

本文主要介绍了如何在一个虚拟服务器 (VPS) 中安装并配置 EFB、Telegram 主端和 QQ 从端，以及如何使用 Telegram 主端来收发 QQ 消息。

## 0x00: 说明
* 本文档基于 EFB v2.0.0b11, efb-qq-slave v2.0.0a3 版本制作而成。较新的版本可能会有不同的安装步骤。敬请注意。
* 本教程面向具有一定背景知识的进阶用户，如有疑问，请您先在互联网上搜索（推荐 Google），若仍未解决、欢迎在 EFB Telegram 支持群组留言。
* 本教程中 QQ 从端所采用的客户端为 酷Q，因此包含安装 docker 的步骤，同时对闭源软件不喜者慎用本项目
* 本文基于 1a23 的 [安装并使用EFB：在Telegram 收发微信消息](https://blog.1a23.com/2017/01/09/EFB-How-to-Send-and-Receive-Messages-from-WeChat-on-Telegram-zh-CN/) 改编而来，根据原文协议**本文协议临时变更为 CC BY-SA 4.0**，请知悉并无视页脚协议声明
* 本教程假定 酷Q 和 EFB 运行在同一机器上，所以 `CoolQ Post Url` 和 `CoolQ API Url` 地址均为 127.0.0.1， 对于复杂场景（酷Q 和 EFB 在不同机器上），上述两个参数需要进行对应的修改，同时防火墙应允许双方的数据包通过，以便双方的请求不会被防火墙拦截。如果双方通信内容必须经过 Internet 传输，请确保已配置 `Access Token` 并启用 `HTTPS` 确保双方通信内容不会在公网被窃听/篡改。

   有关详细信息，请访问 [配置文件文档](https://cqhttp.cc/docs/4.4/#/Configuration) 和 [HTTPS 文档](https://github.com/richardchien/coolq-http-api/wiki/HTTPS) 。

## 0x01: 用料
在开始之前，请准备：

* 电脑 一台
推荐 Linux、macOS 操作系统
部分手机亦可使用，但操作会略微繁琐。
* Telegram 账号 一枚
* 可用的科学上网方式 若干
* Windows 用户需要 SSH 客户端 一枚，常用 PuTTY
* 墙外 VPS 一枚

## 0x02: 构建环境
这里我们使用 Ubuntu 18.04 作为例子。CentOS、Arch 等其他发行版除去包管理器指令之外操作基本相同。（因为实在是不想再加钱开一个 VPS，所以下面的指令运行在同等版本的 Docker Image 上）

首先通过 SSH 客户端连接到你的 VPS。

---------------------------

#### 0x02.1: 安装 Python 与非 Python 依赖
输入以下指令。

注：以下安装的部分软件包可能已经预安装在你的系统中。但请注意将已安装的 Python 3 版本升级到 3.6 或以上。

```bash
sudo apt-get install python3.6 libopus0 ffmpeg libmagic1 python3-pip git nano docker.io
```

----------------------------

#### 0x02.2: 从 PyPI 下载 ehForwarderBot
对于全新安装的 pip，可能需要安装一些基础库
```bash
pip3 install setuptools wheel
```
完毕之后再安装 ehforwarderbot
```bash
pip3 install ehforwarderbot
```

## 0x03: 配置 EFB
#### 0x03.1: 配置主端与从端
全新安装的 ehForwarderBot 需要指定一个唯一的主端及一个或多个从端才能正常工作
先为 ehForwarderBot 创建配置文件夹
```bash
mkdir -p ~/.ehforwarderbot/profiles/default/
mkdir -p ~/.ehforwarderbot/profiles/default/blueset.telegram
mkdir -p ~/.ehforwarderbot/profiles/default/milkice.qq
```
而后使用你最喜欢的编辑器在 ~/.ehforwarderbot/profiles/default/ 创建文件 config.yaml，内容如下
```yaml
master_channel: blueset.telegram
slave_channels:
- milkice.qq
```

_master_channel 指定唯一主端，而slave_channels下可指定多行的从端，此处由于目标为配置 QQ 从端仅添加了 QQ 从端，后期如有需求可以多从端混用_

---------------------------

#### 0x03.2: 配置主端 ETM(EFB-Telegram-Master)
##### 0x03.2.1: 创建 Telegram Bot
Telegram Bot 是 EFB（Telegram 主端）的出口，也是呈献给用户的渠道。我们在这里使用了 Telegram 官方的 Bot API，以最大化利用 Telegram Bot 所提供的各种便利功能。

要创建一个新的 Bot，要先向 [@BotFather](https://t.me/BotFather) 发起会话。发送指令 `/newbot` 以启动向导。期间，你需要指定这个 Bot 的名称与用户名（用户名必须以 `bot` 结尾）。完毕之后 @BotFather 会提供给你一个密钥（Token），妥善保存这个密钥。请注意，为保护您的隐私及信息安全，请不要向任何人提供你的 Bot 用户名及密钥，这可能导致聊天信息泄露等各种风险。

接下来还要对刚刚启用的 Bot 进行进一步的配置：允许 Bot 读取非指令信息、允许将 Bot 添加进群组、以及提供指令列表。

发送 `/setprivacy` 到 @BotFather，选择刚刚创建好的 Bot 用户名，然后选择 "Disable".
发送 `/setjoingroups` 到 @BotFather，选择刚刚创建好的 Bot 用户名，然后选择 "Enable".
发送 `/setcommands` 到 @BotFather，选择刚刚创建好的 Bot 用户名，然后发送如下内容：
```asa
link - 将会话绑定到 Telegram 群组
chat - 生成会话头
recog - 回复语音消息以进行识别
extra - 获取更多功能
```

然后还需要获取你自己的 Telegram ID，ID 应显示为一串数字。获取你自己的 ID 有很多方式，你可以选择任意一种。下面介绍两种可能的方式。

1. Plus Messenger
如果你使用了 Plus Messenger 作为你的 Telegram 客户端，你可以直接打开你自己的资料页，在「自己」下面会显示你的 ID。

2. 通过 Bot 查询
很多现存的 Bot 也提供了 ID 查询服务，直接向其发送特定的指令即可获得自己的数字 ID。在这里介绍一些接触过的。
  * [@get_id_bot](https://t.me/get_id_bot) 发送 /start
  * [@XYMbot](https://t.me/XYMbot) 发送 /whois
  * [@mokubot](https://t.me/mokubot) 发送 /whoami
  * [@GroupButler_Bot](https://t.me/GroupButler_Bot) 发送 /id
  * [@jackbot](https://t.me/jackbot) 发送 /me
  * [@userinfobot](https://t.me/userinfobot) 发送任意文字
  * [@orzdigbot](https://t.me/orzdigbot) 发送 /user

留存你的 Telegram ID 以便后续使用。

-----------------------------------

##### 0x03.2.2: 安装 ETM
```bash
pip3 install efb-telegram-master
```

-----------------------------------

##### 0x03.2.3: 创建 ETM 配置文件
使用你最喜欢的编辑器在 `~/.ehforwarderbot/profiles/default/blueset.telegram/` 创建文件 `config.yaml`，内容如下
```yaml
token: "12345678:QWFPGJLUYarstdheioZXCVBKM"
admins:
- 123456789
```

_其中，`token` 填入你在 0x03.2.1 步骤中从 @BotFather 获取的 bot token，`admins` 下的数字则为管理员 Telegram ID，一行一个_

-----------------------------------

#### 0x03.3: 配置从端 EQS(EFB-QQ-Slave)

##### 0x3.3.0: 配置用户 docker 网络权限

对于部分系统（例如Ubuntu），若当前用户为非root用户可能会出现 docker run 时提示 Permission Denied 的情况 此时请以 root 用户身份执行本命令
```bash
usermod -aG docker $USER
```
$USER 请替换为用户名

完成后请关闭终端并重新打开，此时应能正常运行 docker

-------------------------------------

##### 0x03.3.1: 配置 docker 镜像

对于 Linux 系统来说，酷Q 较为方便的运行方式便是使用 Richard Chien 魔改的 由酷Q官方提供的 docker 镜像
请运行下列命令
```bash
docker pull richardchien/cqhttp:latest
mkdir coolq  # 包含CoolQ程序文件
docker run -ti --rm --name cqhttp-test --net="host" \
     -v $(pwd)/coolq:/home/user/coolq     `# mount coolq folder` \
     -p 9000:9000                         `# 网页noVNC端口` \
     -p 5700:5700                         `# 酷Q对外提供的API接口的端口` \
     -e VNC_PASSWD=MAX8char               `# 请修改 VNC 密码！！！！` \
     -e COOLQ_PORT=5700                   `# 酷Q对外提供的API接口的端口` \
     -e COOLQ_ACCOUNT=123456              `# 在此输入要登录的QQ号，虽然可选但是建议填入` \
     -e CQHTTP_POST_URL=http://127.0.0.1:8000   `# efb-qq-slave监听的端口/地址 用于接受传入的消息` \
     -e CQHTTP_SERVE_DATA_FILES=yes       `# 允许以HTTP方式访问酷Q数据文件` \
     -e CQHTTP_ACCESS_TOKEN=ac0f790e1fb74ebcaf45da77a6f9de47  `# Access Token` \
     -e CQHTTP_POST_MESSAGE_FORMAT=array  `# 回传消息时使用数组（必选）` \
     richardchien/cqhttp:latest
```
请将 docker run 命令中的参数根据注释改为相应数值

- 酷Q Pro用户请注意

  **请在docker run命令中添加额外参数** (`-e COOLQ_URL = "http://dlsec.cqp.me/cqp-tuling"`) **，以便docker下载CoolQ Pro而不是Air**

请注意，为了确保可以从 docker 内访问 ehforwarderbot，建议添加参数 ``--net ="host"``  。如果您遇到网络问题，请尝试删除此参数。

_P.S.: 对于 docker 和 EFB 在同一机器上的情况下，为了保证 EFB 可以访问到 酷Q 暴露的 API 端口，最简单的方案即是将 网络模式 改成 host_

 **请阅读 [docker 文档](https://cqhttp.cc/docs/4.4/#/Docker) 获悉更多的可配置选项.**

--------------------------------

##### 0x03.3.2: 登录

在浏览器内访问 http://<ip或者域名>:9000

请在noVNC终端中输入上述配置选项中的 VNC 密码登录，并使用QQ账户和密码在酷Q中登录QQ账号

--------------------------------

##### 0x03.3.3: 通过 PyPI 下载安装 EQS

```bash
pip3 install efb-qq-slave
```

---------------------------------
##### 0x03.3.4: 配置 ehForwarderBot 端

1. 为 `milkice.qq` 从端创建 `config.yaml` 配置文件

   **配置文件通常位于 `~/.ehforwarderbot/profiles/default/milkice.qq/config.yaml`.**

   样例配置文件如下:

   ```yaml
       Client: CoolQ                         # 指定要使用的 QQ 客户端（此处为CoolQ）
       CoolQ:
           type: HTTP                        # 指定 efb-qq-slave 与 酷Q 通信的方式 现阶段仅支持HTTP
           access_token: ac0f790e1fb74ebcaf45da77a6f9de47
           api_root: http://127.0.0.1:5700/  # 酷Q API接口地址/端口
           host: 127.0.0.1                   # efb-qq-slave 所监听的地址用于接收消息
           port: 8000                        # 同上
           is_pro: true                      # 若为酷Q Pro则为true，反之为false
           air_option:                       # 包含于 air_option 的配置选项仅当 is_pro 为 false 时才有效
               upload_to_smms: true          # 将来自 EFB主端(通常是Telegram) 的图片上传到 sm.ms 服务器并以链接的形式发送到 QQ 端
   ```
2. 控制台启动 `ehforwarderbot`, 大功告成!

## 0x04: 使用 EFB Telegram 主端
现在，在 Telegram 里面搜索你之前指定的 Bot 用户名，点击 Start（开始）即可开始与 QQ 互通消息了。

在最初，所有来自 QQ 的消息都会通过 Bot 直接发送给你，要回复其中的任意一条消息，你需要在 Telegram 中选中那条消息，选择 Reply（回复），再输入消息内容。

如果需要向新联系人发送消息，只需发送 /chat 指令，选择一个会话。之后这条消息就会变成一个「会话头」，回复这条消息就可以向指定的联系人或群组发送消息。

当消息过多时，来自不同会话的消息会使 Telegram 上面的会话混乱不堪。EFB 支持将来自指定会话的消息分流到一个 Telegram 群组中。

在 Telegram 中新建一个空群组，并将你的 Bot 加入到这个群组中。
（如果找不到自己的 Bot，请尝试在桌面版中创建，并在添加成员时搜索 Bot 的用户名）
回到 Bot 会话，发送 /link，选择一个会话，并点击 “Link”
在弹出的列表中选择刚刚创建的空群组即可
在绑定会话中，你可以像普通聊天一样直接发送消息。也可以通过指定回复的形式来 @ 其他人。
"Link" 功能亦可以屏蔽一些群组/用户的消息，在点击"Link"后选择免打扰即可
您亦可将一些会话连接到频道，此时需要特殊的link方式，请点击"Link"之后选择手动绑定，由机器人指导您进行绑定操作
请注意，若将会话绑定到频道，即意味着本回话只能接受消息无法发出，即便用户在频道发出消息也不会被理会

> 注意
虽然 Telegram 群组中的所有人可以看到会话全文，但是只有配置文件 (config.py) 中指定的管理员 (admins) 能够以你的名义发送消息到 QQ。EFB 支持设置多个管理员，但只有第一个管理员（按照输入排序）能够接收到所有消息，而且这有可能造成不必要的隐私问题，敬请注意。

以上就是 EFB Telegram 主端的基础用法。

## FAQ

**以下内容通用 针对所有客户端有效**

* Q - 如何在 主端(Telegram) 撤回消息？

   A - 如果 QQ 客户端支持该操作，请编辑该消息并在该消息前段加上 `rm`` 字样即可在QQ端撤回该消息 同时请注意发出的消息仅能在发出后2分钟内撤回
  
* Q - 如何在 主端(Telegram) 编辑消息？
  
   A - 直接使用 Telegram 的编辑消息功能即可

* Q - EQS 只支持 酷Q 吗？
  
  A - 现阶段 QQ端的开源现状并不如微信，加之腾讯阉割掉WebQQ一大堆功能，导致一大批原本以WebQQ为协议的应用几乎陷入不可用状态（不能收发图片，必须扫码登录或奇淫技巧密码登录等）。目前来说，以iOS/Android/Pad协议为基础的开源应用几乎没有，尚有的一些也处于疯狂改架构状态非常不稳定，在若干闭源方案中选择酷Q是因为酷Q插件体系较为成熟，HTTP API插件可以很方便地暴露接口给外部应用使用
efb-qq-slave 在设计架构的时候已经考虑到多客户端支持的可能性，所以如果有更好的 QQ 客户端 EQS 将会第一时间支持，也希望能够得到社区的力量一起协助完善项目

**以下内容仅针对于 酷Q 客户端有效**

* Q - 为什么我无法在 Telegram 中发送图片到QQ?

   A - 如果您正在使用 CoolQ Air，由于技术限制无法直接发送图片到QQ，请将配置文件中的 `is_pro` 改为 false 并将 `air_option` 中的 `upload_to_smms` 改为true即可变相发送图片（通过链接形式）

* Q - 为什么我无法接收/发送QQ语音？

   A - 酷Q官方以语音处理库太大为由并未将语音模块集成入酷Q，而是提供了一个带语音处理版本的酷Q供下载，目前暂时没有动力编写QQ语音消息的处理，如有需求请在 [这个Github Issue](https://github.com/milkice233/efb-qq-slave/issues/1) 中留言或在issue上发送表情，需求量较高将会考虑开发

* Q - 酷Q不同版本区别？

   A - [https://cqp.cc/t/23290](https://cqp.cc/t/23290) 同时请注意酷Q Air 不支持消息撤回

* Q - 目前暂未实现的功能？

   A - 好友请求处理，加群请求处理，尚未适配少部分消息类型（例如签到消息），语音发送/接收

* Q - 一段时间后 EQS 提示我 Cookie 过期需要手动刷新 Cookie？
   A - 这是由于一些特性需要从腾讯网站上获取，而酷Q提供的Cookie在腾讯外部网站不可访问但是酷Q仍可以使用该Cookie发送/接受消息，因而酷Q认为此Cookie一切正常并不会主动更新Cookie，此时需要手动强制刷新，具体教程请参阅此处 [efb-qq-slave/Workaround-for-expired-cookies-of-CoolQ](https://github.com/milkice233/efb-qq-slave/wiki/Workaround-for-expired-cookies-of-CoolQ)

* Q - 有时候无法显示来源消息的群名，只能显示群号？
   A - 可能用户加入新群之后酷Q并未即时更新群列表，此时可以先给机器人发送 /extra，然后由机器人给出的命令中选择 /{slave_id}_relogin 命令执行（该命令通常名为 /0_relogin），执行 /0_relogin -c，稍等片刻，如果酷Q端已开启自动登录则不需要人工干预群列表即可恢复正常，部分情况下可能需要用户再次登录 Web VNC 重新输入QQ密码登录


