/**
 *  负责日期格式化的 swig filter
 * Created by jess on 16/4/27.
 */


'use strict';


module.exports = function dateFormat( date, format ){
    let fn = d => {
        return ('0' + d).slice(-2);
    };

    if( date ){
        if( grape.util.isNumber(date) ){
            date = new Date( date );
        }else if(grape.util.isString(date)){
            date = new Date(Date.parse(date));
        }
    }

    let d = date || new Date();

    format = format || 'YYYY-MM-DD HH:mm:ss';
    let formats = {
        YYYY: d.getFullYear(),
        MM:  fn(d.getMonth() + 1),
        DD: fn(d.getDate()),
        HH: fn(d.getHours()),
        mm: fn(d.getMinutes()),
        ss: fn(d.getSeconds())
    };

    return format.replace(/([a-z])\1+/ig, a => {
        return formats[a] || a;
    });
};