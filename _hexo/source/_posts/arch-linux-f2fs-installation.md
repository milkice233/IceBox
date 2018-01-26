---
title: Arch Linux 使用F2FS作为根文件系统的安装方案 [ItsLucas]
date: 2017-06-07 22:03:37
tags:
  - Arch
  - F2FS
  - Linux
categories:
  - Tech
  - Maintenance
thumbnail: https://hexo.milkice.me/wp-content/uploads/2018/01/hexo-banner-arch-linux-f2fs-installation.png
---

F2FS被喻为SSD的救星，那么，能不能让Arch也用上F2FS呢？

### 一些BB
F2FS最近被炒的十分火热，某些号称18个月不卡顿的手机用的特别开心
那么我在想要不要把它用在Arch上

### 获取基本信息
首先请Follow官方的安装指南
[https://wiki.archlinux.org/index.php/installation_guide](https://wiki.archlinux.org/index.php/installation_guide)
在建立分区之前停下
我们的分区表大概长这样（我弄了个1G的虚拟磁盘，仅作为演示，不代表真实大小）
```
[root@Lucas-PC lucas]# fdisk -l /dev/loop0
Disk /dev/loop0：1 GiB，1073741824 字节，2097152 个扇区
单元：扇区 / 1 * 512 = 512 字节
扇区大小(逻辑/物理)：512 字节 / 512 字节
I/O 大小(最小/最佳)：512 字节 / 512 字节
磁盘标签类型：gpt
磁盘标识符：B0C1835B-5B1A-4F9D-8D95-C427A694AB7A
设备 起点 末尾 扇区 大小 类型
/dev/loop0p1 2048 411647 409600 200M Linux 文件系统
/dev/loop0p2 411648 2097118 1685471 823M Linux 文件系统
```
那么现在解释一下
使用F2FS安装Arch需要两个分区，一个稍小的boot分区（存放内核，格式为ext4）一个大的root分区（F2FS）
下面涉及到分区的操作均用boot和root表示，请自行代换自己的硬盘分区

### 开始操作

安装f2fs工具
```
pacman -S f2fs-tools
```

然后将两个分区格式化
```
mkfs.ext4 /dev/boot
mkfs.f2fs /dev/root
```

挂载分区到对应位置：
```
mount /dev/root /mnt
mkdir /mnt/boot
mount /dev/boot /mnt/boot
```

安装Arch基本系统：
```
pacstrap /mnt base base-devel
```

继续跟着Arch的安装向导，直到退出chroot之前
为新的系统安装F2FS支持：
```
pacman -S f2fs-tools
```

安装Grub BootLoader：
```
pacman -S grub
grub-install /dev/sda
grub-mkconfig -o /boot/grub/grub.cfg
```

### 重要注意事项
由于Grub不能识别F2FS（表现为不能提取UUID，启动时找不到/分区）
需要手动更改grub.cfg
先找到你的/分区的UUID，比如这样
```
[root@Lucas-PC lucas]# blkid
/dev/sda5: UUID="f462c200-c073-455c-8742-6f917b459018" TYPE="f2fs" PARTUUID="1179e852-25cb-47fa-84e9-d3f2e331835c"
```

然后编辑grub.cfg
找到所有”root=/dev/sdX”的地方，替换为
`root=UUID=<你的UUID>`

保存
最后运行
`mkinitcpio -p linux`

------

将F2FS相关驱动放入initramfs
卸载所有分区，重启完成安装

------

*Posted by ItsLucas*

*本文由ItsLucas杜撰 感谢ItsLucas的贡献*


