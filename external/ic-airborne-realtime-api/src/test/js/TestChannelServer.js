'use strict';

var
  ChannelServer = require('../../main/js/ChannelServer'),
  http = require('http');

exports.ChannelServer = {
  'Should delegate /requests/token?a=1&b=2': function (test) {
    test.expect(4);
    var t, s;

    t = setTimeout(function () {
      test.done();
    }, 100);

    s = new ChannelServer(function (request) {
      clearTimeout(t);
      test.equal(request.token, 'token');
      test.deepEqual(request.queryParameters(), {
        a: '1',
        b: '2'
      });
      test.notStrictEqual(request.send, undefined);
      test.notStrictEqual(request.sendError, undefined);
      test.done();
    });
    s.listen(49152);

    http.get({
      port: 49152,
      path: '/channels/token?a=1&b=2'
    });
  },

  'Should reject GET /other/path': function (test) {
    test.expect(1);
    var t, s;

    t = setTimeout(function () {
      test.done();
    }, 100);

    s = new ChannelServer(function () {
      test.done();
    });
    s.listen(49153);

    http.get({
      port: 49153,
      path: '/other/path'
    }, function (res) {
      clearTimeout(t);
      test.equal(res.statusCode, 404);
      test.done();
    });
  },

  'Should respond with headers, then ping, then message.': function (test) {
    test.expect(3);

    new ChannelServer(function (request) {
      request.ping();
      request.send('hello');
    }).listen(49154);

    http.get({
      port: 49154,
      path: '/channels/token'
    }, function (res) {
      test.equal(res.headers['content-type'], 'application/json');

      var counter = 0;
      res.on('data', function (data) {
        switch (counter) {
        case 0:
          test.equal(data.toString(), ' ');
          break;

        case 1:
          test.equal(data.toString(), '{"type":"hello"}');
          break;

        default:
          test.ok(false, "Called too many times");
        }
        counter += 1;
      });
      res.on('end', function () {
        test.done();
      });
    });
  },

  'Should call Request#destroy() after responding.': function (test) {
    test.expect(1);

    new ChannelServer(function (request) {
      request.destroy = function () {
        test.ok(true);
      };
      request.send('new_event');
    }).listen(49155);

    http
      .get({
        port: 49155,
        path: '/channels/token'
      })
      .on('close', function () {
        test.done();
      });
  }
};
