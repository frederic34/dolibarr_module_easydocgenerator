/**
 * Created by wangcheng on 16/4/14.
 */

'use strict';

let grape = global.grape,
    grapeData = global.grapeData,
    util = grape.util;

class UrlParser extends grape.MiddlewareBase{

    /**
     * 分析步骤 :
     *
     *    获取是否有配置有rewrite配置
     *      case1) 有
     *          parseRewrite, 遍历router,是否match
     *              case1) match
     *                  利用rewrite匹配替换后的
     *              case2) not match
     *
     *      case2) 没有
     *          parseUrl()
     *              getModule
     *              getController
     *              getAction
     *              getQuery
     *              设置到http对象中
     *  Todo : 提供默认module controller action配置
     *  Todo : url错误, module Controller等不存在的异常情况处理
     */
    execute(options){
        let http = this.http,
            req = http.req;
        if(!req.path){
            //this.http.module = configManager.getConf();
            return ;
        }
        let routers = this.getRouter();
        let rewritePath = req.path;
        if(!util.isEmpty(routers)){
            rewritePath = this.parseRouter(routers);
            http.path = rewritePath;
        }

        return this.parseUrlPath();
    }

    /**
     * 使用path, 使用正则match
     */
    parseRouter(routers){
        let http = this.http,
            path = http.path;
        for(let module in routers){
            let rules = routers[module],
                length = rules.length;
            for(let i = 0; i < length; i++){
                let rule = rules[i],
                    matchRule = rule.match,
                    matchRewrite = rule.rewrite;
                if(util.isRegExp(matchRule)){
                    let match = path.match(matchRule);
                    if(match){
                        path = matchRewrite.replace(/\$(\d+)/g, (all, token1) => {
                            return match[token1];
                        });
                    }
                }else if(util.isString(matchRule)){
                    if(path.indexOf(matchRule) == 0){
                        path = matchRewrite;
                    }
                }
            }
        }
        let pos = path.indexOf('?');
        if(pos > 0){
            let querys = {},
                queryStr = path.substr(pos + 1),
                queryTokens = queryStr.split('&');
            for(let i = 0; i < queryTokens.length; i++){
                let queryParams = queryTokens[i];
                let parasTokens = queryParams.split('=');
                querys[parasTokens[0]] = parasTokens[1];
            }
            path = path.substr(0, pos);
            http.query = util.extend(http.query, querys);
        }
        return path;
    }

    /**
     * 1. 获取所有的router
     * 2.
     */
    parseUrlPath(){
        this._stripSlash();

        let http = this.http;

        let module = this.getModule();
        http.module = module;
        let controller = this.getController();
        http.controller = controller;
        let action = this.getAction();
        http.action = action;
        let querys = this.getQuery();
        http.query = util.extend(http.query, querys);

        if( ! module || ! controller || ! action ){
            grape.log.warn(`[url_parser]找不到URL[${http.originalUrl}]对应的module[${module}]或controller[${controller}]或action[${action}]`);
        }

        return Promise.resolve();
    }


    getModule(){
        let http = this.http;
        let urlPath = http.path;
        let pos = urlPath.indexOf('/');
        let module = pos === -1 ? urlPath : urlPath.substr(0, pos);

        if( module === 'common' || grapeData.modules.indexOf(module) < 0 ){
            //如果module不在合法的module列表中, 也要返回404
            module = '';
        }

        if(util.isEmpty(http.module)){
            http.path = http.path.substr(module.length + 1);
            return module;
        }
        return http.module;
    }

    getController(){
        let http = this.http,
            urlPath = http.path;
        if(urlPath.length < 1 ){
            return '';
        }
        let moduleControllers = grapeData.controller;
        if(http.module){
            let controllers = moduleControllers[http.module];
            for(let controllerKey in controllers){
                if(urlPath == controllerKey || urlPath.indexOf(controllerKey + '/') == 0){
                    http.path = http.path.substr(controllerKey.length + 1);
                    return controllerKey;
                }
            }
        }
        return '';
    }

    getAction(){
        let http = this.http,
            urlPath = http.path;
        let action = '';
        if(urlPath.length < 1){
            action = '';
        }
        let pos = urlPath.indexOf('/');
        if(pos === -1){
            http.path = '';
            action = urlPath;
        }else{
            action = urlPath.substr(0, pos);
            http.path = urlPath.substr(pos + 1);
        }
        return action;
    }

    getQuery(){
        let http = this.http,
            urlPath = http.path;
        let params = this._getQuery(urlPath);
        http.path = '';
        return params;
    }

    _getQuery(path){
        let params = {},
            urlTokens = path.split('/');
        if(!util.isEmpty(path)){
            for(let i=0; i < Math.ceil(urlTokens.length / 2); i++){
                params[urlTokens[i * 2]] = decodeURIComponent(urlTokens[i * 2 + 1] || '');
            }
        }
        return params;
    }

    _stripSlash(){
        let http = this.http,
            urlPath = http.path;
        if(urlPath.startsWith('/')){
            http.path = urlPath.substr(1);
        }
    }
}

module.exports = UrlParser;