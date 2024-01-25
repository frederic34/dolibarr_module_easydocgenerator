/**
 * 此插件为 yog-view 的 swig 版本显现。
 * 依赖 yog-view。
 */
var Readable = require('stream').Readable;
var util = require('util');
var Swig = require('swig').Swig;
var loader = require('./lib/loader.js');
var debuglog = require('debuglog')('yog-swig');
var LRU = require('lru-cache');
var tags = [
    'script',
    'style',
    'html',
    'body',
    'require',
    'uri',
    'widget',
    'head',
    'feature',
    'featureelse',
    'spage',
    'pagelet'
];

var swigInstance;

var EngineStream = function (swig, view, locals) {
    this.swig = swig;
    this.view = view;
    this.locals = locals;
    this.reading = false;
    Readable.call(this);
};

util.inherits(EngineStream, Readable);

EngineStream.prototype._read = function () {
    var self = this;
    // var state = self._readableState;
    if (this.reading) {
        return;
    }
    this.reading = true;
    debuglog('start render [%s]', this.view);
    this.swig.renderFile(this.view, this.locals, function (error, output) {
        if (error) {
            debuglog('render [%s] failed', self.view);
            return self.emit('error', error);
        }
        debuglog('render [%s] succ', self.view);
        self.push(output);
        self.push(null);
    });
};

/**
 * Opitions 说明
 * - `views` 模板根目录
 * - `loader` 模板加载器，默认自带，可选。
 *
 * 更多细节请查看 yog-view
 *
 */
var SwigWrap = module.exports = function SwigWrap(app, options) {
    options.renderCacheOptions = options.renderCacheOptions || {};
    var max = options.renderCacheOptions.max || 20 * 1024 * 1024;
    var pruneRate = options.renderCacheOptions.pruneRate || 1;
    var renderCaches = LRU({
        max: max,
        length: function (n, key) {
            return n.length;
        },
        maxAge: options.renderCacheOptions.maxAge || 1000 * 60 * 60
    });
    renderCaches.setCount = 0;
    if (swigInstance) {
        debuglog('use swig instance cache');
        this.swig = swigInstance;
        return;
    }

    debuglog('init swig instance');

    options = options || {};

    // 重写 loader, 让模板引擎，可以识别静态资源标示。如：example:static/lib/jquery.js
    options.loader = options.loader || loader(app, options.views, options.encoding);

    var swig = this.swig = swigInstance = new Swig(options);

    // 加载内置扩展
    tags.forEach(function (tag) {
        var t = require('./tags/' + tag);
        swig.setTag(tag, t.parse, t.compile, t.ends, t.blockLevel || false);
    });

    // 加载用户扩展
    options.tags && Object.keys(options.tags).forEach(function (name) {
        var t = options.tags[name];
        swig.setTag(name, t.parse, t.compile, t.ends, t.blockLevel || false);
    });

    options.filters && Object.keys(options.filters).forEach(function (name) {
        var t = options.filters[name];
        swig.setFilter(name, t);
    });

    swig.renderCache = options.renderCache || {
        get: renderCaches.get.bind(renderCaches),
        set: function (key, value) {
            // 设置过多缓存时，考虑清理老缓存请求次数
            if (renderCaches.setCount > pruneRate * max) {
                renderCaches.prune();
                renderCaches.setCount = 0;
                debuglog('prune widget cache');
            }
            renderCaches.setCount++;
            return renderCaches.set(key, value);
        },
        clean: function () {
            renderCaches.reset();
        }
    };
};

SwigWrap.prototype.cleanCache = function () {
    try {
        this.swig.invalidateCache();
        this.swig.renderCache.clean && this.swig.renderCache.clean();
    }
    catch (e) {}
};

SwigWrap.prototype.makeStream = function (view, locals) {
    debuglog('create [%s] render stream', view);
    return new EngineStream(this.swig, view, locals);
};


Swig.prototype._idToCompiled = function (layer, id, options) {
    var pathname = layer.resolve(id);
    layer.load(id);
    return this.compileFile(pathname, options);
};

// 扩展swig内置函数，用于提供bigpipe支持
Swig.prototype._w = Swig.prototype._widget = function (layer, subTemplate, attr, options) {
    var self = this;
    var id = typeof subTemplate === 'function' ?
        (options.filename || options.resolveFrom + '_' + attr.id) :
        subTemplate;
    var cacheKey = attr.cache ? id + '_' + attr.cache : null;

    function getCompiled(currentLayer) {
        if (cacheKey) {
            var cacheContent = self.renderCache.get(cacheKey);
            if (cacheContent) {
                debuglog('load render cache by [%s]', cacheKey);
                return function (locals) {
                    return cacheContent;
                };
            }
        }
        var fn = subTemplate;
        if (typeof fn !== 'function') {
            fn = self._idToCompiled(currentLayer, subTemplate, options);
        }
        var newFn = function (locals) {
            var contents = fn(locals);
            if (cacheKey) {
                debuglog('set render cache [%s]', cacheKey);
                self.renderCache.set(cacheKey, contents);
            }
            return contents;
        };
        newFn.tokens = fn.tokens;
        newFn.parent = fn.parent;
        newFn.blocks = fn.blocks;
        return newFn;
    }

    if (!layer.supportBigPipe() || !attr.mode || attr.mode === 'sync') {
        return getCompiled(layer);
    }

    return function (locals) {
        var container = attr.container || attr.for;
        var pageletOptions = {
            container: container,
            model: attr.model,
            id: attr.id,
            lazy: attr.lazy === 'true',
            mode: attr.mode,
            locals: locals,
            // view: pathname,
            // viewId: id,
            compiled: function (locals) {
                return getCompiled(locals._yog)(locals);
            }
        };

        if (layer.bigpipe.isSpiderMode) {
            var syncPagelet = new layer.bigpipe.Pagelet(pageletOptions);
            syncPagelet.start(layer.bigpipe.pageletData[attr.id], true);
            return container ? syncPagelet.html : '<div id="' + attr.id + '"> ' + syncPagelet.html + '</div>';
        }
        else {
            container = attr.container || attr.for;
            layer.addPagelet(pageletOptions);
            return container ? '' : '<div id="' + attr.id + '"></div>';
        }
    };
};
