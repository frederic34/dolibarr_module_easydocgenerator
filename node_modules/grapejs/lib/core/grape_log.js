/**
 * 封装日志系统
 * Created by jess on 16/4/18.
 */


'use strict';


const bunyan = require('bunyan');


class GrapeLog{

    constructor(conf){
        this.log = bunyan.createLogger( conf );
    }

    fatal(){
        this.log.fatal.apply( this.log, arguments);
        return this;
    }

    error(){
        this.log.error.apply( this.log, arguments );
        return this;
    }

    warn(){
        this.log.warn.apply( this.log, arguments );
        return this;
    }

    info(){
        this.log.info.apply( this.log, arguments );
        return this;
    }

    debug(){
        this.log.debug.apply( this.log, arguments );
        return this;
    }

    trace(){
        this.log.trace.apply( this.log, arguments );
        return this;
    }

}

let singleton = {

    getLog( conf ){
        return new GrapeLog( conf );
    }

};


module.exports = singleton;
