'use strict';

var
  ChannelRequest = require('../../main/js/ChannelRequest'),
  ChannelManager = require('../../main/js/ChannelManager'),
  Promise = require('promise');

function mockEventBroker() {
  var
    callbacks = {},
    subscriptions = [];

  return {
    on: function (event, callback) {
      callbacks[event] = callback;
    },
    subscribe: function (brokerChannel) {
      subscriptions.push(brokerChannel);
    },
    unsubscribe: function (brokerChannel) {
      var index = subscriptions.indexOf(brokerChannel);
      if (index > -1) {
        subscriptions.splice(index, 1);
      }
    },
    callbacks: function () {
      return callbacks;
    },
    subscriptions: function () {
      return subscriptions;
    }
  };
}

exports.ChannelManager = {
  'Should send "new_event1" when received via broker.': function (test) {
    var eventBroker = mockEventBroker();

    new ChannelManager(eventBroker)
      .delegate(new ChannelRequest('xyz', {
        cursor_position: 10
      }, {
        send: function (data) {
          test.equal(data.type, 'new_event1');
          test.done();
        },
        sendError: function () {
          test.ok(false, "Expected 'new_event', not 'error'.");
          test.done();
        }
      }));

    eventBroker.callbacks().message('abc', 'new_event0');
    eventBroker.callbacks().message('xyz', 'new_event1');
    test.deepEqual(eventBroker.subscriptions(), ['xyz']);
  },

  'Should unsubscribe to broker when channel is destroyed.': function (test) {
    var eventBroker = mockEventBroker();

    new ChannelManager(eventBroker, {
        timeout: 0.0001
      })
      .delegate(new ChannelRequest('abc', {
        cursor_position: 10
      }, {
        send: function (data) {
          test.equal(data.type, 'reconnect');
          test.deepEqual(eventBroker.subscriptions(), []);
          test.done();
        },
        sendError: function () {
          test.ok(false, "Expected 'reconnect', not 'error'.");
          test.done();
        }
      }));

    test.deepEqual(eventBroker.subscriptions(), ['abc']);
  }
};
