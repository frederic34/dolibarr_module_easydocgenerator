var ignore = 'ignore',
    missing = 'missing',
    only = 'only',
    attrs = ["id", "mode", "group", "for", "model", "lazy", "cache"];

/**
 * Includes a template partial in place. The template is rendered within the current locals variable context.
 *
 * @alias widget
 *
 * @example
 * // food = 'burritos';
 * // drink = 'lemonade';
 * {% widget "./partial.html" %}
 * // => I like burritos and lemonade.
 *
 * @example
 * // my_obj = { food: 'tacos', drink: 'horchata' };
 * {% widget "./partial.html" id="pagelet_id" mode="async" with my_obj%}
 * // => I like tacos and horchata.
 *
 * @example
 * {% widget "/this/file/does/not/exist" ignore missing %}
 * // => (Nothing! empty string)
 *
 * @param {string|var}  file      The path, relative to the template root, to render into the current context.
 * @param {literal}     [with]    Literally, "with".
 * @param {object}      [context] Local variable key-value object context to provide to the included file.
 * @param {literal}     [only]    Restricts to <strong>only</strong> passing the <code>with context</code> as local variables–the included template will not be aware of any other local variables in the parent template. For best performance, usage of this option is recommended if possible.
 * @param {literal}     [ignore missing] Will output empty string if not found instead of throwing an error.
 */
exports.compile = function (compiler, args) {
    var file = args.shift(),
        onlyIdx = args.indexOf(only),
        onlyCtx = onlyIdx !== -1 ? args.splice(onlyIdx, 1) : false,
        parentFile = (args.pop() || '').replace(/\\/g, '\\\\'),
        ignore = args[args.length - 1] === missing ? (args.pop()) : false,
        w = args.filter(function (o) {
            return !o.k;
        }).join(','),
        w_args = {};
    var withData;
    args.forEach(function (w) {
        if (w.k && w.k !== 'data')  {
            w_args[w.k] = w.v;
        } else if (w.k === 'data') {
            withData = JSON.stringify(w.v).replace(/"<<VAR:(.*?)>>"/g, '((typeof _ctx.$1 !== "undefined" && typeof _ctx.$1 !== "null") ? _ctx.$1 : ((typeof $1 !== "undefined" && typeof $1 !== "null") ? $1 : ""))');
        }
    });
    if (!withData) {
        withData = w;
    }
    // 处理_VAR_:标记，将其转变为获取ctx变量
    var w_args_str = JSON.stringify(w_args).replace(/"<<VAR:(.*?)>>"/g, '((typeof _ctx.$1 !== "undefined" && typeof _ctx.$1 !== "null") ? _ctx.$1 : ((typeof $1 !== "undefined" && typeof $1 !== "null") ? $1 : ""))');
    return (ignore ? '  try {\n' : '') +
        '_output += _swig._w(_ctx._yog, ' + file + ',' + w_args_str + ', {' +
        'resolveFrom: "' + parentFile + '"' +
        '})(' +
        ((onlyCtx && withData) ? withData : (!withData ? '_ctx' : '_utils.extend({}, _ctx, ' + withData + ')')) +
        ');\n' +
        (ignore ? '} catch (e) {}\n' : '');
};

exports.parse = function (str, line, parser, types, stack, opts) {
    var file, w, k;
    var DATA_FLAG = false;
    var ASSIGNMENT_FLAG = false;
    var withData = {};
    var currentKey;

    parser.on(types.STRING, function (token) {

        if (!file) {
            file = token.match;
            this.out.push(file);
            return;
        }

        var out = {
            v: '',
            k: ''
        };

        if (~attrs.indexOf(k)) {
            out.v = token.match.replace(/^("|')?(.*)\1$/g, '$2');
            out.k = k;
            this.out.push(out);
            k = ''; // reset
            return;
        }

        if (DATA_FLAG && ASSIGNMENT_FLAG) {
            parseToken(token, this.prevToken, this.out, function (match) {
                return match.replace(/^("|')?(.*)\1$/g, '$2');
            });
            return false;
        }
    });

    parser.on(types.BOOL, function (token) {
        if (k && ~attrs.indexOf(k)) {
            var out = {
                v: '',
                k: ''
            };
            out.v = token.match === 'true';
            out.k = k;
            this.out.push(out);
            k = ''; // reset
            return false;
        }

        if (DATA_FLAG && ASSIGNMENT_FLAG) {
            parseToken(token, this.prevToken, this.out, function (match) {
                return match === 'true';
            });
            return false;
        }
    });

    parser.on(types.NUMBER, function (token) {
        if (k && ~attrs.indexOf(k)) {
            var out = {
                v: '',
                k: ''
            };
            out.v = token.match === 'true';
            out.k = k;
            this.out.push(out);
            k = ''; // reset
            return false;
        }

        if (DATA_FLAG && ASSIGNMENT_FLAG) {
            parseToken(token, this.prevToken, this.out, function (match) {
                return match;
            });
            return false;
        }
    });

    parser.on(types.CURLYOPEN, function (token) {
        if (w) {
            DATA_FLAG = true;
        }
    });


    parser.on(types.CURLYCLOSE, function (token) {
        if (w) {
            DATA_FLAG = false;
            this.out.push({
                k: 'data',
                v: withData
            });
        }
    });


    parser.on(types.COMMA, function (token) {
        // 在赋值状态下，不应该出现COMMA
        if (ASSIGNMENT_FLAG) {
            throw new Error('Unexpected token "' + token.match + '" on line ' + line + '.' + ' at ' + opts.filename + '.');
        }
        currentKey = null;
    });

    parser.on(types.ASSIGNMENT, function (token) {
        // with {}下，开启赋值状态
        if (w && currentKey) {
            ASSIGNMENT_FLAG = true;
        }
    });

    function parseToken(token, prevToken, out, parseValueFn) {
        if (ASSIGNMENT_FLAG && currentKey) {
            withData[currentKey] = parseValueFn(token.match);
            ASSIGNMENT_FLAG = false;
            return true;
        }
        return false;
    }

    parser.on(types.VAR, function (token) {
        if (!file) {
            k = '';
            file = token.match;
            return true;
        }

        if (DATA_FLAG) {
            if (!ASSIGNMENT_FLAG) {
                currentKey = token.match;
            } else {
                parseToken(token, this.prevToken, this.out, function (match) {
                    return '<<VAR:' + match + '>>';
                });
            }
            return false;
        }

        if (k && ~attrs.indexOf(k)) {
            var out = {
                v: '',
                k: ''
            };
            out.v = '<<VAR:' + token.match + '>>';
            out.k = k;
            this.out.push(out);
            k = '';
            return false;
        }

        if (~attrs.indexOf(token.match)) {
            k = token.match;
            return false;
        }

        if (!w && token.match === 'with') {
            w = true;
            return;
        }

        if (w && token.match === only && this.prevToken.match !== 'with') {
            this.out.push(token.match);
            return;
        }

        if (token.match === ignore) {
            return false;
        }

        if (token.match === missing) {
            if (this.prevToken.match !== ignore) {
                throw new Error('Unexpected token "' + missing + '" on line ' + line + ' at ' + opts.filename + '.');
            }
            this.out.push(token.match);
            return false;
        }

        if (this.prevToken.match === ignore) {
            throw new Error('Expected "' + missing + '" on line ' + line + ' but found "' + token.match + '"' + ' at ' + opts.filename + '.');
        }

        if (k) {
            throw new Error('Unexpected token "' + token.match + '" on line ' + line + ' at ' + opts.filename + '.');
        }

        return true;
    });

    parser.on('end', function () {
        this.out.push(opts.filename || null);
    });

    return true;
};

exports.ends = false;
