/**
 * middleware 基类
 * Created by jess on 16/4/14.
 */


'use strict';

let HttpBase = require('./http_base.js');


class Middleware extends HttpBase {

    init(http){
        super.init(http)
    }

    execute(data){}

}



module.exports = Middleware;