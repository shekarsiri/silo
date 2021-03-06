/**
 * Naive in memory cache implementation
 * @type {Cache}
 */
const request = require('superagent');

let CacheNode = function(value){
    this._value = value;
    this._refreshCb = null;
    this._cb = null;
}

CacheNode.prototype = {
    from: function(){
        let from = arguments[0];
        if (typeof from === 'function') {
            throw "not implemented yet";
        } else if (typeof from === 'string') {
            this._refreshCb = function(resolve, reject){
                request.get(from)
                    .set('Accept', 'application/json')
                    .end(function(err, res){
                        if (res.ok) {
                            resolve(res.body);
                        } else {
                            reject();
                        }
                    });
            };
        } else {
            throw "from should have one argument, either url or callback"
        }
        return this;
    },
    onUpdate: function(callback){
        if (typeof callback !== 'function'){
            throw 'callback should be a function';
        }
        this._cb = callback;
        if (typeof this._value !== 'undefined') {
            callback(this._value);
        }
        return this;
    },
    refresh: function() {
        (new Promise(this._refreshCb)).then(function(value){
            this._value = value;
            if (typeof this._cb === 'function') {
                this._cb(value);
            }
        }.bind(this));
        return this;
    },
    /**
     * Remove listeners
     */
    cleanup: function(){
        this._cb = null;
    }
};

let Cache = function(){
    this._nodes = [];
};

Cache.prototype = {
    /**
     * @param key
     */
    get: function(key){
        let node = this._nodes[key];
        if (!node) {
            this._nodes[key] = node = new CacheNode();
        }

        return node;
    },
    // Shortcut for SmartCache.get().from()
    getFrom: function(url){
        return this.get(url).from(url);
    },
    cleanup: function(key){
        if (key in this._nodes) {
            this._nodes[key].cleanup();
        } else {
            console.log("Trying to clean "+key+" from Cache but doesn't exist");
        }
        return this;
    },
    refresh: function(key){
        this._nodes[key].refresh();
        return this;
    }
};

module.exports = Cache;
