(function () {
  'use strict';

  var redis = require('redis');

  if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
      position = position || 0;
      return this.indexOf(searchString, position) === position;
    };
  }
  /**
   * Message broker specialized at receiving real-time event notifications via
   * some message queue.
   *
   * @param {Object} config Object containing message queue credentials.
   */
  function EventBroker(config) {
    /** @private */
    if (config.url.startsWith("rediss") || config.url.startsWith("tls")) {
      this.redis = redis.createClient(config.url, {tls: {rejectUnauthorized: false}});
    } else {
      this.redis = redis.createClient(config.url);
    }
  }

  /**
   * Registers event receiver callback.
   *
   * Valid event identifiers are "error" and "message".
   *
   * @param  {string}   event    Event identifier.
   * @param  {Function} callback Event receiver.
   */
  EventBroker.prototype.on = function (event, callback) {
    this.redis.on(event, callback);
  };

  /**
   * Subscribes to identified broker channel.
   *
   * @param {string} brokerChannel Broker channel identifier.
   */
  EventBroker.prototype.subscribe = function (brokerChannel) {
    this.redis.subscribe(brokerChannel);
  };

  /**
   * Unsubscribes to identified broker channel.
   *
   * @param {string} brokerChannel Broker channel identifier.
   */
  EventBroker.prototype.unsubscribe = function (brokerChannel) {
    this.redis.unsubscribe(brokerChannel);
  };

  module.exports = EventBroker;
}());
