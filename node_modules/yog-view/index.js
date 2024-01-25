'use strict';

var layer = require('./lib/layer.js');
var combine = require('./lib/combine.js');
var _ = require('./lib/util.js');
var Transform = require('stream').Transform;

function yogViewEngine(app, engine, settings) {
    // 将res注入到locals中提供模板渲染时使用
    app.use(function (req, res, next) {
        res.locals.__res__ = res;
        next();
    });

    settings.views = app.get('views');
    var EngineClass = _.resolveEngine(engine);
    this.engine = new EngineClass(app, settings);
    this.settings = settings;
}

yogViewEngine.prototype.cleanCache = function () {
    this.engine.cleanCache && this.engine.cleanCache();
};

yogViewEngine.prototype.renderFile = function (filepath, locals, done) {
    var res = locals.__res__;
    var settings = this.settings;
    var me = this;
    // 此处只能使用文字匹配的手段判断传入的回调是否是express默认回调，否则会无法使用pipe进行bigpipe输出
    var hasCustomDone = done.toString().replace(/[\s;]/g, '') != 'function(err,str){if(err)returnreq.next(err)self.send(str)}';

    // 初始化 layer 层。
    // 提供 addScript, addStyle, resolve, addPagelet 各种接口。
    // 用来扩展模板层能力。
    var prototols = layer(res, settings);
    var preFetch = [];
    if (prototols.bigpipe && prototols.bigpipe.isQuicklingMode() === false) {
        // 准备页面数据
        preFetch.push(prototols.bigpipe.preparePageOnly().then(function (data) {
            // 页面数据应与locals合并
            if (data) {
                _.mixin(locals, data);
            }
        }));
    }
    if (prototols.bigpipe && prototols.bigpipe.isSpiderMode) {
        // 获取所有异步pagelet信息
        preFetch.push(prototols.bigpipe.prepareAllSources());
    }

    Promise.all(preFetch).then(render).catch(done);

    function render() {
        var sentData = false;
        var content = [];
        var renderStream = me.engine.makeStream(filepath, _.mixin(locals, {
                _yog: prototols
            }))
            // 合并 tpl 流 和 bigpipe 流。
            .pipe(combine(prototols, {
                customFilter: res.customFilter
            }))
            // 设置默认content-type
            .on('data', function () {
                sentData = true;
                if (!res.headersSent) {
                    // disable nginx proxy_buffering
                    res.setHeader('X-Accel-Buffering', 'no');
                    if (!res.get('Content-Type')) {
                        res.type('html');
                    }
                }
                setImmediate(function () {
                    res.flush && res.flush();
                });
            })
            // bigpipe异步回调异常
            .on('error', function (error) {
                // 属于 chunk error
                if (sentData) {
                    if (typeof settings.chunkErrorHandler === 'function') {
                        settings.chunkErrorHandler(error, res);
                    }
                    else {
                        res.write('<script>window.console && console.error("chunk error", "' + error.message.replace(
                            /"/g,
                            '\\\"') + '")</script>');
                    }
                    res.end();
                }
                else {
                    // 模板渲染前报错，传递至next
                    done(error);
                }
            })
            .on('data', function (data) {
                if (hasCustomDone) {
                    content.push(data);
                }
            })
            .on('end', function () {
                if (hasCustomDone) {
                    done && done(null, Buffer.concat(content).toString());
                }
            });
        if (!hasCustomDone) {
            renderStream.pipe(res);
        }
    }
};

module.exports = yogViewEngine;
