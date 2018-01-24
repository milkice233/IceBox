---
title: Hermit播放器强制HTTPS
tags:
  - Hermit
  - Music
  - PHP
  - WordPress
  - WordPress Plugin
date: 2017-03-25 07:08:08
---


*This post has been obsoleted.*

请使用由liwanglin12维护的Hermit X播放器，完美兼容HTTPS站点且添加了许多新的feature，可代替原版hermit播放器，且可无缝从hermit迁移至hermit X 甚至不需要改短代码

奶冰的icebox(Wordpress)已经用上了Hermit X 了♪(^∇^*)
*本篇特色图片来源于lwl12的blog，出处在此：[https://blog.lwl12.com/read/hermit-x.html](https://blog.lwl12.com/read/hermit-x.html)*


下面的original post仅做存档备用

------

### 前期介绍

> WordPress 虾米音乐插件 Hermit 由 @mufeng 制作，支持Html5+Flash的虾米/网易音乐播放器。支持添加虾米/网易单曲、专辑、精选集和远程音乐，直接在文章编辑页面集成了可视化编辑界面，非常方便</blockquote>
摘自Wordpress大学

的确，Hermit在同类网页播放器中做的是最好的，连文章图形化编辑器都有

![Hermit UI Editor](https://milkice.me/wp-content/uploads/2017/03/hermit_1.png)

美中不足的是由于网易和虾米的音乐api都是http形式，对于全站https的网站来说就会出现HTTP/HTTPS混合内容

像chrome这种浏览器就会在锁上加个黄三角 对于强迫症站长来说（包括我）是无法忍受的

所以我便尝试着手虾米/网易api强制走https

## 开始折腾

先抓包

![Xiami URI](https://milkice.me/wp-content/uploads/2017/03/hermit_2.png)

嗯果然走的是虾米http协议

把这段地址复制过来把http改成https试着直接从浏览器上访问

![Xiami HTTPS URI](https://milkice.me/wp-content/uploads/2017/03/hermit_3-300x37.png)

有小锁，说明虾米api其实可以走https的，只不过返回的默认是http

后来验证了下网易也是如此默认http但是支持https，那么就可以通过修改hermit源码来强制走https api了

------

找了会儿代码，关键代码在hermit/class.json.php

```php
public function song($song_id)
{
    $cache_key = "/xiami/song/" . $song_id;

    $cache = $this->get_cache($cache_key);
    if ($cache) return $cache;

    $response = $this->xiami_http(0, $song_id);

    if ($response && $response["state"] == 0 && $response['data']) {
        $result = $response["data"]["song"];

        $song = array(
            "song_id" => $result["song_id"],
            "song_title" => $result["song_name"],
            "song_author" => $result["singers"],
            "song_src" => $result["listen_file"],
            "song_cover" => $result['logo']
        );

        $this->set_cache($cache_key, $song);

        return $song;
    }

    return false;
}
```

以这段代码为例，最后返回了一个$song数组，再观察数组内容，不难发现song_src这个键值存储的是指向mp3地址的，同时song_cover应该是专辑封面jpg地址

根据刚才抓包的结果只要把这两个地址的 http:// 替换为 https:// 应该就可以了

但是我又不想一个一个键值去替换 ~~~其实是奶冰太懒~~~

参考了下php的str_replace()函数，发现可以替换数组中的字符串，那就方便很多了

```php
public function song($song_id)
{
    $cache_key = "/xiami/song/" . $song_id;

    $cache = $this->get_cache($cache_key);
    if ($cache) return $cache;

    $response = $this->xiami_http(0, $song_id);

    if ($response && $response["state"] == 0 && $response['data']) {
        $result = $response["data"]["song"];

        $song = array(
            "song_id" => $result["song_id"],
            "song_title" => $result["song_name"],
            "song_author" => $result["singers"],
            "song_src" => $result["listen_file"],
            "song_cover" => $result['logo']
        );
        $song = str_replace("http://", "https://", $song);
        //需要添加上一行
        $this->set_cache($cache_key, $song);
        return $song;
    }

    return false;
}
```

在return和set_cache前str_replace一下就可以了
当然这只是一个函数，后面还有很多函数呢也都要一一替换

只要看一个函数里会不会涉及mp3地址以及封面地址就知道要不要替换了

比如在album()函数里需要替换的是$album

```php
$album = array(
    "album_id" => $album_id,
    "album_title" => $result['album_name'],
    "album_author" => $result['artist_name'],
    "album_cover" => $result['album_logo'],
    "album_count" => $count,
    "album_type" => "albums",
);

foreach ($result['songs'] as $key => $val) {
    $song_id = $val['song_id'];
    $album["songs"][] = $this->song($song_id);
}
$album = str_replace("http://", "https://", $album); 
//需要添加上一行
$this->set_cache($cache_key, $album);
return $album;
```

在collect()里要替换两个

```php
$collect = array(
    "collect_id" => $collect_id,
    "collect_title" => $result['collect_name'],
    "collect_author" => $result['user_name'],
    "collect_cover" => $result['logo'],
    "collect_type" => "collects",
    "collect_count" => $count
);
$collect = str_replace("http://", "https://", $collect);
//需要添加上一行
foreach ($result['songs'] as $key => $value) {
    $collect["songs"][] = str_replace("http://", "https://", array(
        "song_id" => $value["song_id"],
        "song_title" => $value["song_name"],
        "song_length" => $value["length"],
        "song_src" => $value["listen_file"],
        "song_author" => $value["singers"],
        "song_cover" => $value['album_logo']
    ));
//需要修改上面一段
}
```

netease_song()

```php
$result = array(
    "song_id" => $music_id,
    "song_title" => $music_name,
    "song_length" => ceil($duration / 1000),
    "song_author" => $artists,
    "song_src" => $mp3_url,
    "song_cover" => $cover
);
$result = str_replace("http://", "https://", $result); 
//需要添加上一行
$this->set_cache($cache_key, $result, 24);

return $result;
```

netease_album()

```php
foreach ($result as $k => $value) {
    $mp3_url = str_replace("http://m", "http://p", $value["mp3Url"]);
    $album["songs"][] = str_replace("http://", "https://", array(
        "song_id" => $value["id"],
        "song_title" => $value["name"],
        "song_length" => ceil($value['duration'] / 1000),
        "song_src" => $mp3_url,
        "song_author" => $album_author,
        "song_cover" => $cover
    ));
//需要修改上面一段
}
```

netease_playlist()

```php
foreach ($result as $k => $value) {
    $mp3_url = str_replace("http://m", "http://p", $value["mp3Url"]);
    $artists = array();
    foreach ($value["artists"] as $artist) {
        $artists[] = $artist["name"];
    }

    $artists = implode(",", $artists);

    $collect["songs"][] = str_replace("http://", "https://", array(
        "song_id" => $value["id"],
        "song_title" => $value["name"],
        "song_length" => ceil($value['duration'] / 1000),
        "song_src" => $mp3_url,
        "song_author" => $artists,
        "song_cover" => $value['album']['picUrl']
    ));
//需要修改上面一段
}
```

netease_radio()

```php
foreach ($result as $k => $val) {
    $collect["songs"][] = str_replace("http://", "https://", array(
        "song_id" => $val['mainSong']['id'],
        "song_title" => $val['mainSong']['name'],
        "song_length" => (int)$val['mainSong']['duration'] / 1000,
        "song_src" => $val['mainSong']['mp3Url'],
        "song_author" => $val['radio']['name'],
        "song_cover" => $val['mainSong']['album']['picUrl']
    ));
//需要修改上面这一段
}
```

这样子之后就能完美全站https啦

![Final](https://milkice.me/wp-content/uploads/2017/03/hermit_4.png)

目前已知的bug是网易云音乐的电台的mp3的证书有问题，所以无法使用

但是其他功能就非常完美啦，棒棒哒(๑•̀ㅂ•́)و✧

**P.S.:如果修改之后还是不能播放，请尝试Hermit播放器 设置 清除缓存，然后浏览器端（以chrome为例），打开审查元素，到network，右键，clear cache后再试**

Example在Wordpress上

如果你懒得一个个去改呢 我这里也提供了我修改好的php文件：

[class.json.php](http://oq3s2uhe2.bkt.clouddn.com/class.json.php)

