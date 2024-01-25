/**
 * 所有policy 的基类, 具体应用中, 可以覆盖掉
 * Created by jess on 16/5/4.
 */


'use strict';


const ControllerBase = require('./controller/base');

class PolicyBase extends ControllerBase{

    /**
     * 所有policy 都执行 execute 方法
     * @param data {any} 可以在执行policy时,传入额外参数
     * @return {promise}
     */
    execute( data ){
        return Promise.reject( new Error(`policy[${this.constructor.name}]必须覆盖基类提供的[execute]方法!!`) );
    }

}


module.exports = PolicyBase;

