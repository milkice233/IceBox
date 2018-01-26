---
title: GcmForMojo 安全相关
tags:
  - GcmForMojo
  - Perl
  - MojoWebQQ
categories:
  - Tech
  - Develop
date: 2017-03-18 19:27:34
---
既然都已经入了Google坑，为什么不享受GCM所带来的便利，比如推送QQ消息呢？

### 在开始前

------

目前关于QQ消息推送共有两种解决方案

一为由周俊开发的GcmForMojo，另外一个则为Rikka开发的FcmForMojo

其中FcmForMojo仅为Android 7.0+用户服务且优化了回复消息的使用体验，如果Android版本允许的话尽量使用FcmForMojo以获得最佳体验

本篇针对GcmForMojo，请注意有没有走错片场，谢谢配合

------

*本奶冰在原版FcmForMojo的基础上做了些许修改使得其可以在Android L以上系统正常工作并改名为FcmForMojoL，如果希望获得更好的视觉体验请尝试使用FcmForMojoL
项目地址：[Github/Fcm-for-Mojo-L](https://github.com/milkice233/FCM-for-Mojo-L)  *

------

**在此解释下，上述两个app名中的GCM与FCM只是为了区别名字，实际上两个app均采用Google新版的FCM进行消息推送，不存在消息推送上的区别**

### 准备工作

------

请注意本篇教程是基于基础Mojo::WebQQ已经搭建完毕的前提下对于OpenQQ的安全性进行优化

如果你还没有搭建好基础Mojo::WebQQ的话可以参照kotomei的教程

[Kotomei's Blog/Gcmformojo的部署与设定](https://blog.kotomei.moe/2017/01/21/GCM%20For%20Mojo%20的部署与设定/)

如果上面的链接挂了就用下面这个

[Gist/Gcmformojo的部署与设定](https://gist.github.com/kotomei/5367a003cd16d05e075c21a7f360b09a)

<del>如果基础教程有问题全部丢给kotomei同学，这锅我不背</del>

**本教程已经尽可能最简化，即便未接触过Perl编程的小伙伴在一番折腾后也应能达成目标，所以请多尝试几次，有问题时可留言询问或直接私聊
另外方便起见，本篇全篇以Mojo::WebQQ为例，如果要增强微信推送的安全性只需引入Mojo::Weixin库后按照本教程模仿即可**

### 简单分析

------

众所周知其实GcmForMojo的回复功能是基于Mojo::Webqq中的OpenQQ插件的

数据传送默认通过HTTP完成

官方示例代码如下：
```perl
$client->load("Openqq",data=>{
        listen => [ {host=>"0.0.0.0",port=>5000}, ] , #HTTP协议
        auth   => sub {my($param,$controller) = @_},
        post_api => 'http://xxxx',
});
```
如果就这样配置的话很容易就会想到问题

- OpenQQ插件使用的是HTTP协议，意味着可能有数据被监听的风险
尤其像现在免费WiFi盛行 通过HTTP传送极易被窃取消息（数据未被加密）
- OpenQQ端口默认没有鉴权，任何人都可以调用OpenQQ接口以该QQ的身份向外发送消息（未认证的访问）
不过好在官方也有一套解决方案

### HTTPS加密

------

针对问题一，使用HTTPS而并非HTTP传送消息即可保证消息不被窃取
在Mojo::WebQQ的文档中给出了OpenQQ在HTTPS下工作的配置代码
```perl
$client->load("Openqq",data=>{
        listen => [{
            host    =>"0.0.0.0",
            port    =>443,
            tls     =>1, 
            tls_ca  =>"/etc/tls/ca.crt",
            tls_cert=>"/etc/tls/server.crt",
            tls_key =>"/etc/tls/server.key",
            },
        ],
});
```
根据测试，tls_ca参数基本上不会用到，可以直接在tls_ca前加上#注释

由于Webqq是在Mojo的基础上开发的，因此只要是Mojo支持的HTTPS加密方式则均可使用
打开pl文件，把原来的listen一行删除再贴上如上代码，然后把tls_ca，tls_cert和tls_key的值改成自己的证书文件所在的路径

有两点需要注意：

1. 需要注册一个自己的域名 毕竟HTTPS证书是和域名挂钩的
2. 需要购买HTTPS证书 按理来说如果是自己生成的证书只要在客户端信任该证书也可使用 不过我没测试过 还需各位自行测试
如果一切都配置完毕且确认没有问题，请将GcmForMojo中回复地址里的http改成https即可

------

#### 故障排除
*如果配置证书后在GcmForMojo客户端无法回复消息，在排除脑残错误之后可参考下面的步骤修复*

请注意，如果使用的证书是Let’s Encrypt或Comodo签发的证书，则Mojo使用该证书前可能需要进行特殊处理（不代表不是这两个CA的证书就可以直接使用！）

请创建一个新的证书文件名为newcert.crt，用文本编辑器打开之（不要用Windows自带记事本！）

然后打开cert.pem（签发给你域名的证书），把该文件的内容复制到newcert.crt

然后找下证书商给你签发的证书里有没有类似chain.pem或者fullchain.pem文件（如果两个文件同时存在优先使用后者），如果有，打开该文件，将内容追加到newcert.crt里

这样操作之后newcert.crt内容应该是这样的
```
----BEGIN CERTIFICATE-----
MII... the leaf certificate
-----END CERTIFICATE-----
-----BEGIN CERTIFICATE-----
MII... the first intermediate certificate, i.e. the one which signed the leaf cert
-----END CERTIFICATE-----
```
然后将tls_cert指向newcert.crt即可使用

**总结成一句话就是补全证书链**

------

*不知道如何操作？请对照最下面的总示例代码进行修改*

------

### 配置盐值

------

针对问题二，服务端与客户端同时配置盐值
OpenQQ插件中的auth参数就是用来解决接口被盗用的问题

该解决方案原理如下（参考了支付宝付款接口对于参数的验证操作）：

*在服务端与客户端配置好相同的盐值，当客户端向服务端发起请求时（比如客户端要给某人发送消息），客户端会将各个参数按照参数名的字母表顺序升序排列并依次把几个参数串起来，再将盐值追加其后进行md5摘要操作，把md5后得到的hash存放在新的参数sign里发往服务端（盐值不会跟着发往服务端），服务端会同样遵循客户端的步骤将各个参数与存储在服务端上的盐值连接&md5之，再将md5后得到的hash与sign进行比较，一致则表示服务端与客户端盐值相同即表示通过，服务端会继续执行客户端要求的操作，如果不一致则拒绝操作，达到防止盗用接口的目的*

这里简单介绍下盐值
> 盐（Salt），在密码学中，是指通过在密码任意固定位置插入特定的字符串，让散列后的结果和使用原始密码的散列结果不相符，这种过程称之为“加盐”。

*上述内容摘自维基百科*

可以这么说，盐值可以自定且必须是服务端和客户端共有，在传输数据时用该盐值稍微处理下生成签名后发送对方，对方会验证签名来判断对方是否可信。任何第三方除非暴力破解，否则没有盐值则无法通过签名验证。

通过原理不难看出该方案要求服务端和客户端都要配置好相同盐值

因此新版GcmForMojo已经添加了回复校验功能

现在用户要做的就是配置服务端

打开pl文件
在pl文件的开头加上一行代码（如果已加就不用管它）
```perl
use Digest::MD5 qw(md5 md5_hex md5_base64);
```
之后定位到OpenQQ段代码，在load()参数前加上如下行，同时请修改盐值

```perl
sub GcmForMojoAuth{
    my($param,$controller) = @_;
    my $secret = 'MilkiceIsHere'; #请更改该行的盐值
    my $text='';
    foreach $key (sort keys %$param){
        if($key ne 'sign'){
            $value =$param->{$key};
            $text.=$value;
        }
    }
    if($param->{sign} eq md5_hex($text.$secret) ){
        return 1;
    }
    else{
        return 0;
    }
}
```
为了防止被爆破盐值，尽量使用足够长的随机生成的盐值，可在这个网站[51240](http://suijimimashengcheng.51240.com/)生成

而后在load()里加入auth参数（如果已有auth参数，将值改成GcmForMojoAuth）

```perl
$client->load("Openqq",data=>{
        listen => [ {host=>"0.0.0.0",port=>5000}, ] ,
        auth   => GcmForMojoAuth, #加入这一行
        post_api => 'http://xxxx',
    });
```

最后，将盐值填入GcmForMojo客户端的“输入盐值”里即可

------

有点晕？你可以看下如下的代码，是配置好HTTPS+盐值后的样例，可以参照下面的代码适当修改

```perl
sub GcmForMojoAuth{
    my($param,$controller) = @_;
    my $secret = 'WowSuchAMilkice'; #请修改该行盐值
    my $text='';
    foreach $key (sort keys %$param){
        if($key ne 'sign'){
            $value =$param->{$key};
            $text.=$value;
        }
    }
    if($param->{sign} eq md5_hex($text.$secret) ){
        return 1;
    }
    else{
        return 0;
    }
}
$client->load("Openqq",data=>{
    listen => [{
        host =>"0.0.0.0",
        port =>25565,
        tls =>1,
        #tls_ca =>'/home/milkiced/milkice_ca.crt',
        tls_cert =>'/home/milkice/milkice_cert.crt',
        tls_key =>'/home/milkice/milkice_cert.key'
    }] ,
    auth => GcmForMojoAuth,
});
```
这样子基本上就已经保证消息的安全性了，大功告成~
(/ω＼)

### 参考资料

------

[CPAN/Mojo::WebQQ::Plugin::OpenQQ](https://metacpan.org/pod/distribution/Mojo-Webqq/doc/Webqq.pod#Mojo::Webqq::Plugin::Openqq)

同时感谢灰灰，周俊以及GcmForMojo群各群友给予的帮助与支持~qwq

P.S.:Mojo::Webqq官方文档挺详细的，感兴趣的可以多了解下，功能丰富堪称瑞士军刀

------

写的第一篇博文 可能描述上有一些错误或者疏漏

如果有疑问的请毫不犹豫地在下面评论告知我 感谢~
