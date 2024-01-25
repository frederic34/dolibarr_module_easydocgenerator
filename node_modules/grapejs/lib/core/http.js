/**
 * Created by wangcheng on 16/4/14.
 */

'use strict';

const GrapeBase = require('./base.js');

class Http extends GrapeBase{

    init(req, res){
        this.req = req;
        this.res = res;
        //当前请求对应的module
        this.module = '';
        this.controller = '';
        this.action = '';
        this.query = req.query;
        this.originalUrl = req.originalUrl;
        this.path = req.path;

        //该请求的response是否已返回
        this._isEnd = false;

        //赋值给模板的字段
        this.locals = {
            '$request' : req
        };

        //默认输出 html, utf-8
        res.set('Content-Type', 'text/html; charset=utf-8');
    }

    isEnd(){
        return this._isEnd;
    }

    assign(key, value){
        this.locals[key] = value;
        return this;
    }

    set( field, value ){
        this.res.set( field, value );
    }

    render(tplPath, data){
        if( ! this._isEnd ){
            this._isEnd = true;
            data = Object.assign({}, this.locals, data || {} );
            this.res.render( tplPath, data);
        }
    }

    end(){
        this._isEnd = true;
        this.res.end();
    }

    sendStatus(httpStatus = '500', data){
        if( ! this._isEnd ){
            
            httpStatus += '';

            this.res.status( httpStatus );
            //如果连module都没找到, 需要读取 common 下的配置
            let moduleConf = super.getConfig(this.module || 'common', 'page') || {};
            let tplPath = moduleConf[httpStatus];
            if( tplPath ){

                //允许用户配置两种情况: 1. 配置模板的路径 2. 配置函数, 框架会调用
                let type = typeof tplPath;
                if( type === 'string' ){
                    //字符串, 代表模板路径, 直接渲染
                    this.render( tplPath, data );    
                }else if( type === 'function' ){
                    //函数, 调用函数, 传入 http 对象
                    try{
                        tplPath( this, httpStatus, data);
                    }catch(e){
                        grape.log.error(e);
                        this.res.sendStatus(httpStatus);
                        this.end();
                    }
                }else{
                    this.res.sendStatus(httpStatus);
                    this.end();
                }
                
            }else{
                this.res.sendStatus(httpStatus);
                this.end();
            }

        }

    }

    redirect(status, path){
        if( arguments.length < 2 ){
            path = status;
            status = 302;
        }
        this.res.redirect( status, path );
    }

    json( data ){
        if( ! this._isEnd ){
            this._isEnd = true;
            this.res.json( data );
        }
    }
    
    e404( data ){
        this.sendStatus(404, data);
    }
    
    e500( err ){
        this.assign('grape_error', err);
        this.sendStatus(500);
    }
}

module.exports = Http;