/**
 * Created by wangcheng on 16/4/22.
 */

'use strict';

let _ = require('lodash');
let yogView = require('yog-view');
let yogBigPipe = require('yog-bigpipe');
let mapjson = require('./mapjson.js');


let viewManager = {};

viewManager.setViewEngine = function (app) {
    let path = global.grape.path;

    let viewConf  = {
        confDir: path.APP_MAP_PATH,
        viewsDir: path.APP_VIEW_PATH,
        bigpipe: false,
        bigpipeOpt: {
            skipAnalysis: true,
            isSpiderMode: function (req) {
                if (req.headers['user-agent'] && /bot|spider/.test(req.headers['user-agent'].toLowerCase())) {
                    return true;
                }
            },
        },
        tpl: {
            cache: 'memory',
            //包含系统默认的和用户自定义的swig filter, 在应用启动阶段已经load好
            filters : grapeData.swigFilter
        },
        engine: {
            tpl: 'yog-swig'
        }
    };


    if( grape.util.isDev() ){
        //开发模式, 关闭掉swig缓存
        viewConf.tpl.cache = false;
    }

    app.set('views', viewConf.viewsDir);

    app.fis = new mapjson.ResourceApi(viewConf.confDir);

    app.use( (req, res, next) => {
        res.fis = app.fis;
        next();
    });

    //初始化bigpipe
    if (viewConf.bigpipe) {
        app.use(yogBigPipe(viewConf.bigpipeOpt));
    }

    _.forIn(viewConf.engine, function (engine, name) {
        //设置view engine
        let viewEngine = new yogView(app, engine, viewConf[name] || {});
        // viewEngines.push(viewEngine);
        app.engine(name, viewEngine.renderFile.bind(viewEngine));
    });

};

module.exports = viewManager;


