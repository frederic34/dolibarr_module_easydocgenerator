
# `swig-filter` 目录

这个目录下, 放置系统自带的 `swig` 的 `filter`.

在应用的`common`模块下的 `swig-filter` 目录, 是应用开发者自定义的 `swig` 的 `filter` 目录, 系统启动时,会自动加载.

每个文件名, 作为 `filter` 名, 模块应该返回对应filter的 `function`