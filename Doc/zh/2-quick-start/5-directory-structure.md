# 目录结构

FastWS-PHP
  -- Doc
     -- zh
        README.md
  -- FastWS
     -- Api
     -- Core
        -- Event
        -- Protocol
        -- Transfer
     config.ini
     index.php
  -- Test
  
Doc为Markdown格式的文档.
   zh目录是中文文档
      README.md是文档目录
      
FastWS是FastWS的代码目录.
   Api是暴露给外部用户使用的接口类文件所在目录. 所有文件均是一个类, 用户在自己的代码中继承该类即可.
   Core是FastWS的核心代码. 如果不是有把握, 我们不建议修改其中的任何一个文件.
   config.ini是配置文件, 采用php.ini相同的语法规则.
   index.php是FastWS的入口文件. 用户自行编写的业务逻辑代码中必须引入index.php
   
Test是测试用例.