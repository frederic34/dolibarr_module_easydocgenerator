/**
 * 应用启动时,读取到的所有配置信息/controller匹配等信息的全局存储
 * Created by jess on 16/4/13.
 */


'use strict';


let grapeData = {};


Object.defineProperty(global, 'grapeData', {
    value : grapeData,
    writable : false,
    enumerable : false
} );


//配置信息
grapeData.config = {};

//路由
grapeData.router = {};

//所有注册的controller
grapeData.controller = {};

//APP下所有注册的模块名
grapeData.modules = [];

//所有注册的middleware, 按照模块划分
grapeData.middleware = {};

//所有定义的policy
grapeData.policy = {};

//默认读取配置的module
grapeData.defaultConfigModule = 'common';

//存放所有的swig的filter配置
grapeData.swigFilter = {};


module.exports = grapeData;

