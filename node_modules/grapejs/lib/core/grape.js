/**
 * 提供grape全局对象,注册grape上的各种loader等方法
 * Created by jess on 16/4/13.
 */


'use strict';


let grape = {};


//注册全局的 grape 对象
Object.defineProperty(global, 'grape', {
    value : grape,
    writable : false,
    enumerable : false
} );

const fs = require('fs');
const path = require('path');

const sep = path.sep;

const GrapeBase = require('./base.js');
const ControllerBase = require('./controller/base.js');
const MiddlewareBase = require('./middleware_base.js');
const PolicyBase = require('./policy_base.js');
const grapeUtil = require('./util.js');
const grapePath = require('./path.js');

require('./data.js');

const grapeConsole = require('./grape_console.js');
const configLoader = require('./config_manager.js');
const middlewareManager = require('./middleware_manager.js');
const hookManager = require('./hook_manager.js');
const policyManager = require('./policy_manager.js');
const controllerManager = require('./controller_manager');
const routerManager = require('./router_manager.js');
const grapeHttp = require('./http.js');
const viewManager = require('./view/view_manager.js');


const App = require('./app.js');

const logFactory = require('./grape_log.js');

const grapeBunyan = require('./bunyan/index.js');


//开发时,不直接使用 console, 使用 grape.console
grape.console = grapeConsole;

//base class
grape.Base = GrapeBase;

//controller base class
grape.ControllerBase = ControllerBase;

//middleware base class
grape.MiddlewareBase = MiddlewareBase;

grape.util = grapeUtil;

grape.path = grapePath;

grape.configManager = configLoader;

grape.middlewareManager = middlewareManager;

grape.hookManager = hookManager;

grape.policyManager = policyManager;

grape.controllerManager = controllerManager;

grape.routerManager = routerManager;

grape.viewManager = viewManager;

grape.Http = grapeHttp;

grape.App = App;

grape.logFactory = logFactory;

//全局唯一的日志对象
grape.log = null;

//暴露到全局上, 方便在应用层使用
grape.bunyanUtil = grapeBunyan;


//将一些对象暴露出去, 方便在具体应用中, 覆盖掉内部实现 //////////////

let data = {
    http : grapeHttp,
    controller_base : ControllerBase,
    policy_base : PolicyBase
};


grape.get = function( name ){
    return data[name];
};

grape.set = function( name, value ){
    data[name] = value;
};


/////////////////////////////////

/**
 * 尝试加载给定JS的绝对路径, 找不到则报错
 * @param absolutePath {String} 要加载的JS文件的绝对路径
 */
grape.tryRequire = function(absolutePath){

    try{
        if( ! path.isAbsolute(absolutePath) ){
            console.warn(`tryRequire 只能加载  绝对路径的 JS:  [${absolutePath}] 文件!!`);
            return null;
        }
        return require(absolutePath);
    }catch(e){
        console.warn(`require [${absolutePath}] 文件异常!!`);
        return null;
    }
};

/**
 * 加载 path 目录下的所有.js文件, 忽略目录
 * @param dir {string} 包含要加载JS的目录的绝对路径
 * @returns {object} 以JS文件名作为key, JS的export作为value的JSON
 */
grape.loadJSInDir = function (dir){

    if( ! grape.util.isDir(dir) ){
        return {};
    }

    let out = {};

    let files = fs.readdirSync(dir);
    files.forEach( function(file){
        let filePath = dir + sep + file;
        let stat = fs.statSync( filePath );
        if (stat.isFile() && /\.js$/.test(file) ) {
            let obj = path.parse(filePath);
            let fileName = obj.name.toLowerCase();
            let data = require( filePath );
            out[ fileName ] = data;
        }
    } );

    return out;
};


const TERMINATE_EXECUTE_FLAG = 'GRAPE_TERMINATE_REQUEST_EXECUTE_FLAG';

//主要用在 policy 中,如果已经返回了response,需要结束掉后续的所有执行动作, 返回特殊的promise, 便于错误处理识别
grape.prevent = function(){
    return Promise.reject( new Error( TERMINATE_EXECUTE_FLAG) );
};

/**
 * 判断错误对象, 是否是上面 terminateRequest 生成的
 * @param err
 * @returns {boolean}
 */
grape.isPrevent = function(err){
    return grape.util.isError(err) && err.message === TERMINATE_EXECUTE_FLAG;
};


module.exports = grape;



