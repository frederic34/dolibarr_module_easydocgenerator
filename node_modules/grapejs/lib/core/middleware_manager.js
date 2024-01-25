/**
 * middleware模块,负责load框架和APP下所有的middleware文件, 提供获取接口
 * Created by jess on 16/4/13.
 */


'use strict';


const fs = require('fs');
const path = require('path');

const sep = path.sep;


let middleware = {};


//加载 path 目录下的所有.js文件, 忽略目录
function loadDirFile(dir){

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
}

//加载框架的配置, 以及APP下各个module的middleware
middleware.load = function(){

    const util = grape.util;

    let grapeData = global.grapeData;

    let globalConfig = grapeData.middleware;

    const modules = grapeData.modules;

    //当前执行环境
    const env = util.getNodeEnv();

    const frameDefaultPath = grape.path.GRAPE_MIDDLEWARE_PATH;
    const APP_PATH = grape.path.APP_PATH;

    //加载框架下的普通配置
    let frameNormalConfig = loadDirFile(frameDefaultPath);

    //加载APP下各个module的配置
    const appCommonPath = util.getAppModulePath(APP_PATH, 'common');

    //先尝试加载APP下的common模块
    let appCommonConfig = loadDirFile(`${appCommonPath}${sep}middleware`);
    appCommonConfig = util.extend({}, frameNormalConfig, appCommonConfig);


    //加载APP的其他模块config
    //modules.forEach( function(moduleName){
    //    if( moduleName === 'common' ){
    //        return;
    //    }
    //    let modulePath = util.getAppModulePath(APP_PATH, moduleName);
    //    let moduleConfig = loadDirFile(`${modulePath}${sep}middleware`);
    //
    //    globalConfig[moduleName] = util.extend({}, appCommonConfig, moduleConfig);
    //} );


    grapeData.middleware = appCommonConfig;
};

/**
 * 获取某个模块下的某个middleware项
 * @param module {string} 模块名
 * @param name {string} 配置项名
 */
middleware.getMiddleware = function( name){
    return grapeData.middleware[name];
};


middleware.executeMiddleware = function( name, httpObj, data){
    let middlewareClass = middleware.getMiddleware( name);
    if( middlewareClass ){
        let obj = new middlewareClass(httpObj);
        return obj.execute(data);
    }else{
        return Promise.reject( new Error(`middleware [${name}] 不存在!!`));
    }
};



module.exports = middleware;