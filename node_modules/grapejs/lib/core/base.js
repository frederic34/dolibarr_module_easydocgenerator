/**
 * 所有类的基类
 * Created by jess on 16/4/13.
 */


'use strict';

class GrapeBase {

    constructor(...args){
        this.init(...args);
    }

    init(){
        //应该在 子类 中覆盖, 在构造函数中,会自动调用
    }

    getConfig(module, confName){
        return grape.configManager.getModuleConfig(module, confName);
    }

    getRouter(){
        return global.grapeData.router;
    }

    execHook(hookName, httpObj, args){
        return grape.hookManager.exec( hookName, httpObj, args);
    }

    getControllerClass(module, controllerPath){
        return grape.controllerManager.getControllerClass(module, controllerPath);
    }

    fatal(){
        this._doLog('fatal', [].slice.call(arguments) );
    }

    error(){
        this._doLog('error', [].slice.call(arguments) );
    }

    warn(){
        this._doLog('warn', [].slice.call(arguments) );
    }

    info(){
        this._doLog('info', [].slice.call(arguments) );
    }

    debug(){
        this._doLog('debug', [].slice.call(arguments) );
    }

    trace(){
        this._doLog('trace', [].slice.call(arguments) );
    }

    _doLog(logLevel, args){
        if( logLevel && args && grape.log && typeof grape.log[logLevel] === 'function' ){
            grape.log[logLevel].apply( grape.log, args);
        }else{
            //出问题了, 只能用 console.log 记录下
            grape.console.error(`[${logLevel}][${args}]对应的日志写入方法不支持, 或参数错误!!`);
        }
    }
}


module.exports = GrapeBase;