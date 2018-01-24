---
title: Kratos-M 原生Kratos主题优化版
date: 2018-01-15 22:14:05
tags:
  - Kratos
  - WordPress
---

![Kratos Image](https://milkice.me/wp-content/uploads/2018/01/kratos-demo.png)
> Kratos 是一款免费开源的两栏结构并且拥有自适应效果的主题，她能够在任何浏览器下进行友好体验的访问。Kratos 秉持了专心写作专心阅读的特点，简单大方的主页构造并采用了2：1的完美比例，使得博客能在臃肿杂乱的环境中脱颖而出。Kratos 主题内置了强大的主题后台控制平台，可以轻松设置关键字及站点描述，自定义的顶部样式（背景图 or 纯色），强大的底部社交化组件，以及漂亮的博客订阅功能组件，让你的网站更加与众不同！

*Content above copied from the original description of Kratos*
### 前言
若干月前IceBox还没建成时就找了一会儿的主题，挑来挑去总是不满意，但是后来逛到了Vtrois(三哥)的博客之后就被Kratos主题所吸引，IceBox也就定下来采取Kratos主题

不过后来呢，毕竟众口难调，有些时候希望能够自定义更多的地方，比如字体大小、超链接颜色之类的，但是改完之后主题升级又成了个问题，毕竟升级会抹掉之前所有的更改。

所以最好的方式便是fork一下原生Kratos主题然后自己重新修改，再把更新来源指向fork就可以了。
口头上虽说是简单，想法也早就有了，可就是没践行=。=趁几天这有动力也就fork了下原Kratos主题自行优化了下，重命名为Kratos-M

Kratos-M即目前本IceBox所用主题，Github项目地址在这里：[MilkiceForks/Kratos-M](https://github.com/MilkiceForks/Kratos)，欢迎点星或二次fork

当然如果需要原生Kratos的话，[介绍](https://www.vtrois.com/theme-kratos.html)在此，或直接从[Github](https://github.com/Vtrois/Kratos)上拉取源码

### 优化
* 将文章超链接字体改为浅蓝色(#2278bb)
* 在<head>处加入DNS预解析元标签以加快加载速度，预解析域名可在主题设置里设置
* 将文章字体大小改为14px（原16px）
* 底部加入Telegram图标，可在主题设置里设置
* 添加标签页颜色，可在主题设置里设置，效果可在移动端Chrome上呈现
* 移除了在移动设备上的边距 （[Credits](http://16bing.com/2017/04/30/kratos-mobile-padding)）
* 适配GithubUpdater
* 添加友链模板（基于osu!web beatmapset界面样式表）
* 修改font-family使得能在任意平台上完美展示内容
* 文章字重改为Normal（个人喜好）
* 修改word-break为break-word以更好地支持带有英语的语段

### To-Do List
* <del>字体大小更改不完整，部分区域仍为16px</del>
* 将各种方框圆角设为2px
* 优化alert-* (bootstrap明明自带alert-*系列，不知道为什么Vtrois还要在style.css里自己实现一个0 0)
* （暂无想法 欢迎评论区内补充）

### 注意事项
* 由于WordPress自带更新组件的原因，原版Kratos在一次升级后便无法再次升级，为此Kratos-M适配了GithubUpdater，请到[Github/GithubUpdater](https://github.com/afragen/github-updater)下载安装GithubUpdater后通过此插件界面安装Kratos以保证持续更新
* 友链界面主要样式表来源于[osu!web项目](https://github.com/ppy/osu-web)，在[osu!/beatmapsets](https://osu.ppy.sh/beatmapsets)里便能发现端倪；同时友链模板的使用较为复杂，以后找时间重新补坑，具体效果可在[IceBox/Friends](https://milkice.me/friends)中查看

### Credits
* 原作者 Vtrois: [Vtrois's Blog](https://www.vtrois.com)
* Modder Milkice: [Milkice's IceBox](https://milkice.me)

---

* jQuery: [https://jquery.com/](https://jquery.com/)
* bootstrap: [https://getbootstrap.com/](https://getbootstrap.com/)
* font-awesome: [https://fontawesome.io/](https://fontawesome.io/)
* osu!web: [https://github.com/ppy/osu-web](https://github.com/ppy/osu-web)
* ...以及其他开源项目

