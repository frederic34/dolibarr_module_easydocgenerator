/**
 * 提供给 bunyan 库的 stream
 * Created by jess on 16/5/18.
 */


'use strict';

const EventEitter = require('events').EventEmitter;
const util = require('util');

function DevStream( options ){

    this.options = Object.assign( {

        showHidden : false,
        depth : 4,
        colors : false,
        customInspect : false,
        showProxy : false,
        maxArrayLength : 100

    }, options || {} );

}

util.inherits( DevStream, EventEitter );

DevStream.prototype.write = function( record ){
    let str = util.inspect( record, this.options );
    console.log( str );
};



let singleton = {

    dev : function( options ){
        return new DevStream( options );
    }

};



module.exports = singleton;

