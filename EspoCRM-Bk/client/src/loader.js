/************************************************************************
 * This file is part of Simply I Do.
 *
 * Simply I Do - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://simplyido.com
 *
 * Simply I Do is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Simply I Do is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Simply I Do. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Simply I Do" word.
 ************************************************************************/

var Espo = Espo || {classMap:{}};

(function (Espo, _, $) {

    var root = this;

    Espo.Loader = function (cache, cacheTimestamp) {
        this.basePath = $('script[data-base-path]').data('basePath') || '';

        this.cache = cache || null;
        this._loadCallbacks = {};

        this.pathsBeingLoaded = {};

        this.libsConfig = {};

        this.cacheTimestamp = cacheTimestamp || null;
    }

    _.extend(Espo.Loader.prototype, {

        cache: null,

        data: null,

        classMap: Espo,

        _loadCallbacks: null,

        libsConfigUrl: 'client/cfg/libs.json',

        _getClass: function (name) {
            if (name in this.classMap) {
                return this.classMap[name];
            }
            return false;
        },

        _setClass: function (name, o) {
            this.classMap[name] = o;
        },

        _nameToPath: function (name) {
            var path;

            if (name.indexOf(':') != -1) {
                var arr = name.split(':');
                var namePart = arr[1];
                var modulePart = arr[0];
                if (modulePart == 'custom') {
                    path = 'client/custom/src/' + namePart;
                } else {
                    path = 'client/modules/' + modulePart + '/src/' + namePart;
                }
            } else {
                path = 'client/src/' + name;
            }
            path += '.js';

            return path;
        },

        _execute: function (script) {
            eval.call(root, script);
        },

        _executeLoadCallback: function (subject, o) {
            if (subject in this._loadCallbacks) {
                this._loadCallbacks[subject].forEach(function (callback) {
                    callback(o);
                });
                delete this._loadCallbacks[subject];
            }
        },

        define: function (subject, dependency, callback) {
            subject = this.normalizeClassName(subject);

            var self = this;
            var proceed = function (relObj) {
                var o = callback.apply(this, arguments);
                if (!o) {
                    if (self.cache) {
                        self.cache.clear('script', name);
                    }
                    throw new Error("Could not load '" + subject + "'");
                }
                self._setClass(subject, o);
                self._executeLoadCallback(subject, o);
            };

            if (!dependency) {
                proceed();
            } else {
                this.require(dependency, function () {
                    proceed.apply(this, arguments);
                });
            }
        },

        require: function (subject, callback, errorCallback) {
            if (Object.prototype.toString.call(subject) === '[object Array]') {
                var list = subject;
                list.forEach(function (item, i) {
                    list[i] = this.normalizeClassName(list[i]);
                }, this);
            } else {
                subject = this.normalizeClassName(subject);
                this.load(subject, callback, errorCallback);
                return;
            }
            var totalCount = list.length;
            var readyCount = 0;
            var loaded = {};

            if (list.length) {
                list.forEach(function (name) {
                    this.load(name, function (c) {
                        loaded[name] = c;
                        readyCount++;
                        if (readyCount == totalCount) {
                            var args = [];
                            for (var i in list) {
                                args.push(loaded[list[i]]);
                            }
                            callback.apply(this, args);
                        }
                    });
                }, this);
            } else {
                callback.apply(this);
            }
        },

        convertCamelCaseToHyphen: function (string) {
            if (string == null) {
                return string;
            }
            return string.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
        },

        normalizeClassName: function (name) {
            var normalizedName = name;
            if (!!/[A-Z]/.exec(name[0])) {
                if (name.indexOf(':') != -1) {
                    var arr = name.split(':');
                    var modulePart = arr[0];
                    var namePart = arr[1];
                    normalizedName = this.convertCamelCaseToHyphen(modulePart) + ':' + this.convertCamelCaseToHyphen(namePart).split('.').join('/');
                } else {
                    normalizedName = this.convertCamelCaseToHyphen(name).split('.').join('/');
                }
            }

            return normalizedName;
        },

        _addLoadCallback: function (name, callback) {
            if (!(name in this._loadCallbacks)) {
                this._loadCallbacks[name] = [];
            }
            this._loadCallbacks[name].push(callback);
        },

        dataLoaded: {},

        load: function (name, callback, errorCallback) {
            var dataType, type, path, fetchObject;
            var realName = name;

            if (name.indexOf('lib!') === 0) {
                dataType = 'script';
                type = 'lib';

                realName = name.substr(4);
                path = realName;

                var exportsTo = 'window';
                var exportsAs = realName;

                if (realName in this.libsConfig) {
                    path = this.libsConfig[realName].path || path;
                    exportsTo = this.libsConfig[realName].exportsTo || exportsTo;
                    exportsAs = this.libsConfig[realName].exportsAs || exportsAs;
                }

                fetchObject = function (name, d) {
                    var from = root;
                    if (exportsTo == 'window') {
                        from = root;
                    } else {
                        exportsTo.split('.').forEach(function (item) {
                            from = from[item];
                        });
                    }
                    if (exportsAs in from) {
                        return from[exportsAs];
                    }
                }

            } else if (name.indexOf('res!') === 0) {
                dataType = 'text';
                type = 'res';

                realName = name.substr(4);
                path = realName;
            } else {
                dataType = 'script';
                type = 'class';

                if (!name || name == '') {
                    throw new Error("Can not load empty class name");
                }

                var c = this._getClass(name);
                if (c) {
                    callback(c);
                    return;
                }

                path = this._nameToPath(name);
            }

            if (name in this.dataLoaded) {
                callback(this.dataLoaded[name]);
                return;
            }

            if (this.cache) {
                var cached = this.cache.get(type, name);
                if (cached) {
                    if (dataType == 'script') {
                        this._execute(cached);
                    }
                    if (type == 'class') {
                        var c = this._getClass(name);
                        if (c) {
                            callback(c);
                            return;
                        }
                        this._addLoadCallback(name, callback);
                    } else {
                        var d = cached;
                        if (typeof fetchObject == 'function') {
                            d = fetchObject(realName, cached);
                        }
                        this.dataLoaded[name] = d;
                        callback(d);
                    }

                    return;
                }
            }

            if (path in this.pathsBeingLoaded) {
                this._addLoadCallback(name, callback);
                return;
            }
            this.pathsBeingLoaded[path] = true;

            var useCache = false;
            if (this.cacheTimestamp) {
                useCache = true;
                var sep = (path.indexOf('?') > -1) ? '&' : '?';
                path += sep + 'r=' + this.cacheTimestamp;
            }

            $.ajax({
                type: 'GET',
                cache: useCache,
                dataType: 'text',
                local: true,
                url: this.basePath + path,
                success: function (response) {
                    if (this.cache) {
                        this.cache.set(type, name, response);
                    }

                    this._addLoadCallback(name, callback);

                    if (dataType == 'script') {
                        this._execute(response);
                    }

                    if (type == 'class') {
                        var c = this._getClass(name);
                        if (c && typeof c === 'function') {
                            this._executeLoadCallback(name, c);
                        }
                    } else {
                        var d = response;
                        if (typeof fetchObject == 'function') {
                            d = fetchObject(realName, response);
                        }
                        this.dataLoaded[name] = d;
                        this._executeLoadCallback(name, d);
                    }
                    return;
                }.bind(this),
                error: function (event, xhr, options) {
                    if (typeof errorCallback == 'function') {
                        errorCallback();
                        return;
                    }
                    throw new Error("Could not load file '" + path + "'");
                }
            });
        },


        loadLib: function (url, callback) {
            if (this.cache) {
                var script = this.cache.get('script', url);
                if (script) {
                    this._execute(script);
                    if (typeof callback == 'function') {
                        callback();
                    }
                    return;
                }
            }

            $.ajax({
                url: this.basePath + url,
                type: 'GET',
                dataType: 'script',
                local: true,
                success: function () {
                    if (typeof callback == 'function') {
                        callback();
                    }
                },
                error: function () {
                    throw new Error("Could not load file '" + url + "'");
                },
            });

        },

        loadLibsConfig: function (callback) {
            $.ajax({
                url: this.basePath + this.libsConfigUrl,
                dataType: 'json',
                local: true,
                success: function (data) {
                    this.libsConfig = data;
                    callback();
                }.bind(this)
            });
        },

        addLibsConfig: function (data) {
            this.libsConfig = _.extend(this.libsConfig, data);
        },
    });

    Espo.loader = new Espo.Loader();
    Espo.require = function (subject, callback, context, errorCallback) {
        if (context) {
            callback = callback.bind(context);
        }
        Espo.loader.require(subject, callback, errorCallback);
    }
    Espo.define = function (subject, dependency, callback) {
        Espo.loader.define(subject, dependency, callback);
    }
    Espo.loadLib = function (url, callback) {
        Espo.loader.loadLib(url, callback);
    }

}).call(this, Espo, _, $);
