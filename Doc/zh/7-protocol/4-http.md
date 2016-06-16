# HTTP协议

HTTP协议是一个应用层协议, 模拟Nginx/Apache的作用, 使用HTTP协议的MeepoPS是一个WebServer。

解析HTTP的header和body, 提取所需数据, 填充到$_GET, $_POST, $_FILE, $_SERVER, $_SESSION, $_COOKIE, $_REQUEST等常用的超全局变量。

启动使用HTTP协议的MeepoPS, 开发应用时和普通WebServer的感觉99.99%一致。

### 与普通WebServer开发时的不同

唯三的不同就是SESSION功能, header()函数, setcookie()函数。

##### SESSION
因为PHP的Session处理, 在使用session_start()时会自动分配SESSION ID. 但是MeepoPS是常驻内存的PHP, 这就导致所有人访问时都只会用同一个SESSION ID。即使session_set_save_handler等自定义SESSION的函数也不可以。

因此, 只能在使用SESSION时, 各位同学不能再使用session_系列函数。

另外，除了Session Name外, 其余配置都使用php.ini中的"session.*"的配置项。

- 开启SESSION(类似session_start()): `\MeepoPS\Api\Http::sessionStart();`。
- 保存SESSION(自动执行的, 不需要手动, 类似session_write_close()): `\MeepoPS\Api\Http::sessionWrite();`。
- 获取SESSION ID(类似session_id()): `\MeepoPS\Api\Http::sessionId();`。
- 销毁SESSION(类似session_destroy()): `\MeepoPS\Api\Http::sessionDestroy();`。

如果您需要SESSION共享, 请根据需要自行修改MeepoPS/Library/Session.php

SESSION的GC在调用`\MeepoPS\Api\Http::sessionStart();`时自动执行, 触发机率和SESSION有效期请参考php.ini中的session.gc_probability, session.gc_divisor, session.gc_maxlifetime。

##### header()函数
MeepoPS的HTTP协议中, 不支持直接使用header()函数来设置头, 设置头信息时, 请使用`\MeepoPS\Api\Http::setHeader();`. 参数个数和含义与header()完全一致。

##### setcookie()函数
MeepoPS的HTTP协议中, 不支持直接使用setcookie()函数来设置Cookie, 设置Cookie信息时, 请使用```\MeepoPS\Api\Http::setCookie();```. 参数个数和含义与setcookie()完全一致。

### 使用方法
使用时调用Http的接口即可。
```
$telnet = new \MeepoPS\Api\Http('0.0.0.0', '19910');
```

### 特殊属性
无, 与[接口](../3-api)中所阐述的通用属性完全一致。

### 特殊方法
除[接口](../3-api)中所阐述的通用方法外, HTTP接口所独有的:

#### 设置HTTP头信息\MeepoPS\Api\Http::setHeader()
- 名称: \MeepoPS\Api\Http::setHeader();
- 参数: 参数个数和含义与header()完全一致。
- 返回: bool 
- 描述: MeepoPS的HTTP协议中, 不支持直接使用header()函数来设置头, 设置头信息时, 请使用`\MeepoPS\Api\Http::setHeader();`。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
\MeepoPS\Api\Http::setHeader('Location: index.php');
//或者
\MeepoPS\Api\Http::setHeader('HTTP/1.1 400 Bad Request');
```

#### 删除指定的HTTP头\MeepoPS\Api\Http::delHttpHeader($name)
- 名称: \MeepoPS\Api\Http::delHttpHeader();
- 参数: 参数$name是需要删除的头的名称。
- 返回: void 
- 描述: 删除指定的HTTP头。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
\MeepoPS\Api\Http::delHttpHeader('TEST');
```

#### 设置HTTP Cookie信息\MeepoPS\Api\Http::setCookie()
- 名称: \MeepoPS\Api\Http::setCookie();
- 参数: 参数个数和含义与setcookie()完全一致
- 返回: bool 
- 描述: MeepoPS的HTTP协议中, 不支持直接使用setcookie()函数来设置Cookie, 设置Cookie信息时, 请使用`\MeepoPS\Api\Http::setCookie();`。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
\MeepoPS\Api\Http::setCookie('USERNAME', 'meepops');
\MeepoPS\Api\Http::setCookie('SEX', 'male');
```

#### 开启SESSION \MeepoPS\Api\Http::sessionStart()
- 名称: \MeepoPS\Api\Http::sessionStart();
- 参数: 无。
- 返回: bool 
- 描述: 开启SESSION, 类似session_start()。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
//开启SESSION
\MeepoPS\Api\Http::sessionStart()
```

#### 保存SESSION \MeepoPS\Api\Http::sessionWrite()
- 名称: \MeepoPS\Api\Http::sessionWrite();
- 参数: 无。
- 返回: bool 
- 描述: 保存SESSION, 本方法自动执行, 通常不需要您手动, 类似session_write_close(), 默认在脚本执行结束时触发。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
//开启SESSION
\MeepoPS\Api\Http::sessionStart()
//立即保存SESSION
\MeepoPS\Api\Http::sessionWrite()
```

#### 获取SESSION ID \MeepoPS\Api\Http::sessionId()
- 名称: \MeepoPS\Api\Http::sessionId();
- 参数: 无。
- 返回: bool 
- 描述: 获取SESSION ID, 类似session_id()。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
//开启SESSION
\MeepoPS\Api\Http::sessionStart()
//获取SESSION ID
$sessionId = \MeepoPS\Api\Http::sessionId()
```

#### 销毁SESSION \MeepoPS\Api\Http::sessionDestroy()
- 名称: \MeepoPS\Api\Http::sessionDestroy();
- 参数: 无。
- 返回: bool 
- 描述: 销毁SESSION, 类似session_destroy()。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
//开启SESSION
\MeepoPS\Api\Http::sessionStart()
//销毁SESSION
\MeepoPS\Api\Http::sessionDestroy()
```
 
#### 设置HTTP错误页 \MeepoPS\Api\Http::setErrorPage()
- 名称: \MeepoPS\Api\Http::setErrorPage();
- 参数1: $httpCode。HTTP状态码.
- 参数2: $description。加载页面的文件路劲, 或者描述。
- 返回: bool 
- 描述: 设置HTTP错误页, 指定HTTP状态码, 当此HTTP状态码时, 加载指定的页面, 或者在MeepoPS的默认样式中显示描述。

###### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
//设置错误页
//404, 设置一个专门的页面来展示
$http->setErrorPage('404', __DIR__ . '/Test/Web/404.html');
//403, 使用默认样式(其实就是居中了一句话), 自定义错误描述
$http->setErrorPage('403', '您没有被授权访问!');
```