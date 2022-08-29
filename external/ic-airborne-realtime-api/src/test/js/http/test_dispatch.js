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
'use strict';

var dispatch = require('../../../main/js/http/dispatch');

module.exports = {
  'Should match GET /test.': function (test) {
    test.expect(2);
    var request = {
      url: '/test',
      method: 'GET'
    };
    dispatch({
      '/test': function (req, res) {
        test.equals(req, request);
        test.equals(res, 'response');
        test.done();
      }
    })(request, 'response', 'next');
  },

  'Should not match XYZ /abc.': function (test) {
    var request = {
      url: '/abc',
      method: 'XYZ'
    };
    dispatch({
      '/test': function () {
        test.ok(false, 'should not be called');
      }
    })(request, 'response');

    test.done();
  },

  'Should match /abc/test123 with regex route.': function (test) {
    var request = {
      url: '/abc/test123'
    };
    dispatch({
      '/(\\w+)/test\\d*': function (req, res, group) {
        test.equals(req, request);
        test.equals(res, 'response');
        test.equals(group, 'abc');
        test.done();
      }
    })(request, 'response', 'next');
  },

  'Should only call first matching route.': function (test) {
    test.expect(3);
    var request = {
      url: '/abc',
      method: 'POST'
    };
    dispatch({
      '/(\\w+)/?': function (req, res, group) {
        test.equals(req, request);
        test.equals(res, 'response');
        test.equals(group, 'abc');
      },
      '/(\\w+)': function () {
        test.ok(false, 'only first match should be called');
      }
    })(request, 'response');
    setTimeout(test.done, 10);
  },

  'Should match /folder/some/other/path with nested route.': function (test) {
    var request = {
      url: '/folder/some/other/path',
      method: 'GET'
    };
    dispatch({
      '/folder': {
        '/some/other': {
          '/path': function (req, res) {
            test.equals(req, request);
            test.equals(res, 'response');
            test.done();
          }
        }
      }
    })(request, 'response', 'next');
  },

  'Should match /one/two/three with nested regex route.': function (test) {
    var request = {
      url: '/one/two/three',
      method: 'GET'
    };
    dispatch({
      '/(\\w+)': {
        '/(\\w+)': {
          '/(\\w+)': function (req, res, group1, group2, group3) {
            test.equals(req, request);
            test.equals(res, 'response');
            test.equals(group1, 'one');
            test.equals(group2, 'two');
            test.equals(group3, 'three');
            test.done();
          }
        }
      },
      '/one/two/three': function () {
        test.ok(false, 'should not be called, previous key matches');
        test.done();
      }
    })(request, 'response', 'next');
  },

  'Should match requests against proper method routes.': function (test) {
    test.expect(5);

    var callOrder, request, handler;

    callOrder = [];
    request = {
      url: '/test',
      method: 'GET'
    };
    handler = dispatch({
      'GET /test': function (req, res) {
        callOrder.push('GET');
        test.equals(req, request);
        test.equals(res, 'response');
      },
      'POST /test': function (req, res) {
        callOrder.push('POST');
        test.equals(req, request);
        test.equals(res, 'response');
      }
    });
    handler(request, 'response', 'next');
    request.method = 'POST';
    handler(request, 'response', 'next');
    request.method = 'DELETE';
    handler(request, 'response');

    test.same(callOrder, ['GET', 'POST']);
    test.done();
  },

  'Should match requests against nested method routes.': function (test) {
    test.expect(2);

    var request, handler;

    request = {
      url: '/path/test',
      method: 'GET'
    };
    handler = dispatch({
      '/path': {
        'GET /test': function (req, res) {
          test.equals(req, request);
          test.equals(res, 'response');
        }
      }
    });
    handler(request, 'response', function () {
      test.ok(false, 'should not be called');
    });
    request.method = 'POST';
    handler(request, 'response');

    test.done();
  },

  'Should match requests against nested method subroute.': function (test) {
    test.expect(2);
    var request, handler;

    request = {
      url: '/path/create/item',
      method: 'POST'
    };
    handler = dispatch({
      '/path': {
        'POST /create': {
          '/item': function (req, res) {
            test.equals(req, request);
            test.equals(res, 'response');
          }
        }
      }
    });
    handler(request, 'response', function () {
      test.ok(false, 'should not be called');
    });
    request.method = 'GET';
    handler(request, 'response');

    test.done();
  },

  'Should match request against multiple method subroute.': function (test) {
    test.expect(2);

    var request, handle_req;

    request = {
      url: '/path/create/item',
      method: 'GET'
    };
    handle_req = dispatch({
      '/path': {
        'POST /create': {
          'GET /item': function (req, res) {
            test.equals(req, request);
            test.equals(res, 'response');
          }
        }
      }
    });
    handle_req(request, 'response', function () {
      test.ok(false, 'should not be called');
    });
    request.method = 'POST';
    handle_req(request, 'response');

    test.done();
  },

  'Should accept any space between route method and URL.': function (test) {
    test.expect(4);

    var request, handle_req;

    request = {
      url: '/test',
      method: 'GET'
    };
    handle_req = dispatch({
      'GET    /test': function (req, res) {
        test.equals(req, request);
        test.equals(res, 'response');
      },
      'POST\t/test': function (req, res) {
        test.equals(req, request);
        test.equals(res, 'response');
      }
    });
    handle_req(request, 'response');
    request.method = 'POST';
    handle_req(request, 'response');
    test.done();
  },

  'Should match /abc/test123 against regex pattern route.': function (test) {
    var request = {
      url: '/abc/test123'
    };
    dispatch({
      '/:name/test\\d{3}': function (req, res, name) {
        test.equals(req, request);
        test.equals(res, 'response');
        test.equals(name, 'abc');
        test.done();
      }
    })(request, 'response');
  },

  'Should match /test/123 against nested pattern route.': function (test) {
    var request = {
      url: '/test/123'
    };
    dispatch({
      '/test': {
        '/:param': function (req, res, name) {
          test.equals(req, request);
          test.equals(res, 'response');
          test.equals(name, '123');
          test.done();
        }
      }
    })(request, 'response');
  },

  'Should match against route ending with method subroute.': function (test) {
    var request = {
      url: '/test/etc',
      method: 'POST'
    };
    dispatch({
      '/test': {
        GET: function () {
          test.ok(false, 'GET should not be called');
        },
        '/etc': {
          GET: function () {
            test.ok(false, 'GET should not be called');
          },
          POST: function (req, res) {
            test.equals(req, request);
            test.equals(res, 'response');
            test.done();
          }
        }
      }
    })(request, 'response');
  }
};
