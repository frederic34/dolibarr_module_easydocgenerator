/**
 * Created by jess on 16/4/15.
 */


'use strict';

let grape = global.grape;

class Middle extends grape.MiddlewareBase{

    execute(http){
        //console.log(`[hook]:test_m2`);
        return Promise.resolve();
    }

}


module.exports = Middle;