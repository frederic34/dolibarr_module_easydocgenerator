/**
 * Created by wangcheng on 16/4/14.
 */

'use strict';

let grape = global.grape;

class UrlRewrite extends grape.MiddlewareBase{

    execute(){

        let http = this.http;
        let pos = http.path.indexOf(grape.path.APP_URL_PREFIX);
//console.log('UrlRewrite execute start ', http.path, grape.path.APP_URL_PREFIX);
        if(pos === 0 && grape.path.APP_URL_PREFIX !== '/' && grape.path.APP_URL_PREFIX){
            http.path = http.path.substr(grape.path.APP_URL_PREFIX.length);
//console.log(http.path);
        }
        Promise.resolve();
    }

}


module.exports = UrlRewrite;