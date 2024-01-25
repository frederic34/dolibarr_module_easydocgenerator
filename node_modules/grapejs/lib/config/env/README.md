
# `config/env` 目录下

`config/env` 目录下,放各个不同 `environment` 环境下的配置, 会覆盖掉外面的默认配置

比如, `NODE_ENV=production` , 框架会加载 `production.js`; 类似的, 在 `development` 环境下, 加载 `development.js`.