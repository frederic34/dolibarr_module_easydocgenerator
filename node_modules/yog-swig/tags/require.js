var exports = module.exports;

/**
 * trigger the load of FIS, it means add a js/css file to the page.
 *
 * @alias require
 *
 * @example
 * // if `namespace` = `user`
 * // load mod.js
 * {%require "user:static/mod.js"%}
 *
 * @param {string|var} id  the resource `id` of the FIS system.
 */
exports.compile = function(compiler, args, content, parents, options, blockName) {
    var code = '_ctx._yog.load(' + args[0] + ', ' + args[1] + ');'
    return code;
};

exports.parse = function(str, line, parser, types) {
    parser.on(types.STRING, function (token) {
        this.out.unshift(token.match);
    });
    parser.on(types.VAR, function (token) {
        if (token.match === 'external') {
            this.out.push(true);
        }else {
            this.out.unshift('_ctx.' + token.match);
        }
    });
    return true;
};

exports.ends = false;
