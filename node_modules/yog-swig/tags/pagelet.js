var ATTRS = ['id', 'mode', 'group', 'for', 'model', 'lazy', 'cache'];

exports.compile = function (compiler, args, content, parents, options, blockName) {
    var attr = {};
    for (var i = 0; i < args.length; i++) {
        attr[args[i].k] = args[i].v;
    }
    attr.mode = attr.mode || 'async';
    var attrStr = JSON.stringify(attr).replace(/"<<VAR:(.*?)>>"/g, '((typeof _ctx.$1 !== "undefined" && typeof _ctx.$1 !== "null") ? _ctx.$1 : ((typeof $1 !== "undefined" && typeof $1 !== "null") ? $1 : ""))');
    var compiledContent = compiler(content, parents, options, blockName);
    var output = '_output += _swig._w(_ctx._yog, function(_ctx) { var _output = ""; ' + compiledContent +
        ';return _output;}, ' + attrStr + ', ' + JSON.stringify(options) + ')(_ctx);';
    return output;
};

exports.parse = function (str, line, parser, types) {
    var currentKey;

    function parseToken(token, prevToken, out, parseValueFn) {
        if (currentKey && prevToken.type === types.ASSIGNMENT) {
            out.push({
                k: currentKey,
                v: parseValueFn(token.match)
            });
            currentKey = ''; // reset
            return true;
        }
        return false;
    }

    [{
        type: types.STRING,
        parser: function (match) {
            return match.replace(/^("|')?(.*)\1$/g, '$2');
        }
    }, {
        type: types.BOOL,
        parser: function (match) {
            return match === 'true';
        }
    }, {
        type: types.NUMBER,
        parser: function (match) {
            return match;
        }
    }].forEach(function (parseValue) {
        parser.on(parseValue.type, function (token) {
            var ret = parseToken(token, this.prevToken, this.out, parseValue.parser);
            if (ret) {
                return;
            }
            throw new Error('Unexpected token "' + token.match + '" on line ' + line + '.');
        });
    });

    parser.on(types.VAR, function (token) {
        // 用于获取被赋值的变量
        var ret = parseToken(token, this.prevToken, this.out, function (match) {
            return '<<VAR:' + match + '>>';
        });
        if (ret) {
            return;
        }
        // 获取当前Key
        if (~ATTRS.indexOf(token.match)) {
            currentKey = token.match;
            return false;
        }
        if (currentKey) {
            throw new Error('Unexpected token "' + token.match + '" on line ' + line + '.');
        }
        return true;
    });

    return true;
};

exports.ends = true;
