'use strict';

var ChannelRequest = require('../../main/js/ChannelRequest');

exports.ChannelRequest = {
  'Should expose token and query given at construction.': function (test) {
    var c = new ChannelRequest('token', {
      'cursor_position': 1234
    }, null);
    test.equal(c.token, 'token');
    test.equal(c.queryParameters().cursor_position, 1234);
    test.done();
  },

  'Should delegate send call to controller.': function (test) {
    var c = new ChannelRequest(null, null, {
      send: function (data) {
        test.deepEqual(data, {
          type: 'event'
        });
        test.done();
      }
    });
    c.send('event');
  },

  'Should delegate error call to controller.': function (test) {
    var c = new ChannelRequest(null, null, {
      send: function (data) {
        test.deepEqual(data, {
          type: 'error',
          message: 'message'
        });
        test.done();
      }
    });
    c.sendError('message');
  },

  'Should delegate ping call to controller.': function (test) {
    test.expect(1);

    var c = new ChannelRequest(null, null, {
      ping: function () {
        test.ok(true);
      }
    });
    c.ping();
    test.done();
  },

  'Should stop automatic ping when destroying request.': function (test) {
    test.expect(1);

    var c = new ChannelRequest(null, null, {
      ping: function () {
        test.ok(true);
      },
      options: {
        pingInterval: 0.002
      }
    });
    setTimeout(c.destroy, 3);
    setTimeout(test.done, 4);
  }
};
