(function () {
  'use strict';

  var
    Channel = require('./Channel'),
    iclog = require('./log/iclog');

  /**
   * Manages the lifetimes of client channels.
   *
   * Accepts delegated Request objects, attaches these to Channel objects, and
   * makes sure these are notified of relevant events.
   *
   * Valid channel lifetime options are `timeout` (channel lifetime limit, in
   * seconds) and `retryLimit` (request attach limit).
   *
   * @param {EventBroker} eventBroker Event broker object.
   * @param {Object}      [options]   Channel lifetime options.
   */
  function ChannelManager(eventBroker, options) {
    /** @private */
    this.eventBroker = eventBroker;

    /** @private */
    this.options = options;

    /** @private */
    this.channels = {};

    var self = this;
    eventBroker.on('error', function (err) {
      iclog.warning('Event listener error.', new Error().stack, err);
    });
    eventBroker.on('message', function (token, message) {
      var channel = self.channels[token];
      if (channel) {
        channel.notify(message);
      }
    });
  }

  /**
   * Delegates care of given Request object to this channel manager.
   *
   * @param {Request} request Request to delegate.
   */
  ChannelManager.prototype.delegate = function (request) {
    this.attachToChannel(request);
  };

  /** @private */
  ChannelManager.prototype.attachToChannel = function (request) {
    var self, channel;

    channel = this.channels[request.token];
    if (!channel) {
      self = this;

      this.eventBroker.subscribe(request.token);
      channel = new Channel(request.token, function (token) {
        self.eventBroker.unsubscribe(token);
        delete self.channels[token];
      }, this.options);

      this.channels[request.token] = channel;
    }

    channel.attach(request);
  };

  module.exports = ChannelManager;
}());
