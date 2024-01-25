/**
 * Created by jess on 16/4/14.
 */


'use strict';


let singleton = {};


/**
 * 执行某个hook配置,所对应的所有middleware
 * @param hookName {string} hook名字
 * @param httpObj {Http} http对象
 * @param data {any} 执行时,要传给middleware的数据
 */
singleton.exec = function( hookName, httpObj, data){

    //hook 的配置只挂载在common下
    const moduleName = 'common';

    let util = grape.util;
    let hookConf = grape.configManager.getModuleConfig(moduleName, 'hook') || {};
    let hookList = hookConf[hookName];
    if( hookList ){
        if( util.isArray(hookList) ){
            let p = Promise.resolve();
            hookList.forEach( function(middlewareName){
                p = p.then( () => {
                    return grape.middlewareManager.executeMiddleware( middlewareName, httpObj, data);
                });
            } );
            return p;
        }else{
            return Promise.reject( new Error(`[${hookName}] 对应的hook列表[${hookList}] 必须是数组!!`) );
        }
    }
    return Promise.resolve();
};



module.exports = singleton;