---
title: ADSL猫（RG100A）通过设置VLAN虚拟WAN口
date: 2017-06-07 16:41:41 
tags:
  - Openwrt
  - RG100A
  - Router
  - VLAN
categories:
  - Tech
  - Web
thumbnail: https://milkice.me/wp-content/uploads/2018/01/hexo-banner-simulate-wan-ports-via-vlan-on-rg100a.png
---

嘛，折腾这玩意，不掉坑还行，一掉坑就是巨坑

### 前言
自从家里宽带升到百兆之后
电信师傅就把我原来那个配置好的刷了tomato的路由器换成了电信的光猫

按照惯例我在网上找破解这个光猫超级密码的方法，但是我找了很多试了很多办法都不行，想刷成别的系统找了下似乎也没有适配这个机型的系统，于是就很无奈啊

本来我家打印机是接在那个tomato路由器上的这样一来我就可以在手机上直接打印了，可是现在我还得把打印机搬到我的房间里去，非常麻烦

想了想自己的仓库里还有个老早之前还是adsl上网的时候电信送的一个猫，不知道这个猫可不可以刷成openwrt代替原来的tomato路由器呢，于是翻箱倒柜找到了这个猫，型号是Alcatel RG100A，查了下openwrt官网还真有，而且连最新的lede系统（openwrt的一个分支）也有编译这个机型的系统，遂刷之

刷完之后发现一个问题，这是adsl猫，是接电话线的，所以没有WAN口，woc那岂不是这个猫不能上网么，这年头谁还用ADSL拨号上网的？

谷歌搜了下，似乎可以用VLAN虚拟出一个WAN口使得猫能够通过一个LAN口连接外网（其实虚拟WAN口这个说法不是太准确，VLAN的功能远不止如此，有点类似交换机）那我就用LEDE自带的VLAN功能模拟一个WAN口，这篇文章就用我自己的贝尔RG100A为例

<span style="color: red;">警告：配置VLAN是件非常危险的事情，配置错误很可能导致设备无法通过任何途径访问（inaccessbile），这时候只能重置设备甚至需要重新刷机，请严格遵守本文的步骤并一定一定谨慎操作，避免不必要的麻烦发生</span>
P.S.:来自一个掉坑无数次的人的忠告，奶冰至少为这玩意重刷系统10次以上

### 基础准备

当然需要有一台Openwrt运行的路由器啦
这我就不说了，Openwrt和LEDE均可，网上教程很多
或者 你懒得去找的话
[戳我啊](http://lmgtfy.com/?q=%E6%80%8E%E4%B9%88%E5%88%B7Openwrt)

### 前期准备
*P.S.：假定现在是通过网线连接RG100A的*

刷好系统之后，进入web配置界面（luci），用默认密码root登录后改管理员密码，**接下来请一定打开WiFi接入点**！
![vlan-1](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-1.jpg)
![vlan-2](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-2.jpg)

将你的设备通过WiFi连接至LEDE，这是为了防止VLAN配置错误后导致无法访问RG100A

### 正式开始作死
转到switch

![vlan-3](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-3.jpg)

按图配置VLAN

![vlan-4](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-4.jpg)

这里呢，想想你要拿哪个端口当WAN口，我这里是拿第三个网口当WAN口，其他端口请按照图里的设置进行更改 

*胡说八道的解释:VLAN1代表WAN，所以只有一个是Off，除CPU外其他都是Untagged；VLAN2代表LAN，所以除WAN口和CPU口其他都是Off；其实我也不是太懂Off,Tagged,Untagged的区别，有空补VLAN知识（摊手*

这时候请注意，再按图配置好之后 **请点击Save，不要点击Save & Apply！**

完成后来到Interfaces界面对WAN和LAN分别配置

![vlan-5](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-5.jpg)
![vlan-6](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-6.jpg)
![vlan-7](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-7.jpg)
![vlan-8](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-8.jpg)

勾选eth1.1（你看的没错，桥接的是代表WAN的那个VLAN），最后**按Save不要点击Save & Apply！**

同理配置WAN

![vlan-9](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-9.jpg)
![vlan-10](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-10.jpg)

WAN不需要桥接，直接eth1.2，同理*Save Save Save*!!!

接下来看右上角 点击Unsaved Changes

![vlan-11](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-11.jpg)

确认下配置 如果是全新安装的话应该有12条更改

![vlan-12](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-12.jpg)

请注意这里是**Apply Apply Apply**，如果配置有误Apply之后还可以通过重启路由器的方式恢复，如果直接点击Save&Apply那就GG了

这时候将网线插入，如果回到luci主页后WAN口显示已联通，就说明没问题了

![vlan-13](https://milkice.me/wp-content/uploads/2017/06/openwrt-vlan-13.jpg)

这时候再回到Unsaved Changes，点击Save & Apply就可以啦 

<span style="background-color: black;">这是一篇怨气满满的文章</span>
