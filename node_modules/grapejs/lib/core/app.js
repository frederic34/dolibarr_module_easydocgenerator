/**
 * Created by wangcheng on 16/4/14.
 */


'use strict';

const cluster = require('cluster');
const express = require('express');
const cookieParser = require('cookie-parser');
const bodyParser = require('body-parser');
const session = require('express-session');

const Base = require('./base.js');

let grape = global.grape;

class App extends Base{

    init(http){
        this.http = http;
    }

    execHook(hookName, args){
        return super.execHook( hookName, this.http, args);
    }

    async exec(){

        await this.execHook('request_start');
        await this.execHook('request_parse');

        await this.requestCheck();

        await this.execHook('policy_start');
        await this.execPolicy();
        await this.execHook('policy_end');
        await this.execHook('controller_start');
        await this.execController();
        await this.execHook('controller_end');

    }

    //检查请求是否OK
    requestCheck(){
        let http = this.http;

        if(  http.module &&  http.controller && http.action ){
            return Promise.resolve();
        }

        http.sendStatus(404);

        return grape.prevent();

    }

    execPolicy(){
        let http = this.http;

        let module = http.module;
        let controllerKey = http.controller;
        let action = http.action;
        
        return grape.policyManager.executePolicyOfAction(module, controllerKey, action, http);

    }

    execController(){
        //this.http.res.end('hello, grape');

        let http = this.http;

        let module = http.module;
        let controllerKey = http.controller;
        let action = http.action;

        let controllerClass = this.getControllerClass(module, controllerKey);

        let method = action + 'Action';

        if( controllerClass ){

            let obj = new controllerClass( this.http );
            if( typeof obj[method] === 'function' ){
                return obj[method]();
            }
        }

        grape.log.info(`[execController]找不到module[${module}]controller[${controllerKey}]action[${action}]`);

        //返回一个 404 错误
        http.e404();
        return grape.prevent();
    }

    static run(){

        const app = express();
        const serverConf = grape.configManager.getConfig('server');
        const port = serverConf.port;

        //禁止输出 x-powered-by
        app.disable('x-powered-by');
        app.disable('etag');

        if( serverConf['x-powered-by'] ){
            app.use(function(req, res, next ){
                res.set('x-powered-by', serverConf['x-powered-by']);
                next();
            });
        }

        grape.viewManager.setViewEngine(app);

        let staticPrefix = grape.path.APP_STATIC_URL_PREFIX + '/static';
        app.use(staticPrefix, express.static(grape.path.APP_STATIC_PATH));

        app.use( cookieParser() );

        let limit = serverConf.limit || '100kb';

        app.use(bodyParser.json({ limit : limit })); // for parsing application/json
        app.use(bodyParser.urlencoded({ extended: true, limit : limit })); // for parsing application/x-www-form-urlencoded

        //配置session
        const sessionConf = grape.configManager.getConfig('session');
        if( sessionConf && ! grape.util.isEmpty( sessionConf ) && sessionConf.store ){
            //应用打开了session
            let conf = Object.assign({
                name : 'grapesid',
                proxy : undefined,
                resave : false,
                rolling : false,
                saveUninitialized : false,
                secret : '',
                unset : 'keep',
                cookie : {
                    secure : false,
                    maxAge : null
                }
            }, sessionConf );

            app.use( session( conf ) );
        }


        function requestReceive(req, res, next){

            const Http = grape.get('http');

            let http = new Http(req, res);
            let obj = new App( http );
            obj.exec().catch( (err) => {

                if( grape.isPrevent(err) ){
                    grape.console.log(`receive prevent error`);
                    return;
                }
                if( ! grape.util.isDev() ){
                    //线上环境,记录到日志
                    grape.log.error(err);
                }else{
                    //开发环境, 直接打印输出 执行出错
                    grape.console.log( err.stack );
                    // grape.console.log(`出错: ${err.message}`);
                }

                http.e500( err );

            });
        }
        app.use( grape.path.APP_URL_PREFIX, requestReceive);

        app.use( function(err, req, res, next){

            var Http = grape.get('http');
            var http = new Http(req, res);

            grape.log.error(err);

            http.e500(err);
        } );

        let server = app.listen(port);


        process.on('uncaughtException', function uncaughtException(err){

            grape.log.fatal(err);

            var timer = setTimeout(function(){
                process.exit(1);
            }, 30000);

            timer.unref();

            server.close();

            if( cluster.worker ){
                //console.info('server is worker');
                cluster.worker.disconnect();
            }
            //process.exit(1);
        } );
        
        
        grape.console.log(`当前运行环境 NODE_ENV=${ grape.util.getNodeEnv() }`);

        return server;

    }
}


module.exports = App;