介绍
============

本组件可用于图片缩放，通过URL参数，即可加载不同尺寸的图片。同时支持紫铜的@2x, @3x图片。

``组件仅支持Symfony2.7+``


## 安装
============

### Step 1. 将本组件 ```giko/image-bundle``` 和 ```sonata-project/media-bundle```  添加到 ``composer.json`` 文件:
```
        "require": {
            #...
            "sonata-project/media-bundle": "dev-master",
            "giko/image-bundle": "dev-master",
        }
```

### Step 2. 在应用内核代码中注册组件：
```php
          //app/AppKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new Giko\ImageBundle\GikoImageBundle(),
                  // ...
              );
          }
```

### Step 3. Sonata Media的相关配置步骤，请参见Sonata Media文档：

### Step 4. 增加Route
```
    giko_image:
        resource: "@GikoImageBundle/Resources/config/routing.yml"
        prefix:   /
```


-----------------------
如果，咳咳，我的代码对你有帮助，在Github上加个星，让我知道你看到了本代码。
<a href="https://me.alipay.com/giko"><img src="https://raw.github.com/gikoluo/SinaweiboBundle/master/doc/donate-with-alipay.png" alt="通过支付宝捐赠"></a>
-----------------------



