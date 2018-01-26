---
title: Ubuntu版Telegram CJK（中韩日）字体优化方案
date: 2017-05-13 20:01:01
tags:
  - CJK
  - Font
  - Telegram
  - Ubuntu
categories:
  - Tech
  - Maintenance
thumbnail: https://milkice.me/wp-content/uploads/2018/01/hexo-banner-ubuntu-telegram-cjk-font-optimization.png
---

Without a better font rendering solution, it's just trash.

### 前言
> Telegram是一款俄罗斯人开发的IM软件，由于其安全性高、功能多、表情包丰富（这是重点！）、客户端优雅简介、跨平台、API强悍、完全免费，同时亦可用作逃避审查的通讯工具，因此深受国内IT相关人士喜爱

基于Qt开发的桌面端Telegram对于东亚语系字体支持并不是很好
打开聊天框
映入眼帘的锯齿字体仿佛把人们带回了XP时代（雾
对于Windows端Telegram来说 已经有人研究过这个问题了 只要替换一个dll即可
Ubuntu端呢 虽然字体要比Windows端好看 但是也没好到哪去
![Original Style](https://milkice.me/wp-content/uploads/2017/05/ubuntu-telegram-cjk-font-1.png)
同时百度与谷歌上也几乎找不到调教Ubuntu版Telegram中文字体的教程 只能自己尝试了

*说实话本文采用的是一种非常dirty的方式强行改变字体渲染
之前在水博客的时候看到Meto有更好的字体优化方案 贴在这里 推荐先去用他的方案折腾 如果无果再看本文的方案*

[萨摩公园/修复 Ubuntu 中文字体渲染](https://i-meto.com/fix-chinese-font-display/)

但是前段时间也有用户反馈称两种方案在Ubuntu 16.04上均失效 所以可能两种方案都已过有效期 还请各位自行实验（毕竟已经切到Arch系去了_(:зゝ∠)_）

注：实验环境如下
1. Ubuntu版本：Ubuntu 16.04
2. Telegram版本：Telegram 1.0.29

### 初次尝试
*P.S.:该段为本奶冰在探索字体优化方案中的一个小插曲*
*与最终优化方案关联不大*
*如果想要最后方案请跳过本段*

注：编译环境如下
1. Ubuntu版本：Ubuntu 14.04
2. 运行内存：8G RAM + 8G SWAP
3. 硬盘空间：100 GB
4. CPU: 4 cores

一开始我是从代码入手的 想在代码里强制指定字体
翻了下Github上的telegram源码 发现Telegram内置了Open Sans字体
那么把自带的Open Sans字体换成别的CJK字体，比如Source Han Sans不就可以了么
于是我在vultr上开了个VPS当编译机 参照了官方的[doc](https://github.com/telegramdesktop/tdesktop/blob/dev/docs/building-cmake.md)编译
里面有两个坑 
1. libva项目已转移 文档里面的repo地址是旧地址
新地址在这里[Github/libva](https://github.com/01org/libva)
2. 编译过程中如果出现

```
make[2]: *** No rule to make target '/usr/lib/libicutu.a', needed by 'codegen_numbers'.
```

请执行下列命令(with root)

```shell
for libname in libicutu.a libicui18n.a libicuuc.a libicudata.a; do
    sudo ln -s /usr/lib/x86_64-linux-gnu/${libname} /usr/lib/${libname}
done
```

即可修正

这是我在编译过程中碰到的错误 当然也有碰到一些依赖错误我就不贴了
只说两点：
> 一开始我是用2G机器编译的，到86%之时提示Memory exhausted内存不足
后来加了2G swap重编译 还是内存不足
之后把vps物理内存升到了4G 加了4G swap 还是不行
最后气了 8G RAM+8G SWAP 总算编译通过
是我编译过程弄错了还是怎么 我到现在还不敢相信编译一个Telegram**居然**要16G内存
编译完毕后生成的两个binary，一个Telegram一个Updater，Updater正常
可是Telegram文件体积居然**高达**700M 我又怀疑我做错了什么……

其实我只是把官方的代码原封不动地编译了下
在编译成功后奶冰打算把字体换掉重新编译
可是后来某人的一句话让我有了另外一个idea

### 另辟蹊径
*第二次编译前奶冰和ItsLucas在吐槽Telegram字体*

![Conversation with Lucas](https://milkice.me/wp-content/uploads/2017/05/ubuntu-telegram-cjk-font-2.png)

最后一句话给了我想法
对啊 既然Telegram内置的Open Sans不支持中文
它实际上是调用系统中文字体显示中文的
那么 把系统中文字体替换掉不也就好了么

------

打开终端，安装font-manager

```
sudo apt install font-manager
```

安装好后打开，我一个一个字体翻了过去找一些渲染怪异的字体
在几遍测试之后找到了如下的字体 这些字体就是影响Telegram使用体验的<em>罪魁祸首</em>

![Font to be disabled 1](https://milkice.me/wp-content/uploads/2017/05/ubuntu-telegram-cjk-font-3.png)

![Font to be disabled 2](https://milkice.me/wp-content/uploads/2017/05/ubuntu-telegram-cjk-font-4.png)

选中要禁用的字体 点击右侧的<i class="fa fa-ban"></i>标志即可禁用

**P.S.:通过试验发现Noto Sans字体家族是Ubuntu系统高度依赖的字体，一旦Noto Sans中文字体被禁会导致系统全局字体发生异常，因此只能禁用除简体中文外其他东亚语言字体**

关闭窗口后重启Telegram 瞬间界面好看了很多

![After 1](https://milkice.me/wp-content/uploads/2017/05/ubuntu-telegram-cjk-font-5.png)

------

![After 2](https://milkice.me/wp-content/uploads/2017/05/ubuntu-telegram-cjk-font-6.png)

经测试，对多国语言支持良好

至此全部结束

( ¯•ω•¯ )
