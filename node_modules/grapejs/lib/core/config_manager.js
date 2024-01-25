/**
 * config模块,负责load配置文件, 提供获取接口
 * Created by jess on 16/4/13.
 */


'use strict';


const fs = require('fs');
const path = require('path');

const sep = path.sep;


let config = {};


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
        if (stat.isFile() && /\.js$/.test(file)) {
            let obj = path.parse(filePath);
            let fileName = obj.name;
            let data = require( filePath );
            out[ fileName ] = data;
        }
    } );

    return out;
}

//加载框架的配置, 以及APP下各个module的配置
config.load = function(){

    const util = grape.util;

    let grapeData = global.grapeData;

    let globalConfig = grapeData.config;

    const modules = grapeData.modules;

    //当前执行环境
    const env = util.getNodeEnv();

    const frameDefaultPath = grape.path.GRAPE_CONFIG_PATH;
    const APP_PATH = grape.path.APP_PATH;

    //加载框架下的普通配置
    let frameNormalConfig = loadDirFile(frameDefaultPath);
    //加载框架下, 当前执行NODE_ENV对应的JS
    let frameEnvConfig = grape.tryRequire(`${frameDefaultPath}${sep}env${sep}${env}.js`) || {};
    //合并框架级别的配置信息
    let frameFinalConfig = Object.assign( {}, frameNormalConfig, frameEnvConfig);

    //加载APP下各个module的配置
    const appCommonPath = util.getAppModulePath(APP_PATH, 'common');

    //先尝试加载APP下的common模块
    let appCommonConfig = loadDirFile(`${appCommonPath}${sep}config`);
    let appCommonEnvConfig = grape.tryRequire(`${appCommonPath}${sep}config${sep}env${sep}${env}.js`) || {};
    let appCommonFinalConfig = Object.assign({}, frameFinalConfig, appCommonConfig, appCommonEnvConfig);


    //加载APP的其他模块config
    modules.forEach( function(moduleName){
        if( moduleName === 'common' ){
            return;
        }
        let modulePath = util.getAppModulePath(APP_PATH, moduleName);
        let moduleConfig = loadDirFile(`${modulePath}${sep}config`);
        let moduleEnvConfig = grape.tryRequire(`${modulePath}${sep}config${sep}env${sep}${env}.js`) || {};

        globalConfig[moduleName] = Object.assign({}, appCommonFinalConfig, moduleConfig, moduleEnvConfig);
    } );


    globalConfig.grapeDefault = frameNormalConfig;
    globalConfig.grapeEnv = frameEnvConfig;
    globalConfig.grapeFinal = frameFinalConfig;

    //存储APP 的common级别的config, hook等只能在common中配置
    globalConfig.common = appCommonFinalConfig;
};

/**
 * 获取某个模块下的某个配置项
 * @param module {string} 模块名
 * @param name {string} 配置项名
 */
config.getModuleConfig = function(module, name){
    if( arguments.length === 1 ){
        name = module;
        //如果只传了一个参数, 则module默认为 common
        module = global.grapeData.defaultConfigModule;
    }
    if( module ){
        let moduleConfig = grapeData.config[module] || {};
        if( name ){
            return moduleConfig[name] || {};
        }
        return moduleConfig;
    }

    return {};
};


config.getConfig = config.getModuleConfig;



module.exports = config;