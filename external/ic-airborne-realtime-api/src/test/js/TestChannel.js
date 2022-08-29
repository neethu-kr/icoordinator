'use strict';

var
  Channel = require('../../main/js/Channel'),
  ChannelRequest = require('../../main/js/ChannelRequest');

exports.Channel = {
  'Should destroy channel after retryLimit attach calls.': function (test) {
    var channel = new Channel('abc', function (token) {
      test.equal(token, 'abc');
      test.done();
    }, {
      retryLimit: 2
    });

    channel.attach(null);
    channel.attach(null);
    channel.attach(null);
  },

  'Should send "reconnect" message when destroyed.': function (test) {
    test.expect(2);

    var channel = new Channel('abc', function (token) {
      test.equal(token, 'abc');
    }, {
      retryLimit: -1
    });
    channel.attach(new ChannelRequest('abc', {}, {
      send: function (data) {
        test.equal(data.type, 'reconnect');
        test.done();
      }
    }));
  },

  'Should clear onDestroy callback when destroyed.': function (test) {
    var calls = 0;
    new Channel('abc', function () {
      calls += 1;
    }, {
      timeout: 0.001
    }).destroy();

    setTimeout(function () {
      test.equal(calls, 1);
      test.done();
    }, 5);
  },

  'Should pass on notify arguments to ChannelRequest#send().': function (test) {
    test.expect(1);

    var channel = new Channel();
    channel.attach(new ChannelRequest('abc', {}, {
      send: function (data) {
        test.equal(data.type, 'notify0');
        test.done();
      }
    }));
    channel.notify('notify0');
    channel.destroy();
  },

  'Should call ChannelRequest#send() only if ID > cursor_position.': function (
    test) {
    test.expect(2);

    var
      channel = new Channel(),
      calls = 0;

    channel.attach(new ChannelRequest('abc', {
      cursor_position: 10
    }, {
      send: function (data) {
        calls += 1;
        test.equal(data.type, 'new_event');
      }
    }));
    channel.notifyOfLatestEventId(9);
    channel.notifyOfLatestEventId(10);
    channel.notifyOfLatestEventId(11);
    channel.destroy();

    setTimeout(function () {
      test.equal(calls, 1);
      test.done();
    }, 5);
  },

  'Should pass panic message to ChannelRequest#send().': function (test) {
    test.expect(3);

    var channel = new Channel('abc', function (token) {
      test.equal(token, 'abc');
    });
    channel.attach(new ChannelRequest('abc', {}, {
      send: function (data) {
        test.equal(data.type, 'error');
        test.equal(data.message, 'message');
      }
    }));
    channel.panic('message');

    test.done();
  }
};
