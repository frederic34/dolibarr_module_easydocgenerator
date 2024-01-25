/**
 * 负责加载和执行 policy
 * Created by jess on 16/4/20.
 */


'use strict';



const fs = require('fs');
const path = require('path');

const sep = path.sep;


let policyManager = {};

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




//加载框架的配置, 以及APP下各个common的policy
policyManager.load = function(){

    const util = grape.util;

    let grapeData = global.grapeData;

    let globalConfig = grapeData.policy;

    const modules = grapeData.modules;

    //当前执行环境
    const env = util.getNodeEnv();

    // const frameDefaultPath = grape.path.GRAPE_MIDDLEWARE_PATH;
    const APP_PATH = grape.path.APP_PATH;

    //加载框架下的普通配置
    let frameNormalConfig = {};

    //加载APP下各个module的配置
    const appCommonPath = util.getAppModulePath(APP_PATH, 'common');

    //先尝试加载APP下的common模块
    let appCommonConfig = loadDirFile(`${appCommonPath}${sep}policy`);
    appCommonConfig = util.extend({}, frameNormalConfig, appCommonConfig);

    globalConfig.common = appCommonConfig;


    // 加载APP的其他模块config
    modules.forEach( function(moduleName){
       if( moduleName === 'common' ){
           return;
       }
       let modulePath = util.getAppModulePath(APP_PATH, moduleName);
       let moduleConfig = loadDirFile(`${modulePath}${sep}policy`);

       globalConfig[moduleName] = util.extend({}, appCommonConfig, moduleConfig);
    } );


    grapeData.policy = globalConfig;
};

/**
 * 获取某个模块下某个action对应的某个policy项
 * @param moduleName {string} 模块名
 * @param controllerName {string} controller的key
 * @param actionName {string} action名字
 * @return {Array} 配置文件中, 定义好的要执行的 policy 数组
 */
policyManager.getActionPolicyConfig = function( moduleName, controllerName, actionName){
    let out = [];
    let moduleConf = grape.configManager.getModuleConfig(moduleName, 'policy');
    if( moduleConf ){
        out = moduleConf['*'] || out;
        let controllerConf = moduleConf[controllerName];
        if( controllerConf ){
            out = controllerConf['*'] || out;
            let actionConf = controllerConf[actionName];
            if( actionConf ){
                out = actionConf;
            }
        }
    }

    return out;
};


/**
 * 获取某个module下的某个policy定义
 * @param moduleName {string} 模块名
 * @param policyName {string} policy名
 */
policyManager.getPolicy = function(moduleName, policyName){
    let moduleConf = grapeData.policy[moduleName];
    return moduleConf[policyName];
};

/**
 * 执行某个module下,某个controller的action对应的policy
 * @param moduleName {string} 模块名
 * @param controllerName {string} controller的路径
 * @param actionName {string}  action名
 * @param httpObj {Http} http对象
 * @returns {Promise}
 */
policyManager.executePolicyOfAction = function( moduleName, controllerName, actionName, httpObj){
    let policyArray = policyManager.getActionPolicyConfig(moduleName, controllerName, actionName);
    return policyArray.reduce( (promise, obj) => {

        let data = null;
        let policyName = obj;

        //如果policyName是JSON, 则包含了执行policy时,要传递的参数
        if( grape.util.isObject(obj) ){
            policyName = obj.name;
            data = obj.data;
        }else if( grape.util.isString(obj) ){
            policyName = obj;
        }else{
            return Promise.reject(`[${moduleName}][${controllerName}][${actionName}]配置的policy[${obj}]非法!!只能是JSON或string`);
        }
        
        let policyClass = policyManager.getPolicy(moduleName, policyName);
        return promise.then( () => {
            return ( new policyClass( httpObj ) ).execute( data );
        } );
    }, Promise.resolve() );

};



module.exports = policyManager;