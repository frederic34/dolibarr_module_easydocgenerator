var path = require('path');
var _ = require('./util.js');

// 此事件在 pagelet 渲染前触发。
// 主要为了收集 js/css, 后面等 pagelet 渲染完后再把收集到的添加到 pagelet 中。
function beforePageletRender(pagelet, locals) {
    var layer = locals._yog;
    var fork, subpagelets, origin;

    if (!layer) return;

    // layer 在 pagelet 里面收集的 js 不能与原来的合并。
    // 所以需要 fork 一份出来。
    fork = locals._yog = layer.fork();
    fork.isPagelet = true;

    subpagelets = [];
    origin = fork.addPagelet;

    // pagelet 中套 pagelet，在父 pagelet 渲染完后再开始子 pagelet.

    // 缓存起来。
    fork.addPagelet = function () {
        subpagelets.push(arguments);
    };

    // 等待父 pagelet 渲染完毕。
    pagelet.once('after', function () {
        subpagelets.forEach(function (args) {
            args[0].parentId = pagelet.id;
            origin.apply(fork, args);
        });

        fork.addPagelet = origin;
        fork = locals = origin = subpagelets = pagelet = null;
    });

}

// 将 layer 收集的 js/css 添加到 pagelet 中。
function afterPageletRender(pagelet, locals) {
    var layer = locals._yog;

    if (!layer) return;

    locals._yog = layer.parent;

    var scripts = layer.getScripts();
    var styles = layer.getStyles();
    var css = layer.getCss();
    var js = layer.getJs();

    if (layer.getResourceMap()) {
        pagelet.addScript('require.resourceMap(' +
            JSON.stringify(layer.getResourceMap()) + ');');
    }

    pagelet.addStyles(styles);
    pagelet.addScripts(scripts);

    css && pagelet.addCss(css);
    js && pagelet.addJs(js);
}

function hasEventLinstener(emiter, type, fn) {
    var list = emiter._events[type];

    if (list && (list === fn || (list.indexOf && ~list.indexOf(fn)))) {
        return true;
    }

    return false;
}

var defaultOptions = {
    tpl: {
        css: '<% if (this.css) { %>' +
            '<% this.css.forEach(function(uri) { %>' +
            '<link rel="stylesheet" href="<%= uri %>" />' +
            '<% }); %>' +
            '<% } %>' +

            '<% if (this.embedCss) { %>' +
            '<style type="text/css"><%= this.embedCss %></style>' +
            '<% } %>',


        js: '<% if (this.framework) { %>' +
            '<script type="text/javascript" src="<%= this.framework %>"></script>' +
            '<% } %>' +

            '<% if (this.sourceMap) { %>' +
            '<script type="text/javascript">require.resourceMap(<%= this.sourceMap %>);</script>' +
            '<% } %>' +

            '<% if (this.js) { %>' +
            '<% this.js.forEach(function(uri) { %>' +
            '<script type="text/javascript" src="<%= uri %>"></script>' +
            '<% }); %>' +
            '<% } %>' +

            '<% if (this.embedJs) { %>' +
            '<script type="text/javascript"><%= this.embedJs %></script>' +
            '<% } %>'
    },
    onResourceOutput: function (res) {
        return {
            url: res['uri']
        };
    }
};

var createHanlder = module.exports = function (res, options) {
    options = _.mixin(_.mixin({}, defaultOptions), options);

    // 静态资源 api
    var fis = res.fis;

    // bigpipe api
    var bigpipe = res.bigpipe;

    // 模板目录
    var views = options.views;

    var loaded = [];

    // include all async files
    var asyncs = [];

    // include all sync files
    var syncs = [];

    var framework;

    // collect all inner script
    var scripts = [];

    // collect all inner style
    var styles = [];

    var depScaned = {};

    var addedAsync = {};

    var addedSync = {};

    var jsList = [];

    var cssList = [];

    // var timeused = 0;

    var changed = false;

    var usedPkg = {};
    var usedSync = {};

    /**
     * 获取同步资源依赖
     * @param file
     * @param added 已经处理过的同步资源
     * @returns {Array}
     */
    function getDepList(file, added) {
        var depList = [];
        added = added || {};
        file.deps && file.deps.forEach(function (depId) {
            if (added[depId]) {
                return false;
            }
            added[depId] = true;
            var dep = fis.getInfo(depId, true);
            if (!dep || (dep.type !== 'js' && dep.type !== 'css')) {
                return;
            }
            depList = depList.concat(getDepList(dep, added));
            depList.push(dep);
        });
        return depList;
    }

    /**
     * 获得指定文件的异步资源依赖
     * @param file
     * @param added 已经处理过的异步资源
     * @param depScaned 已经处理过的同步资源
     * @returns {Array}
     */
    function getAsyncList(file, added, depScaned) {
        var asyncList = [];
        added = added || {};
        depScaned = depScaned || {};
        //对同步依赖进行异步依赖检查
        file.deps && file.deps.forEach(function (depId) {
            if (depScaned[depId]) {
                return false;
            }
            depScaned[depId] = true;
            var dep = fis.getInfo(depId, true);
            if (!dep || (dep.type !== 'js' && dep.type !== 'css')) {
                return;
            }
            asyncList = asyncList.concat(getAsyncList(dep, added, depScaned));
        });
        file.extras && file.extras.async && file.extras.async.forEach(function (asyncId) {
            if (added[asyncId]) {
                return false;
            }
            added[asyncId] = true;
            var async = fis.getInfo(asyncId, true);
            if (!async || (async.type !== 'js' && async.type !== 'css')) {
                return;
            }
            asyncList = asyncList.concat(getAsyncList(async, added, depScaned));
            //异步资源依赖需要递归添加所有同步依赖
            asyncList = asyncList.concat(getDepList(async, added));
            asyncList.push(async);
        });
        return asyncList;
    }

    function load(id, external) {
        if (external) {
            var split = id.split('.');
            var ext = split.pop();
            if (ext) {
                syncs.push({
                    uri: id,
                    id: id,
                    type: ext.indexOf('css') === 0 ? 'css' : 'js'
                });
            }
        } else {
            var file = fis.getInfo(id, true);
            if (file) {
                changed = true;
                var depList = getDepList(file, addedSync);
                var asyncList = getAsyncList(file, addedAsync, depScaned);
                syncs = syncs.concat(depList);
                if (file.type === 'js' || file.type === 'css') {
                    syncs.push(file);
                }
                asyncs = asyncs.concat(asyncList);
            }
        }
    }

    if (bigpipe && !hasEventLinstener(bigpipe, 'pagelet:render:before',
            beforePageletRender)) {

        bigpipe
            .on('pagelet:render:before', beforePageletRender)
            .on('pagelet:render:after', afterPageletRender);
    }

    return {

        CSS_HOOK: '<!--FIS_CSS_HOOK-->',
        JS_HOOK: '<!--FIS_JS_HOOK-->',
        BIGPIPE_HOOK: bigpipe ? '<!--FIS_BIGPIPE_HOOK-->' : '',

        /**
         * 添加内嵌 js
         * @param script  the code between <script> and </script>.
         */
        addScript: function (script) {
            scripts.push(script);
        },

        getScripts: function () {
            return scripts;
        },

        /**
         * 添加 js
         * @param {[type]} url [description]
         */
        addJs: function (url) {
            changed = true;
            var info = fis && fis.getInfo(url, true);

            if (info) {
                this.load(url);
            }
            else {
                ~jsList.indexOf(url) || jsList.push(url);
            }
        },

        /**
         * 获取 js
         * @return {[type]} [description]
         */
        getJs: function () {
            this.preparePageResource();
            return jsList;
        },

        /**
         * 添加内联样式
         * @param style  the code between <style> and </style>
         */
        addStyle: function (style) {
            styles.push(style);
        },

        getStyles: function () {
            return styles;
        },

        /**
         * 添加样式
         * @param {[type]} url [description]
         */
        addCss: function (url) {
            changed = true;
            var info = fis && fis.getInfo(url, true);

            if (info) {
                this.load(url);
            }
            else {
                ~cssList.indexOf(url) || cssList.push(url);
            }
        },

        /**
         * 获取 css
         * @return {[type]} [description]
         */
        getCss: function () {
            this.preparePageResource();
            return cssList;
        },

        /**
         * 设置 framework js.
         * @param {[type]} js [description]
         */
        setFramework: function (js) {
            framework = fis ? fis.resolve(js) : js;
        },

        getFramework: function () {
            return framework;
        },

        load: function () {
            fis && load.apply(this, arguments);
        },

        resolve: function (id) {
            var resolved = fis && fis.resolve(id);

            if (resolved) {
                return path.join(views, resolved);
            }

            return id;
        },

        getUrl: function (id) {
            var resolved = fis && fis.resolve(id);
            return resolved || id;
        },

        supportBigPipe: function () {
            return !!bigpipe;
        },


        addPagelet: function () {
            return bigpipe && bigpipe.addPagelet.apply(bigpipe, arguments);
        },

        fork: function () {
            var forked = createHanlder(res, options);
            forked.parent = this;
            return forked;
        },

        addResourceToList: function (res) {
            var me = this;
            if (usedSync[res.id]) {
                return;
            }
            usedSync[res.id] = true;
            if (res.deps) {
                res.deps.forEach(function (dep) {
                    var file = fis.getInfo(dep, true);
                    if (file) {
                        me.addResourceToList(file);
                    }
                });
            }
            if (res.pkg) {
                var pkg = fis.getPkgInfo(res.pkg);
                if (usedPkg[res.pkg] || !pkg) {
                    return true;
                }
                pkg.has.forEach(function (has) {
                    usedSync[has] = true;
                });
                usedPkg[res.pkg] = true;
                res = pkg;
                if (pkg.deps) {
                    pkg.deps.forEach(function (dep) {
                        var file = fis.getInfo(dep, true);
                        if (file) {
                            me.addResourceToList(file);
                        }
                    });
                }
            }
            if (res.type === 'js' && res.uri !== framework) {
                jsList.push(res.uri);
            }
            else if (res.type === 'css') {
                cssList.push(res.uri);
            }
        },

        preparePageResource: function () {
            //检查preparePageResource后是否还有资源修改，如果没有修改则无需优化
            if (!changed) {
                return;
            }
            changed = false;
            //生成同步资源引用列表
            var depList = syncs;
            asyncs = asyncs.filter(function (async, index) {
                //将样式表资源强制设定为同步加载，避免异步加载样式表
                if (async.type === 'css') {
                    depList.push(async);
                    return false;
                }
                return true;
            });

            depList.forEach(this.addResourceToList.bind(this));
            asyncs = asyncs.filter(function (async, index) {
                //剔除同步资源
                if (usedSync[async.id]) {
                    return false;
                }
                return true;
            });

        },

        getResourceMap: function () {
            var id, rMap, res, pkg;

            for (id in asyncs) {
                res = asyncs[id];
                id = res.id;

                if (res['type'] != 'js') {
                    continue;
                }

                rMap = rMap || {};
                rMap['res'] = rMap['res'] || {};
                rMap['pkg'] = rMap['pkg'] || {};

                rMap['res'][id] = options.onResourceOutput(res) || {
                    url: res['uri']
                };

                if (res['deps']) {
                    // 异步资源的deps中剔除非JS资源
                    var deps = res['deps'].filter(function (dep) {
                        var info = fis.getInfo(dep, true);
                        if (info && info.type === 'js') {
                            return true;
                        }
                    }) || [];

                    if (deps.length !== 0) {
                        rMap['res'][id].deps = deps;
                    }
                }


                if (res['pkg']) {
                    rMap['res'][id]['pkg'] = res['pkg'];
                }

                if (res['pkg'] && this.fis) {
                    pkg = fis.getPkgInfo(res['pkg']);
                    rMap['pkg'][res['pkg']] = {
                        'url': pkg['uri']
                    };
                }
            }
            return rMap;
        },

        getDepsInfo: function () {
            this.preparePageResource();
            var resourceMap = this.getResourceMap();
            var embedJs = this.getScripts();
            var jsDeps = jsList;
            var embedCss = this.getStyles();
            var cssDeps = cssList;

            return  {
                resourceMap: resourceMap,
                embedJs: embedJs,
                jsDeps: jsDeps,
                embedCss: embedCss,
                cssDeps: cssDeps,
                framework: framework
            }
        },

        filter: function (content) {
            content = this.filterJs(content);

            content = this.filterCss(content);

            return content;
        },

        filterJs: function (content) {

            this.preparePageResource();

            var resourceMap = this.getResourceMap();
            var scripts = this.getScripts();
            var jses = jsList;
            var data = {};

            var loadModjs = !!framework;

            if (loadModjs) {
                data.framework = framework;
                resourceMap && (data.sourceMap = JSON.stringify(resourceMap));
            }

            data.resolve = this.getUrl;

            jses && (data.js = jses);
            scripts.length && (data.embedJs = '!function() {' +
                scripts.join('}();\n!function() {') + '}();');


            return content.replace(this.JS_HOOK, _.tpl(options.tpl.js, data));
        },

        filterCss: function (content) {

            this.preparePageResource();

            var styles = this.getStyles();
            var csses = cssList;
            var data = {};

            csses && (data.css = csses);
            styles.length && (data.embedCss = styles.join('\n'));

            data.resolve = this.getUrl;

            return content.replace(this.CSS_HOOK, _.tpl(options.tpl.css, data));
        },

        destroy: function () {
            loaded = asyncs = syncs = scripts = styles = asyncToSync = null;
            this.fis = this.bigpipe = this.views = null;
            fis = bigpipe = views = null;
        },

        // references
        fis: fis,
        bigpipe: bigpipe,
        views: views
    };
};
