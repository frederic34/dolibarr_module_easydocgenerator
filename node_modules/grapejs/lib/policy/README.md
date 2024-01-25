
# `policy` 

在请求到达 `action` 之前, 可以配置需要先经过哪些 `policy`, 对请求进行过滤.

`policy` 可以设置当前用户, 校验请求参数, 判断用户权限等操作.

`policy` 的实现, 必须继承自 `grape.get('policy_base')`, 动态获取, 因为可能会在具体应用中, 提供自定义的policy基类

