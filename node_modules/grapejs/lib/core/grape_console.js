/**
 * 封装 console 对象,只在 development 模式,打印console信息
 * Created by jess on 16/4/18.
 */

/*eslint no-console: 0*/


'use strict';


let IS_DEV = null;

let singleton = {

    _canShowLog : function(){
        if( IS_DEV !== null ){
            return IS_DEV;
        }
        IS_DEV = ! grape.util.isProduction();
        return IS_DEV;
    },

    log : function(){
        if( ! this._canShowLog() ){
            return;
        }
        console.log.apply( console, arguments );
    },

    info : function(){
        if( ! this._canShowLog() ){
            return;
        }
        console.info.apply( console, arguments );
    },

    error : function(){
        if( ! this._canShowLog() ){
            return;
        }
        console.error.apply( console, arguments );
    }
};


module.exports = singleton;