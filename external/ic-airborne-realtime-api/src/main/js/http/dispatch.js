/*
This code has been shamelessly stolen from Caolan McMahon, who made it
available on GitHub under the below license (MIT). As Caolan failed to merge a
pull request containing a bug fix critial to this project, the whole codebase
was taken, as permitted by the license, adapted, and then placed here.

Copyright (c) 2010 Caolan McMahon

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/
(function () {
  'use strict';

  var url = require('url');

  /**
   * Accepts a route key and returns object containing URL and method.
   *
   * Example argument:
   *     'GET /home/users'
   *
   * Expected result:
   *     {url: '/home/users', method: 'GET'}
   *
   * @private
   * @param {string} route Route to parse.
   */
  function parseRouteKey(route) {
    var method, path, match = /^([A-Z]+)(?:\s+|$)/.exec(route);
    if (match) {
      method = match[1];
      path = /^[A-Z]+\s+([\w$-_.+!*'(), ]*)$/.exec(route);
      route = path ? path[1] : '';
    }
    return {
      url: route,
      method: method
    };
  }

  /**
   * Accepts a nested map of route keys and callbacks, and produces an array.
   *
   * Example argument:
   *     {
   *       'GET /home': {
   *         '/users': function () {},
   *         '/admins': function () {}
   *       }
   *     }
   *
   * Expected result:
   *     [
   *       ['/home/users', 'GET', function () {}],
   *       ['/home/admins', 'GET', function () {}],
   *     ]
   *
   * @private
   * @param {Object} map Nested map of route keys and callbacks.
   */
  function flattenRouteMap(routeMap) {
    function iter(routeMap, accumulator, prefix, method) {
      Object.keys(routeMap).forEach(function (key) {
        var value = routeMap[key];

        key = parseRouteKey(key);
        if (typeof value === 'function') {
          accumulator.push([prefix + key.url, key.method || method, value]);

        } else {
          iter(value, accumulator, prefix + key.url, key.method);
        }
      });
      return accumulator;
    }
    return iter(routeMap, [], '');
  }

  /**
   * Takes a list of routes, as returned by #flattenRouteMap(), and turns all
   * plain text paths into their compiled regular expression equivalents.
   *
   * Example input:
   *     [
   *       ['abc', 'GET', function () {}],
   *       ['xyz', 'POST', function () {}]
   *     ]
   *
   * Expected result:
   *     [
   *       [/^abc$/, 'GET', function () {}],
   *       [/^xyz$/, 'POST', function () {}]
   *     ]
   *
   * @private
   * @param {Array} routeList List of route objects.
   */
  function compileRouteListUrls(routeList) {
    return routeList.map(function (route) {
      var pattern = route[0].replace(/\/:\w+/g, '(?:/([^\/]+))');
      route[0] = new RegExp('^' + pattern + '$');
      return route;
    });
  }

  /**
   * Takes a nested map of routes and route callbacks, and returns a function
   * taking the node.js http.IncomingMessage and http.ServerResponse objects as
   * arguments. The returned function may appropriately be given as sole
   * argument to the node.js http.createServer() function.
   *
   * Example input:
   *     {
   *       'GET /channels/:param': function (req, res, param) {},
   *       '/some': {
   *         '/regex_(\\w+)': {
   *         	 '/route': function (req, res) {}
   *         }
   *       }
   *     }
   *
   * Expected result:
   *     Function routing node.js requests to appropriate callbacks.
   *
   * More examples may be found in the test suite covering this function.
   *
   * @param {Object} routeMap Map of routes, as depicted in the above example.
   */
  module.exports = function (routeMap) {
    var routes = compileRouteListUrls(flattenRouteMap(routeMap));
    return function (req, res) {
      var args = [req, res];
      routes.some(function (route) {
        var match = route[0].exec(url.parse(req.url).pathname);
        if (match) {
          if (!route[1] || route[1] === req.method) {
            route[2].apply(null, args.concat(match.slice(1)));
            return true;
          }
        }
        return false;
      });
    };
  };
}());
