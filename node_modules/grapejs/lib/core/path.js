/**
 * Created by wangcheng on 16/4/13.
 */


'use strict';

const path = require('path');

const sep = path.sep;

//框架lib目录
const grapeLibPath = path.dirname( __dirname );
//框架根目录
const grapeFrameworkPath = path.dirname( grapeLibPath );

module.exports = {
    //框架根目录
    GRAPE_FRAMEWORK_PATH : grapeFrameworkPath,
    //框架lib目录
    GRAPE_LIB_PATH : grapeLibPath,
    //框架的配置文件目录
    GRAPE_CONFIG_PATH : path.normalize( `${grapeLibPath}${sep}config`),
    //框架的middleware文件目录
    GRAPE_MIDDLEWARE_PATH : path.normalize( `${grapeLibPath}${sep}middleware`),
    //应用所在目录
    APP_PATH : null,
    //应用静态资源目录
    APP_STATIC_PATH : null,
    //应用模板目录
    APP_VIEW_PATH : null,
    //应用静态资源map目录
    APP_MAP_PATH : null,
    //应用所有URL的前缀
    APP_URL_PREFIX : '/',
    //应用的common模块
    APP_COMMON_MODULE_PATH : '',
    //应用的日志记录目录
    APP_LOG_PATH : ''
};
